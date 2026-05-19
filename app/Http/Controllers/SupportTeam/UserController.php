<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Requests\UserRequest;
use App\Models\Department;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\UserRepo;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class UserController extends Controller
{
    protected $user, $loc, $my_class;

    public function __construct(UserRepo $user, LocationRepo $loc, MyClassRepo $my_class)
    {
        $this->middleware('teamSA', ['only' => ['index', 'store', 'edit', 'update'] ]);
        $this->middleware('super_admin', ['only' => ['reset_pass','destroy'] ]);

        $this->user = $user;
        $this->loc = $loc;
        $this->my_class = $my_class;
    }

    public function index()
    {
        $ut = $this->user->getAllTypes();
        $ut2 = $ut->where('level', '>', 2);

        $d['user_types'] = Qs::userIsAdmin() ? $ut2 : $ut;
        $d['states'] = $this->loc->getStates();
        $d['users'] = $this->user->getPTAUsers();
        $d['nationals'] = $this->loc->getAllNationals();
        $d['blood_groups'] = $this->user->getBloodGroups();
        $d['departments'] = Department::orderBy('name')->get();
        $teacherType = $ut->firstWhere('title', 'teacher');
        $d['teacher_type_hash'] = $teacherType ? Qs::hash($teacherType->id) : '';
        return view('pages.support_team.users.index', $d);
    }

    public function edit($id)
    {
        $id = Qs::decodeHash($id);
        $d['user'] = $this->user->find($id);
        $d['states'] = $this->loc->getStates();
        $d['users'] = $this->user->getPTAUsers();
        $d['blood_groups'] = $this->user->getBloodGroups();
        $d['nationals'] = $this->loc->getAllNationals();
        $d['departments'] = Department::orderBy('name')->get();
        return view('pages.support_team.users.edit', $d);
    }

    public function reset_pass($id)
    {
        // Redirect if Making Changes to Head of Super Admins
        if(Qs::headSA($id)){
            return back()->with('flash_danger', __('msg.denied'));
        }

        $data['password'] = Hash::make('user');
        $data['must_change_password'] = true; // force password change after reset
        $this->user->update($id, $data);
        return back()->with('flash_success', __('msg.pu_reset'));
    }

    public function store(UserRequest $req)
    {
        $user_type = $this->user->findType($req->user_type)->title;

        $data = $req->except(Qs::getStaffRecord());
        $data['name'] = ucwords($req->name);
        $data['user_type'] = $user_type;
        $data['photo'] = Qs::getDefaultUserImage();
        $data['code'] = strtoupper(Str::random(10));

        $user_is_staff = in_array($user_type, Qs::getStaff());
        $user_is_teamSA = in_array($user_type, Qs::getTeamSA());

        $staff_id = Qs::getAppCode().'/STAFF/'.date('Y/m', strtotime($req->emp_date)).'/'.mt_rand(1000, 9999);
        $data['username'] = $uname = ($user_is_teamSA) ? $req->username : $staff_id;

        $pass = $req->password ?: $user_type;
        $data['password'] = Hash::make($pass);
        $data['must_change_password'] = true; // force password change on first login

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath($user_type).$data['code'], $f['name']);
            $data['photo'] = asset('storage/' . $f['path']);
        }

        /* Ensure that both username and Email are not blank*/
        if(!$uname && !$req->email){
            return back()->with('pop_error', __('msg.user_invalid'));
        }

        $user = $this->user->create($data); // Create User

        /* CREATE STAFF RECORD */
        if($user_is_staff){
            $d2 = $req->only(Qs::getStaffRecord());
            $d2['user_id'] = $user->id;
            $d2['code'] = $staff_id;
            $this->user->createStaffRecord($d2);
        }

        /* AUTO-CREATE EMPLOYEE RECORD for HR self-service portal */
        if ($user_is_staff) {
            \App\Services\EmployeeProfileService::createFromUser($user);
        }

        return Qs::jsonStoreOk();
    }

    public function update(UserRequest $req, $id)
    {
        $id = Qs::decodeHash($id);

        // Redirect if Making Changes to Head of Super Admins
        if(Qs::headSA($id)){
            return Qs::json(__('msg.denied'), FALSE);
        }

        $user = $this->user->find($id);

        $user_type = $user->user_type;
        $user_is_staff = in_array($user_type, Qs::getStaff());
        $user_is_teamSA = in_array($user_type, Qs::getTeamSA());

        $data = $req->except(Qs::getStaffRecord());
        $data['name'] = ucwords($req->name);
        $data['user_type'] = $user_type;

        if($user_is_staff && !$user_is_teamSA){
            $data['username'] = Qs::getAppCode().'/STAFF/'.date('Y/m', strtotime($req->emp_date)).'/'.mt_rand(1000, 9999);
        }
        else {
            $data['username'] = $user->username;
        }

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath($user_type).$user->code, $f['name']);
            $data['photo'] = asset('storage/' . $f['path']);
        }

        $this->user->update($id, $data);   /* UPDATE USER RECORD */

        /* UPDATE STAFF RECORD */
        if($user_is_staff){
            $d2 = $req->only(Qs::getStaffRecord());
            $d2['code'] = $data['username'];
            $this->user->updateStaffRecord(['user_id' => $id], $d2);
        }

        return Qs::jsonUpdateOk();
    }

    public function show($user_id)
    {
        $user_id = Qs::decodeHash($user_id);
        if(!$user_id){return back();}

        $data['user'] = $this->user->find($user_id);

        /* Prevent Other Students from viewing Profile of others*/
        if(Auth::user()->id != $user_id && !Qs::userIsTeamSAT() && !Qs::userIsMyChild(Auth::user()->id, $user_id)){
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

        return view('pages.support_team.users.show', $data);
    }

    public function bulkTemplate()
    {
        $headers = ['user_type','name','email','username','phone','gender','dob','address','emp_date','password'];
        $example = ['teacher','ABEBE KEBEDE','abebe@email.com','abebe.kebede','0911234567','Male','1990-05-12','Addis Ababa',date('Y-m-d'),'Teacher@123'];
        $csv = implode(',', $headers) . "\n" . implode(',', $example) . "\n";
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_bulk_template.csv"',
        ]);
    }

    public function bulkImport(\Illuminate\Http\Request $req)
    {
        $req->validate(['csv_file' => 'required|file|mimes:csv,txt|max:5120']);

        $file    = $req->file('csv_file');
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $imported = 0;
        $errors   = [];
        $row      = 1;

        // Build a map of user type name → UserType model
        $typeMap = \App\Models\UserType::all()->keyBy(fn($t) => strtolower($t->title));

        while (($line = fgetcsv($handle)) !== false) {
            $row++;

            // Skip completely empty rows
            if (empty(array_filter($line, fn($v) => trim($v) !== ''))) continue;

            if (count($line) < count($headers)) {
                $errors[] = "Row {$row}: Not enough columns — skipped.";
                continue;
            }

            $data = array_combine($headers, array_map('trim', $line));

            // ── Required: name ───────────────────────────────────────────────
            if (empty($data['name']) || strlen($data['name']) < 3) {
                $errors[] = "Row {$row}: Name is required (min 3 characters) — skipped.";
                continue;
            }

            // ── Required: gender ─────────────────────────────────────────────
            if (!in_array($data['gender'] ?? '', ['Male', 'Female'])) {
                $errors[] = "Row {$row}: Gender must be 'Male' or 'Female' — skipped.";
                continue;
            }

            // ── Resolve user type ────────────────────────────────────────────
            $typeKey  = strtolower($data['user_type'] ?? '');
            $userType = $typeMap[$typeKey] ?? null;
            if (!$userType) {
                $errors[] = "Row {$row}: Unknown user type '{$data['user_type']}'. Valid: " . implode(', ', $typeMap->keys()->toArray()) . " — skipped.";
                continue;
            }

            // ── Duplicate email check ────────────────────────────────────────
            if (!empty($data['email']) && \App\User::where('email', $data['email'])->exists()) {
                $errors[] = "Row {$row}: Email '{$data['email']}' already exists — skipped.";
                continue;
            }

            // ── Duplicate username check ─────────────────────────────────────
            if (!empty($data['username']) && \App\User::where('username', $data['username'])->exists()) {
                $errors[] = "Row {$row}: Username '{$data['username']}' already exists — skipped.";
                continue;
            }

            // ── Validate DOB if provided ─────────────────────────────────────
            $dobFormatted = null;
            if (!empty($data['dob'])) {
                try {
                    $dobFormatted = \Carbon\Carbon::parse($data['dob'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $errors[] = "Row {$row}: Invalid date of birth '{$data['dob']}' — skipped.";
                    continue;
                }
            }

            $userTypeTitle = $userType->title;
            $userIsStaff   = in_array($userTypeTitle, Qs::getStaff());
            $userIsTeamSA  = in_array($userTypeTitle, Qs::getTeamSA());

            $empDate  = !empty($data['emp_date']) ? $data['emp_date'] : date('Y-m-d');
            $staffId  = Qs::getAppCode() . '/STAFF/' . date('Y/m', strtotime($empDate)) . '/' . mt_rand(1000, 9999);
            $username = $userIsTeamSA ? ($data['username'] ?: $staffId) : $staffId;
            $pass     = !empty($data['password']) ? $data['password'] : $userTypeTitle;
            $code     = strtoupper(Str::random(10));

            $user = $this->user->create([
                'name'                 => strtoupper(trim($data['name'])),
                'email'                => !empty($data['email']) ? $data['email'] : null,
                'username'             => $username,
                'phone'                => $data['phone'] ?? null,
                'dob'                  => $dobFormatted,
                'gender'               => $data['gender'],
                'address'              => !empty($data['address']) ? $data['address'] : 'N/A',
                'user_type'            => $userTypeTitle,
                'code'                 => $code,
                'password'             => Hash::make($pass),
                'must_change_password' => true, // force password change on first login
                'photo'                => Qs::getDefaultUserImage(),
            ]);

            if ($userIsStaff) {
                $this->user->createStaffRecord([
                    'user_id'  => $user->id,
                    'code'     => $staffId,
                    'emp_date' => $empDate,
                ]);

                /* AUTO-CREATE EMPLOYEE RECORD for HR self-service portal */
                \App\Services\EmployeeProfileService::createFromUser($user);
            }

            $imported++;
        }

        fclose($handle);

        \App\Models\AuditLog::log('bulk_import', 'users', "Bulk created {$imported} user(s).");

        $msg = "{$imported} user(s) imported successfully.";
        if ($errors) $msg .= ' ' . count($errors) . ' row(s) skipped.';

        return response()->json(['ok' => $imported > 0, 'msg' => $msg, 'errors' => $errors]);
    }

    public function destroy($id)
    {
        $id = Qs::decodeHash($id);

        // Redirect if Making Changes to Head of Super Admins
        if(Qs::headSA($id)){
            return back()->with('pop_error', __('msg.denied'));
        }

        $user = $this->user->find($id);

        if($user->user_type == 'teacher' && $this->userTeachesSubject($user)) {
            return back()->with('pop_error', __('msg.del_teacher'));
        }

        $path = Qs::getUploadPath($user->user_type).$user->code;
        Storage::exists($path) ? Storage::deleteDirectory($path) : true;
        $this->user->delete($user->id);

        return back()->with('flash_success', __('msg.del_ok'));
    }

    protected function userTeachesSubject($user)
    {
        $subjects = $this->my_class->findSubjectByTeacher($user->id);
        return ($subjects->count() > 0) ? true : false;
    }

}
