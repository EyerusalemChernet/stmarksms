<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePromotionRulesTable extends Migration
{
    public function up()
    {
        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 191);
            $table->enum('rule_type', [
                'min_overall_average',
                'core_subject_min_score',
                'max_failed_subjects',
                'min_attendance_rate',
                'fee_clearance_required',
                'discipline_restriction',
                'conditional_promotion',
            ]);
            $table->enum('condition_operator', ['gte','lte','gt','lt','eq'])->nullable();
            $table->decimal('threshold_value', 8, 2)->nullable();
            $table->enum('scope_type', ['school','class','department','year'])->default('school');
            $table->unsignedInteger('scope_class_id')->nullable();
            $table->unsignedBigInteger('scope_department_id')->nullable();
            $table->string('scope_year', 20)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('description')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->index(['is_active', 'scope_type'], 'idx_active_scope');
            $table->foreign('scope_class_id')->references('id')->on('my_classes')->onDelete('set null');
            $table->foreign('scope_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Seed the 7 default rules
        $now = now();
        DB::table('promotion_rules')->insert([
            ['name'=>'Minimum Overall Average','rule_type'=>'min_overall_average','condition_operator'=>'gte','threshold_value'=>50,'scope_type'=>'school','scope_class_id'=>null,'scope_department_id'=>null,'scope_year'=>null,'is_active'=>1,'description'=>'Student must achieve an overall yearly average of at least 50% to be promoted.','created_by'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Core Subject Minimum Score','rule_type'=>'core_subject_min_score','condition_operator'=>'gte','threshold_value'=>50,'scope_type'=>'school','scope_class_id'=>null,'scope_department_id'=>null,'scope_year'=>null,'is_active'=>0,'description'=>'All core subjects (Math, English, Science) must be passed with at least 50%.','created_by'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Maximum Failed Subjects','rule_type'=>'max_failed_subjects','condition_operator'=>'lte','threshold_value'=>2,'scope_type'=>'school','scope_class_id'=>null,'scope_department_id'=>null,'scope_year'=>null,'is_active'=>0,'description'=>'Student may fail at most 2 subjects and still be promoted.','created_by'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Minimum Attendance Rate','rule_type'=>'min_attendance_rate','condition_operator'=>'gte','threshold_value'=>75,'scope_type'=>'school','scope_class_id'=>null,'scope_department_id'=>null,'scope_year'=>null,'is_active'=>0,'description'=>'Student must have attended at least 75% of school days.','created_by'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Fee Clearance Required','rule_type'=>'fee_clearance_required','condition_operator'=>null,'threshold_value'=>null,'scope_type'=>'school','scope_class_id'=>null,'scope_department_id'=>null,'scope_year'=>null,'is_active'=>0,'description'=>'Student must have no outstanding unpaid fees before promotion.','created_by'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Discipline Restriction','rule_type'=>'discipline_restriction','condition_operator'=>null,'threshold_value'=>null,'scope_type'=>'school','scope_class_id'=>null,'scope_department_id'=>null,'scope_year'=>null,'is_active'=>0,'description'=>'Students with active disciplinary records may be blocked from promotion.','created_by'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Conditional Promotion','rule_type'=>'conditional_promotion','condition_operator'=>'gte','threshold_value'=>40,'scope_type'=>'school','scope_class_id'=>null,'scope_department_id'=>null,'scope_year'=>null,'is_active'=>0,'description'=>'Students with average between 40–49 may be conditionally promoted pending remedial exam.','created_by'=>1,'created_at'=>$now,'updated_at'=>$now],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('promotion_rules');
    }
}
