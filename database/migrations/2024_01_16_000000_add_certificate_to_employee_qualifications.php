<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCertificateToEmployeeQualifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_qualifications', function (Blueprint $table) {
            $table->string('certificate_path')->nullable()->after('graduation_year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_qualifications', function (Blueprint $table) {
            $table->dropColumn('certificate_path');
        });
    }
}
