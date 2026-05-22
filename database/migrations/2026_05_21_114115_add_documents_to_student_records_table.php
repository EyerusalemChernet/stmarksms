<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentsToStudentRecordsTable extends Migration
{
    public function up()
    {
        Schema::table('student_records', function (Blueprint $table) {
            // Uploaded birth certificate or student ID document
            $table->string('birth_cert_path')->nullable()->after('religion');
            // Original filename for display
            $table->string('birth_cert_name')->nullable()->after('birth_cert_path');
        });
    }

    public function down()
    {
        Schema::table('student_records', function (Blueprint $table) {
            $table->dropColumn(['birth_cert_path', 'birth_cert_name']);
        });
    }
}
