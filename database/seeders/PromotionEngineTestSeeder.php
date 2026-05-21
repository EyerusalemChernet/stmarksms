<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\Mark;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * PromotionEngineTestSeeder
 *
 * Creates a complete end-to-end test dataset for the Promotion Engine:
 *
 *   Phase 1 — Admission:     10 test students in Primary 1 / Section Silver
 *   Phase 2 — Academic Setup: 2 exams for session 2026-2027 (Term 1 + Term 2)
 *   Phase 3 — Assessments:   Marks entered for both exams, all subjects
 *   Phase 4 — Results:       ExamRecord averages computed per student per exam
 *   Phase 5 — Promotion:     Ready for Promotion Engine to evaluate
 *
 * Student profiles (10 students, different performance levels):
 *
 *   1. Excellent Student      — avg ~90, all subjects pass, attendance 95%
 *   2. Good Student           — avg ~72, all subjects pass, attendance 88%
 *   3. Average Student        — avg ~55, all subjects pass, attendance 80%
 *   4. Borderline Student     — avg ~51, all subjects pass, attendance 76%
 *   5. Failing Math Student   — avg ~52, Math fails (38), English passes
 *   6. Below Average Student  — avg ~42, multiple subjects fail
 *   7. Missing Marks Student  — marks only for Term 1, Term 2 missing
 *   8. Low Attendance Student — avg ~60, attendance only 55%
 *   9. Unpaid Fees Student    — avg ~65, has unpaid fee record
 *  10. Repeat Student         — avg ~30, fails most subjects
 *
 * Run with: php artisan db:seed --class=PromotionEngineTestSeeder
 * Reset with: php artisan db:seed --class=PromotionEngineTestSeeder (idempotent — skips existing)
 */
class PromotionEngineTestSeeder extends Seeder
{
    // Target class: Primary 1 (id=4), Section: Silver (id=6)
    private int $classId   = 4;
    private int $sectionId = 6;
    private string $session = '2026-2027';

    // Existing subjects for Primary 1
    private int $englishSubjectId = 7;
    private int $mathSubjectId    = 8;

    // Location defaults
    private int $stateId = 1;
    private int $lgaId   = 1;
    private int $nalId   = 60; // Ethiopian

    public function run(): void
    {
        $this->command->info('🎓 Seeding Promotion Engine test data...');

        // ── Phase 2: Ensure 2 exams exist for the session ──────────────────
        $exam1 = $this->ensureExam(1, 'Term 1 Examination 2026-2027', '2026-09-01', '2026-12-15');
        $exam2 = $this->ensureExam(2, 'Term 2 Examination 2026-2027', '2027-01-15', '2027-05-30');

        $this->command->info("  ✓ Exams: [{$exam1->id}] {$exam1->name}, [{$exam2->id}] {$exam2->name}");

        // ── Phase 1: Create 10 test students ───────────────────────────────
        $profiles = $this->getStudentProfiles();

        foreach ($profiles as $profile) {
            $user = $this->ensureStudent($profile);
            $sr   = $this->ensureStudentRecord($user);

            $this->command->info("  ✓ Student: {$user->name} (ID: {$user->id})");

            // ── Phase 3 & 4: Seed marks + exam records ─────────────────────
            if ($profile['has_term1_marks']) {
                $this->seedMarks($user->id, $exam1, $profile['term1_english'], $profile['term1_math']);
                $this->seedExamRecord($user->id, $exam1, $sr, $profile['term1_english'], $profile['term1_math']);
            }

            if ($profile['has_term2_marks']) {
                $this->seedMarks($user->id, $exam2, $profile['term2_english'], $profile['term2_math']);
                $this->seedExamRecord($user->id, $exam2, $sr, $profile['term2_english'], $profile['term2_math']);
            }

            // ── Attendance records ─────────────────────────────────────────
            $this->seedAttendance($user->id, $profile['attendance_pct']);

            // ── Fee records ────────────────────────────────────────────────
            if ($profile['has_unpaid_fees']) {
                $this->seedUnpaidFee($user->id);
            }
        }

        $this->command->info('');
        $this->command->info('✅ Promotion Engine test data seeded successfully!');
        $this->command->info('');
        $this->command->info('📊 Student Summary:');
        $this->command->table(
            ['#', 'Name', 'T1 Eng', 'T1 Math', 'T2 Eng', 'T2 Math', 'Avg', 'Attendance', 'Fees', 'Expected Outcome'],
            collect($profiles)->map(fn($p, $i) => [
                $i + 1,
                $p['name'],
                $p['has_term1_marks'] ? $p['term1_english'] : '—',
                $p['has_term1_marks'] ? $p['term1_math']    : '—',
                $p['has_term2_marks'] ? $p['term2_english'] : '—',
                $p['has_term2_marks'] ? $p['term2_math']    : '—',
                $p['has_term1_marks'] && $p['has_term2_marks']
                    ? round((($p['term1_english'] + $p['term1_math']) / 2 + ($p['term2_english'] + $p['term2_math']) / 2) / 2, 1)
                    : '—',
                $p['attendance_pct'] . '%',
                $p['has_unpaid_fees'] ? '❌ Unpaid' : '✓ Clear',
                $p['expected_outcome'],
            ])->toArray()
        );
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('  1. Go to Promotion Engine → Dashboard');
        $this->command->info('  2. Initiate a Promotion Run for session ' . $this->session);
        $this->command->info('  3. Review the Preview page — verify each student\'s outcome');
        $this->command->info('  4. Test overrides, bulk actions, and finalization');
    }

