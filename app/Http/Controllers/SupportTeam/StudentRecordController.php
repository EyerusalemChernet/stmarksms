<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Helpers\Mk;
use App\Http\Requests\Student\StudentRecordCreate;
use App\Http\Requests\Student\StudentRecordUpdate;
use App\Models\AuditLog;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\Repositories\UserRepo;
use App\Http\Controllers\Controller;
use App\Services\RulesEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentRecordController extends Controller
{
    protected $loc, $my_class, $user, $student;

   public function __construct(LocationRepo $loc, MyClassRepo $my_class, UserRepo $user, StudentRepo $student)
   {
       $this->middleware('teamSA', ['only' => ['edit','update', 'reset_pass', 'create', 'store', 'graduated'] ]);
       $this->middleware('super_admin', ['only' => ['destroy',] ]);

        $this->loc = $loc;
        $this->my_class = $my_class;
        $this->user = $user;
        $this->student = $student;
   }

    public function reset_pass($st_id)
    {
        $st_id = Qs::decodeHash($st_id);
        $data['password'] = Hash::make('student');
        $this->user->update($st_id, $data);
        return back()->with('flash_success', __('msg.p_reset'));
    }

    public function create()
    {
        $data['my_classes'] = $this->my_class->all();
        $data['parents'] = $this->user->getUserByType('parent');
        $data['states'] = $this->loc->getStates();
        $data['nationals'] = $this->loc->getAllNationals();
        return view('pages.support_team.students.add', $data);
    }

    public function store(StudentRecordCreate $req)
    {
       $data =  $req->only(Qs::getUserRecord());
       $sr =  $req->only(Qs::getStudentData());

        $ct = $this->my_class->findTypeByClass($req->my_class_id)->code;
       /* $ct = ($ct == 'J') ? 'JSS' : $ct;
        $ct = ($ct == 'S') ? 'SS' : $ct;*/

        $data['user_type'] = 'student';
        $data['name'] = ucwords($req->name);
        $data['code'] = strtoupper(Str::random(10));
        $data['password'] = Hash::make('student');
        $data['photo'] = Qs::getDefaultUserImage();

        // Auto-generate admission number: STM-{YEAR}-{4-digit sequence}
        $year = date('Y');
        
        // Find the highest existing admission number for this year
        $existingAdmNos = \App\Models\StudentRecord::where('adm_no', 'LIKE', "STM-{$year}-%")
            ->pluck('adm_no')
            ->map(function($adm_no) {
                if (preg_match('/STM-\d{4}-(\d{4})/', $adm_no, $matches)) {
                    return intval($matches[1]);
                }
                return 0;
            })
            ->filter()
            ->sort();
        
        $sequence = $existingAdmNos->isEmpty() ? 1 : $existingAdmNos->last() + 1;
        
        // Ensure uniqueness by checking if the generated number already exists
        do {
            $adm_no = 'STM-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = \App\User::where('username', $adm_no)->exists() || 
                     \App\Models\StudentRecord::where('adm_no', $adm_no)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);

        $data['username'] = $adm_no;

        $profileFile = $req->hasFile('photo') ? $req->file('photo') : null;
        if (!$profileFile && $req->hasFile('birth_cert')) {
            $bc = $req->file('birth_cert');
            if (str_starts_with((string) $bc->getMimeType(), 'image/')) {
                $profileFile = $bc;
            }
        }
        if ($profileFile) {
            $f = Qs::getFileMetaData($profileFile);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $profileFile->storeAs(Qs::getUploadPath('student').$data['code'], $f['name']);
            $data['photo'] = asset('storage/' . $f['path']);
        }

        $user = $this->user->create($data); // Create User

        $enrollmentService = app(\App\Services\EnrollmentService::class);
        try {
            $section = $enrollmentService->assignAvailableSection((int) $req->my_class_id);
        } catch (\RuntimeException $e) {
            $user->delete();
            return Qs::json($e->getMessage(), false);
        }

        $sr['adm_no'] = $adm_no;
        $sr['user_id'] = $user->id;
        $sr['section_id'] = $section->id;
        $sr['session'] = Qs::getSetting('current_session');

        // Auto-calculate age from DOB
        if (!empty($data['dob'])) {
            try {
                $sr['age'] = \Carbon\Carbon::parse($data['dob'])->age;
            } catch (\Exception $e) {}
        }

        // Save birth certificate / student ID document if uploaded
        if ($req->hasFile('birth_cert')) {
            $doc = $req->file('birth_cert');
            $docPath = $doc->storeAs(
                Qs::getUploadPath('student') . $data['code'],
                'birth_cert.' . $doc->getClientOriginalExtension()
            );
            $sr['birth_cert_path'] = $docPath;
            $sr['birth_cert_name'] = $doc->getClientOriginalName();
        }

        $studentRecord = $this->student->createRecord($sr); // Create Student

        // Create enrollment record for the new enrollment-based system
        try {
            $yearId = $enrollmentService->activeYearId();
            if ($yearId) {
                $enrollmentService->createForAdmission(
                    $user->id,
                    $req->my_class_id,
                    $section->id,
                    $yearId
                );
            }
        } catch (\Exception $e) {
            // Non-fatal: enrollment creation failure should not block admission
            \Illuminate\Support\Facades\Log::warning('Enrollment creation failed for student ' . $user->id . ': ' . $e->getMessage());
        }

        AuditLog::log('created', 'students', "Student '{$data['name']}' admitted (Adm: {$data['username']})");
        return Qs::jsonStoreOk();
    }

    public function listByClass($class_id)
    {
        $data['my_class'] = $mc = $this->my_class->getMC(['id' => $class_id])->first();
        $data['students'] = $this->student->findStudentsByClass($class_id);
        $data['sections'] = $this->my_class->getClassSections($class_id);

        return is_null($mc) ? Qs::goWithDanger() : view('pages.support_team.students.list', $data);
    }

    public function graduated()
    {
        $data['my_classes'] = $this->my_class->all();
        $data['students'] = $this->student->allGradStudents();

        return view('pages.support_team.students.graduated', $data);
    }

    public function not_graduated($sr_id)
    {
        $d['grad'] = 0;
        $d['grad_date'] = NULL;
        $d['session'] = Qs::getSetting('current_session');
        $this->student->updateRecord($sr_id, $d);

        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function show($sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $data['sr'] = $this->student->getRecord(['id' => $sr_id])->first();

        /* Prevent Other Students/Parents from viewing Profile of others */
        if(Auth::user()->id != $data['sr']->user_id && !Qs::userIsTeamSAT() && !Qs::userIsMyChild($data['sr']->user_id, Auth::user()->id)){
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

        return view('pages.support_team.students.show', $data);
    }

    public function edit($sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $data['sr'] = $this->student->getRecord(['id' => $sr_id])->first();
        $data['my_classes'] = $this->my_class->all();
        $data['parents'] = $this->user->getUserByType('parent');
        $data['states'] = $this->loc->getStates();
        $data['nationals'] = $this->loc->getAllNationals();
        return view('pages.support_team.students.edit', $data);
    }

    public function update(StudentRecordUpdate $req, $sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $sr = $this->student->getRecord(['id' => $sr_id])->first();
        $d =  $req->only(Qs::getUserRecord());
        $d['name'] = ucwords($req->name);

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath('student').$sr->user->code, $f['name']);
            $d['photo'] = asset('storage/' . $f['path']);
        }

        $this->user->update($sr->user->id, $d); // Update User Details

        $srec = $req->only(Qs::getStudentData());

        // Auto-recalculate age from updated DOB
        if (!empty($d['dob'])) {
            try {
                $srec['age'] = \Carbon\Carbon::parse($d['dob'])->age;
            } catch (\Exception $e) {}
        }

        $this->student->updateRecord($sr_id, $srec); // Update St Rec

        /*** If Class/Section is Changed in Same Year, Delete Marks/ExamRecord of Previous Class/Section ****/
        Mk::deleteOldRecord($sr->user->id, $srec['my_class_id']);

        return Qs::jsonUpdateOk();
    }

    /**
     * Download the CSV template for bulk student import.
     */
    public function bulkTemplate()
    {
        $headers = ['name','gender','dob','email','phone','address','class_name','year_admitted','religion','nationality','parent_email'];
        $example = ['ABEBE KEBEDE','Male','2012-05-12','abebe@email.com','0911234567','Addis Ababa','Grade 1',date('Y'),'Ethiopian Orthodox','Ethiopian','parent@email.com'];

        $csv = implode(',', $headers)."\n".implode(',', $example)."\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_bulk_template.csv"',
        ]);
    }

    /**
     * Download a student's uploaded document (birth cert / ID).
     * Super admin only.
     */
    public function downloadDocument($sr_id)
    {
        if (!Qs::userIsSuperAdmin()) return Qs::goWithDanger();

        $sr_id = Qs::decodeHash($sr_id);
        $sr = $this->student->getRecord(['id' => $sr_id])->first();

        if (!$sr || !$sr->birth_cert_path || !Storage::exists($sr->birth_cert_path)) {
            return back()->with('flash_danger', 'Document not found.');
        }

        return Storage::download($sr->birth_cert_path, $sr->birth_cert_name ?: 'document');
    }

    /**
     * Process bulk student import from CSV.
     */
    public function bulkImport(Request $req)
    {
        $req->validate(['csv_file' => 'required|file|mimes:csv,txt|max:5120']);

        $file    = $req->file('csv_file');
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $imported = 0;
        $errors   = [];
        $row      = 1;

        // Pre-load nationality map (name → id) for fast lookup
        $nationalityMap = \App\Models\Nationality::all()
            ->keyBy(fn($n) => strtolower(trim($n->name)));
        $ethiopianId = $nationalityMap['ethiopian']->id ?? null;

        // Pre-compute the next admission sequence once before the loop
        $year = date('Y');
        
        // Find the highest existing admission number for this year
        $existingAdmNos = \App\Models\StudentRecord::where('adm_no', 'LIKE', "STM-{$year}-%")
            ->pluck('adm_no')
            ->map(function($adm_no) {
                if (preg_match('/STM-\d{4}-(\d{4})/', $adm_no, $matches)) {
                    return intval($matches[1]);
                }
                return 0;
            })
            ->filter()
            ->sort();
        
        $seq = $existingAdmNos->isEmpty() ? 1 : $existingAdmNos->last() + 1;

        while (($line = fgetcsv($handle)) !== false) {
            $row++;

            // Skip completely empty rows
            if (empty(array_filter($line, fn($v) => trim($v) !== ''))) continue;

            if (count($line) < count($headers)) {
                $errors[] = "Row {$row}: Not enough columns — skipped.";
                continue;
            }

            $data = array_combine($headers, array_map('trim', $line));

            // ── Required: name ──────────────────────────────────────────────
            if (empty($data['name']) || strlen($data['name']) < 3) {
                $errors[] = "Row {$row}: Name is required (min 3 characters) — skipped.";
                continue;
            }

            // ── Required: gender ────────────────────────────────────────────
            if (!in_array($data['gender'] ?? '', ['Male', 'Female'])) {
                $errors[] = "Row {$row}: Gender must be 'Male' or 'Female' — skipped.";
                continue;
            }

            // ── Required: dob ───────────────────────────────────────────────
            if (empty($data['dob'])) {
                $errors[] = "Row {$row}: Date of Birth is required — skipped.";
                continue;
            }
            try {
                $dob = \Carbon\Carbon::parse($data['dob']);
                $age = $dob->age;
                if ($age < 3 || $age > 25) {
                    $errors[] = "Row {$row}: Age must be between 3 and 25 (DOB: {$data['dob']}) — skipped.";
                    continue;
                }
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: Invalid date of birth '{$data['dob']}' — skipped.";
                continue;
            }

            // ── Resolve class ────────────────────────────────────────────────
            $class = \App\Models\MyClass::where('name', $data['class_name'] ?? '')->first();
            if (!$class) {
                $errors[] = "Row {$row}: Class '{$data['class_name']}' not found — skipped.";
                continue;
            }

            // ── Auto-assign section (lowest enrollment) ─────────────────────
            try {
                $section = app(\App\Services\EnrollmentService::class)->assignAvailableSection($class->id);
            } catch (\RuntimeException $e) {
                $errors[] = "Row {$row}: {$e->getMessage()} — skipped.";
                continue;
            }

            // ── Duplicate email check ────────────────────────────────────────
            if (!empty($data['email']) && \App\User::where('email', $data['email'])->exists()) {
                $errors[] = "Row {$row}: Email '{$data['email']}' already exists — skipped.";
                continue;
            }

            // ── Resolve nationality (default Ethiopian) ──────────────────────
            $nalId = $ethiopianId;
            if (!empty($data['nationality'])) {
                $nalId = $nationalityMap[strtolower($data['nationality'])]->id ?? $ethiopianId;
            }

            // ── Generate admission number (sequential, with uniqueness check) ──────────
            do {
                $adm_no = 'STM-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
                $exists = \App\User::where('username', $adm_no)->exists() || 
                         \App\Models\StudentRecord::where('adm_no', $adm_no)->exists();
                if ($exists) {
                    $seq++;
                }
            } while ($exists);
            
            $seq++; // Increment for next student

            $code = strtoupper(Str::random(10));

            $user = $this->user->create([
                'name'                 => strtoupper(trim($data['name'])),
                'email'                => !empty($data['email']) ? $data['email'] : null,
                'phone'                => $data['phone'] ?? null,
                'dob'                  => $dob->format('Y-m-d'),
                'gender'               => $data['gender'],
                'address'              => !empty($data['address']) ? $data['address'] : 'N/A',
                'nal_id'               => $nalId,
                'user_type'            => 'student',
                'code'                 => $code,
                'username'             => $adm_no,
                'password'             => Hash::make('student'),
                'must_change_password' => false, // students don't log in directly
                'photo'                => Qs::getDefaultUserImage(),
            ]);

            $this->student->createRecord([
                'user_id'       => $user->id,
                'my_class_id'   => $class->id,
                'section_id'    => $section->id,
                'adm_no'        => $adm_no,
                'year_admitted' => !empty($data['year_admitted']) ? $data['year_admitted'] : $year,
                'religion'      => $data['religion'] ?? null,
                'age'           => $age,
                'session'       => Qs::getSetting('current_session'),
                'my_parent_id'  => !empty($data['parent_email'])
                    ? (\App\User::where('email', $data['parent_email'])->where('user_type','parent')->value('id') ?? null)
                    : null,
            ]);

            // Create enrollment record for the new enrollment-based system
            try {
                $enrollmentService = app(\App\Services\EnrollmentService::class);
                $yearId = $enrollmentService->activeYearId();
                if ($yearId) {
                    $enrollmentService->createForAdmission($user->id, $class->id, $section->id, $yearId);
                }
            } catch (\Exception $e) {
                // Non-fatal — log but don't block bulk import
            }

            $imported++;
        }

        fclose($handle);

        AuditLog::log('bulk_import', 'students', "Bulk admitted {$imported} student(s).");

        $msg = "{$imported} student(s) imported successfully.";
        if ($errors) $msg .= ' ' . count($errors) . ' row(s) skipped.';

        return response()->json(['ok' => $imported > 0, 'msg' => $msg, 'errors' => $errors]);
    }

    public function destroy($st_id)
    {
        if(!$st_id){return Qs::goWithDanger();}

        $sr = $this->student->getRecord(['user_id' => $st_id])->first();
        $path = Qs::getUploadPath('student').$sr->user->code;
        Storage::exists($path) ? Storage::deleteDirectory($path) : false;
        $this->user->delete($sr->user->id);

        return back()->with('flash_success', __('msg.del_ok'));
    }

}
