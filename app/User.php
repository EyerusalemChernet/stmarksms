<?php

namespace App;

use App\Models\BloodGroup;
use App\Models\Lga;
use App\Models\Nationality;
use App\Models\Position;
use App\Models\Shift;
use App\Models\StaffPayroll;
use App\Models\StaffPosition;
use App\Models\StaffRecord;
use App\Models\StaffSalary;
use App\Models\StaffShift;
use App\Models\State;
use App\Models\StudentRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username', 'email', 'phone', 'phone2', 'dob', 'gender', 'photo', 'address', 'bg_id', 'password', 'nal_id', 'state_id', 'lga_id', 'code', 'user_type', 'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function student_record()
    {
        return $this->hasOne(StudentRecord::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nal_id');
    }

    public function blood_group()
    {
        return $this->belongsTo(BloodGroup::class, 'bg_id');
    }

    public function staff()
    {
        return $this->hasMany(StaffRecord::class);
    }

    // ── HR Enhancements ──────────────────────────────────────────────────────────

    public function salaries()
    {
        return $this->hasMany(StaffSalary::class)->orderByDesc('start_date');
    }

    /** Current active salary (end_date is null) */
    public function currentSalary()
    {
        return $this->hasOne(StaffSalary::class)->whereNull('end_date')->latestOfMany('start_date');
    }

    public function staffPositions()
    {
        return $this->hasMany(StaffPosition::class)->orderByDesc('start_date');
    }

    /** Current active position */
    public function currentPosition()
    {
        return $this->hasOne(StaffPosition::class)->whereNull('end_date')->with('position')->latestOfMany('start_date');
    }

    public function staffShifts()
    {
        return $this->hasMany(StaffShift::class)->orderByDesc('start_date');
    }

    /** Current active shift */
    public function currentShift()
    {
        return $this->hasOne(StaffShift::class)->whereNull('end_date')->with('shift')->latestOfMany('start_date');
    }

    public function payrolls()
    {
        return $this->hasMany(StaffPayroll::class)->orderByDesc('month');
    }
}