    // ── Student profiles ────────────────────────────────────────────────────

    private function getStudentProfiles(): array
    {
        return [
            [
                'name'             => 'TEST Excellent Student',
                'email'            => 'test.excellent@test.sms',
                'term1_english'    => 88,
                'term1_math'       => 92,
                'term2_english'    => 85,
                'term2_math'       => 94,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 95,
                'has_unpaid_fees'  => false,
                'expected_outcome' => '✅ Promoted',
            ],
            [
                'name'             => 'TEST Good Student',
                'email'            => 'test.good@test.sms',
                'term1_english'    => 70,
                'term1_math'       => 75,
                'term2_english'    => 68,
                'term2_math'       => 74,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 88,
                'has_unpaid_fees'  => false,
                'expected_outcome' => '✅ Promoted',
            ],
            [
                'name'             => 'TEST Average Student',
                'email'            => 'test.average@test.sms',
                'term1_english'    => 55,
                'term1_math'       => 58,
                'term2_english'    => 52,
                'term2_math'       => 56,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 80,
                'has_unpaid_fees'  => false,
                'expected_outcome' => '✅ Promoted',
            ],
            [
                'name'             => 'TEST Borderline Student',
                'email'            => 'test.borderline@test.sms',
                'term1_english'    => 52,
                'term1_math'       => 51,
                'term2_english'    => 50,
                'term2_math'       => 53,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 76,
                'has_unpaid_fees'  => false,
                'expected_outcome' => '✅ Promoted (just passes)',
            ],
            [
                'name'             => 'TEST Failing Math Student',
                'email'            => 'test.failmath@test.sms',
                'term1_english'    => 65,
                'term1_math'       => 35,
                'term2_english'    => 62,
                'term2_math'       => 38,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 82,
                'has_unpaid_fees'  => false,
                'expected_outcome' => '⚠️ Conditional (Math fails core rule)',
            ],
            [
                'name'             => 'TEST Below Average Student',
                'email'            => 'test.below@test.sms',
                'term1_english'    => 40,
                'term1_math'       => 38,
                'term2_english'    => 42,
                'term2_math'       => 36,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 72,
                'has_unpaid_fees'  => false,
                'expected_outcome' => '🔴 Repeated (avg < 50)',
            ],
            [
                'name'             => 'TEST Missing Marks Student',
                'email'            => 'test.missing@test.sms',
                'term1_english'    => 60,
                'term1_math'       => 65,
                'term2_english'    => 0,
                'term2_math'       => 0,
                'has_term1_marks'  => true,
                'has_term2_marks'  => false, // Term 2 marks NOT entered
                'attendance_pct'   => 78,
                'has_unpaid_fees'  => false,
                'expected_outcome' => '⏳ Pending Review (incomplete marks)',
            ],
            [
                'name'             => 'TEST Low Attendance Student',
                'email'            => 'test.lowatt@test.sms',
                'term1_english'    => 62,
                'term1_math'       => 58,
                'term2_english'    => 60,
                'term2_math'       => 55,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 55, // Below 75% threshold
                'has_unpaid_fees'  => false,
                'expected_outcome' => '🔴 Repeated (attendance < 75%)',
            ],
            [
                'name'             => 'TEST Unpaid Fees Student',
                'email'            => 'test.unpaid@test.sms',
                'term1_english'    => 68,
                'term1_math'       => 62,
                'term2_english'    => 65,
                'term2_math'       => 60,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 85,
                'has_unpaid_fees'  => true, // Has unpaid fee
                'expected_outcome' => '🔴 Repeated (unpaid fees)',
            ],
            [
                'name'             => 'TEST Repeat Student',
                'email'            => 'test.repeat@test.sms',
                'term1_english'    => 28,
                'term1_math'       => 25,
                'term2_english'    => 32,
                'term2_math'       => 30,
                'has_term1_marks'  => true,
                'has_term2_marks'  => true,
                'attendance_pct'   => 65,
                'has_unpaid_fees'  => false,
                'expected_outcome' => '🔴 Repeated (avg ~29, all fail)',
            ],
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function ensureExam(int $term, string $name, string $start, string $end): Exam
    {
        return Exam::firstOrCreate(
            ['term' => $term, 'year' => $this->session],
            [
                'name'        => $name,
                'start_date'  => $start,
                'end_date'    => $end,
                'status'      => 'completed',
                'created_by'  => 1,
            ]
        );
    }

    private function ensureStudent(array $profile): User
    {
        return User::firstOrCreate(
            ['email' => $profile['email']],
            [
                'name'      => $profile['name'],
                'username'  => Str::slug($profile['name'], '.'),
                'password'  => Hash::make('test123'),
                'user_type' => 'student',
                'gender'    => 'Male',
                'dob'       => '2015-06-15',
                'address'   => 'Addis Ababa, Ethiopia',
                'phone'     => '0911000000',
                'nal_id'    => $this->nalId,
                'state_id'  => $this->stateId,
                'lga_id'    => $this->lgaId,
                'code'      => strtoupper(Str::random(10)),
                'photo'     => asset('global_assets/images/user.png'),
            ]
        );
    }

    private function ensureStudentRecord(User $user): StudentRecord
    {
        return StudentRecord::firstOrCreate(
            ['user_id' => $user->id, 'session' => $this->session],
            [
                'my_class_id'  => $this->classId,
                'section_id'   => $this->sectionId,
                'adm_no'       => 'TEST-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'year_admitted' => 2026,
                'grad'         => 0,
                'age'          => 10,
            ]
        );
    }

    private function seedMarks(int $studentId, Exam $exam, int $englishScore, int $mathScore): void
    {
        $texField = 'tex' . $exam->term;

        // English
        $mk = Mark::firstOrCreate(
            [
                'student_id'  => $studentId,
                'exam_id'     => $exam->id,
                'subject_id'  => $this->englishSubjectId,
                'my_class_id' => $this->classId,
                'section_id'  => $this->sectionId,
                'year'        => $this->session,
            ],
            [
                't1'       => (int) round($englishScore * 0.30),
                't2'       => (int) round($englishScore * 0.20),
                'tca'      => (int) round($englishScore * 0.50),
                'exm'      => (int) round($englishScore * 0.50),
                $texField  => $englishScore,
                'cum_ave'  => $englishScore,
                'sub_pos'  => 1,
            ]
        );

        // Math
        Mark::firstOrCreate(
            [
                'student_id'  => $studentId,
                'exam_id'     => $exam->id,
                'subject_id'  => $this->mathSubjectId,
                'my_class_id' => $this->classId,
                'section_id'  => $this->sectionId,
                'year'        => $this->session,
            ],
            [
                't1'       => (int) round($mathScore * 0.30),
                't2'       => (int) round($mathScore * 0.20),
                'tca'      => (int) round($mathScore * 0.50),
                'exm'      => (int) round($mathScore * 0.50),
                $texField  => $mathScore,
                'cum_ave'  => $mathScore,
                'sub_pos'  => 1,
            ]
        );
    }

    private function seedExamRecord(int $studentId, Exam $exam, StudentRecord $sr, int $english, int $math): void
    {
        $avg   = round(($english + $math) / 2, 2);
        $total = $english + $math;

        ExamRecord::firstOrCreate(
            [
                'student_id'  => $studentId,
                'exam_id'     => $exam->id,
                'my_class_id' => $this->classId,
                'section_id'  => $this->sectionId,
                'year'        => $this->session,
            ],
            [
                'total'     => $total,
                'ave'       => $avg,
                'pos'       => 1,
                'class_ave' => $avg,
            ]
        );
    }

    private function seedAttendance(int $studentId, int $pct): void
    {
        // Check if attendance_sessions table exists and has records
        if (!DB::getSchemaBuilder()->hasTable('attendance_sessions')) {
            return;
        }

        $sessions = DB::table('attendance_sessions')
            ->where('year', $this->session)
            ->pluck('id');

        if ($sessions->isEmpty()) {
            return;
        }

        foreach ($sessions as $sessionId) {
            $present = (rand(1, 100) <= $pct) ? 'present' : 'absent';
            DB::table('attendance_records')->updateOrInsert(
                ['student_id' => $studentId, 'session_id' => $sessionId],
                ['status' => $present, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedUnpaidFee(int $studentId): void
    {
        // Check if payment_records table exists
        if (!DB::getSchemaBuilder()->hasTable('payment_records')) {
            return;
        }

        // Create a payment record in the payments table first (payment_id FK)
        $paymentId = DB::table('payments')->insertGetId([
            'title'        => 'TEST School Fee',
            'amount'       => 5000,
            'ref_no'       => 'TEST-FEE-' . $studentId,
            'method'       => 'cash',
            'my_class_id'  => $this->classId,
            'description'  => 'Test unpaid fee for promotion engine testing',
            'year'         => $this->session,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('payment_records')->updateOrInsert(
            ['student_id' => $studentId, 'payment_id' => $paymentId],
            [
                'paid'       => 0,
                'amt_paid'   => 0,
                'balance'    => 5000,
                'year'       => $this->session,
                'ref_no'     => 'TEST-UNPAID-' . $studentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
