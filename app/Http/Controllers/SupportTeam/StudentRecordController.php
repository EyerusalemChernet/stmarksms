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
        $lastStudent = \App\Models\StudentRecord::whereYear('created_at', $year)->latest()->first();
        if ($lastStudent && $lastStudent->adm_no && preg_match('/STM-\d{4}-(\d{4})/', $lastStudent->adm_no, $m)) {
            $sequence = intval($m[1]) + 1;
        } else {
            $sequence = 1;
        }
        $adm_no = 'STM-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

        $data['username'] = $adm_no;

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath('student').$data['code'], $f['name']);
            $data['photo'] = asset('storage/' . $f['path']);
        }

        $user = $this->user->create($data); // Create User

        $sr['adm_no'] = $adm_no;
        $sr['user_id'] = $user->id;
        $sr['session'] = Qs::getSetting('current_session');

        $this->student->createRecord($sr); // Create Student
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
        $headers = ['name','gender','email','phone','dob','address','class_name','section_name','year_admitted','religion'];
        $example = ['Abebe Kebede','Male','abebe@email.com','0911234567','2010-05-12','Addis Ababa','Grade 1','A', date('Y'),'Ethiopian Orthodox'];

        $csv = implode(',', $headers)."\n".implode(',', $example)."\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_bulk_template.csv"',
        ]);
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

        while (($line = fgetcsv($handle)) !== false) {
            $row++;
            $data = array_combine($headers, array_map('trim', $line));

            // Resolve class
            $class = \App\Models\MyClass::where('name', $data['class_name'] ?? '')->first();
            if (!$class) { $errors[] = "Row {$row}: Class '{$data['class_name']}' not found."; continue; }

            // Resolve section
            $sectionName = $data['section_name'] ?? '';
            $section = \App\Models\Section::where('my_class_id', $class->id)
                ->where('name', $sectionName)->first();
            if (!$section) {
                // fallback to default_section_id if provided
                $section = $req->default_section_id
                    ? \App\Models\Section::find($req->default_section_id)
                    : \App\Models\Section::where('my_class_id', $class->id)->first();
            }
            if (!$section) { $errors[] = "Row {$row}: Section not found for class '{$data['class_name']}'."; continue; }

            // Skip duplicate email
            if (!empty($data['email']) && \App\User::where('email', $data['email'])->exists()) {
                $errors[] = "Row {$row}: Email '{$data['email']}' already exists — skipped."; continue;
            }

            // Auto-generate admission number
            $year    = date('Y');
            $last    = \App\Models\StudentRecord::whereYear('created_at', $year)->latest()->first();
            $seq     = 1;
            if ($last && $last->adm_no && preg_match('/STM-\d{4}-(\d{4})/', $last->adm_no, $m)) {
                $seq = intval($m[1]) + 1;
            }
            $adm_no = 'STM-'.$year.'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);

            $code = strtoupper(Str::random(10));

            $user = $this->user->create([
                'name'       => ucwords($data['name'] ?? ''),
                'email'      => $data['email'] ?: null,
                'phone'      => $data['phone'] ?? null,
                'dob'        => $data['dob'] ?? null,
                'gender'     => $data['gender'] ?? 'Male',
                'address'    => $data['address'] ?? 'N/A',
                'user_type'  => 'student',
                'code'       => $code,
                'username'   => $adm_no,
                'password'   => Hash::make('student'),
                'photo'      => Qs::getDefaultUserImage(),
            ]);

            $this->student->createRecord([
                'user_id'      => $user->id,
                'my_class_id'  => $class->id,
                'section_id'   => $section->id,
                'adm_no'       => $adm_no,
                'year_admitted'=> $data['year_admitted'] ?? date('Y'),
                'religion'     => $data['religion'] ?? null,
                'session'      => Qs::getSetting('current_session'),
            ]);

            $imported++;
        }

        fclose($handle);

        AuditLog::log('bulk_import', 'students', "Bulk admitted {$imported} student(s).");

        $msg = "{$imported} student(s) imported successfully.";
        if ($errors) $msg .= ' '.count($errors).' row(s) skipped.';

        return response()->json(['ok' => $imported > 0, 'msg' => $msg, 'errors' => $errors]);
    }

    public function destroy($st_id)
    {
        $st_id = Qs::decodeHash($st_id);
        if(!$st_id){return Qs::goWithDanger();}

        $sr = $this->student->getRecord(['user_id' => $st_id])->first();
        $path = Qs::getUploadPath('student').$sr->user->code;
        Storage::exists($path) ? Storage::deleteDirectory($path) : false;
        $this->user->delete($sr->user->id);

        return back()->with('flash_success', __('msg.del_ok'));
    }

}
