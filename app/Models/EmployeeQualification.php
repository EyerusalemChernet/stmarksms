<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeQualification extends Model
{
    protected $fillable = [
        'employee_id',
        'degree',
        'field_of_study',
        'institution',
        'graduation_year',
        'certificate_path',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the full URL to the certificate file
     */
    public function getCertificateUrl()
    {
        return $this->certificate_path ? asset('storage/' . $this->certificate_path) : null;
    }

    /**
     * Get the file name from the path
     */
    public function getCertificateFileName()
    {
        return $this->certificate_path ? basename($this->certificate_path) : null;
    }
}

