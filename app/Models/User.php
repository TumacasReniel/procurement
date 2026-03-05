<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable  implements MustVerifyEmail
{
    use HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'username',
        'email',
        'kradworkz',
        'password',
        'code',
        'is_active',
        'is_locked',
        'must_change',
        'email_verified_at',
        'password_changed_at',
        'two_factor_confirmed_at',
        'two_factor_secret',
        'two_factor_recovery_codes'
    ];

    protected $hidden = [
        'kradworkz',
        'password',
        'remember_token',
        'code',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tags()
    {
        return $this->hasMany('App\Models\RequestTag', 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany('App\Models\UserAddress', 'user_id');
    }

    public function dtrs()
    {
        return $this->hasMany('App\Models\Dtr', 'user_id');
    }

    public function payrolls()
    {
        return $this->hasMany('App\Models\Payroll', 'user_id');
    }

    public function profile()
    {
        return $this->hasOne('App\Models\UserProfile', 'user_id');
    }

    public function organization()
    {
        return $this->hasOne('App\Models\UserOrganization', 'user_id');
    }

    public function information()
    {
        return $this->hasOne('App\Models\UserInformation', 'user_id');
    }

    public function academics()
    {
        return $this->hasMany('App\Models\UserAcademic', 'user_id');
    }

    public function contracts()
    {
        return $this->hasMany('App\Models\UserContract', 'user_id');
    }

    public function credentials()
    {
        return $this->hasMany('App\Models\UserCredential', 'user_id')->orderBy('created_at','DESC');
    }

    public function deductions()
    {
        return $this->hasMany('App\Models\UserDeduction', 'user_id')->orderBy('created_at','DESC');
    }

    public function credits()
    {
        return $this->hasMany('App\Models\UserCredit', 'user_id')->orderBy('created_at','DESC');
    }

    public function folders()
    {
        return $this->hasMany('App\Models\UserFolder', 'user_id');
    }

    public function myroles()
    {
        return $this->hasMany('App\Models\UserRole', 'user_id')->orderBy('is_active','DESC');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\ListRole', 'user_roles', 'user_id', 'role_id');
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function setEmailAttribute($value)
    {
        $email = strtolower($value);
        $this->attributes['email'] = Crypt::encryptString($email);
        $this->attributes['kradworkz'] = hash('sha256', $email);
    }

    public function getEmailAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    public function getEmailVerifiedAtAttribute($value)
    {
        return $value ? date('M d, Y g:i a', strtotime($value)) : null;
    }

    public function getLastLoginAtAttribute($value)
    {
        return $value ? date('M d, Y g:i a', strtotime($value)) : null;
    }

    public function getPasswordChangedAtAttribute($value)
    {
        return $value ? date('F d, Y g:i a', strtotime($value)) : null;
    }

    public function getTwoFactorConfirmedAtAttribute($value)
    {
        return $value ? date('M d, Y g:i a', strtotime($value)) : null;
    }
    
    public function getUpdatedAtAttribute($value)
    {
        return date('M d, Y g:i a', strtotime($value));
    }

    public function getCreatedAtAttribute($value)
    {
        return date('F d, Y g:i a', strtotime($value));
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly(['username','kradworkz','is_active','must_change'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName} the user information")
        ->useLogName('User')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

    public function activities()
    {
        $this->loadMissing(['addresses','information','profile','organization']);

        $profile = $this->profile?->id;
        $organization = $this->organization?->id;
        $information = $this->information?->id;
        $addressIds = $this->addresses->pluck('id')->toArray();

        return Activity::with(['causer:id','causer.profile:user_id,firstname,lastname,middlename,suffix_id']) // 👈 eager load here
        ->where(function ($query) use ($addressIds, $organization, $information, $profile) {
            $query->where('subject_type', User::class)->where('subject_id', $this->id);
            $query->orWhere('subject_type', UserProfile::class)->where('subject_id', $profile);
            $query->orWhere('subject_type', UserInformation::class)->where('subject_id', $information);
            $query->orWhere('subject_type', UserOrganization::class)->where('subject_id', $organization);

            if ($addressIds) {
                $query->orWhere(function ($q) use ($addressIds) {
                    $q->where('subject_type', UserAddress::class)
                      ->whereIn('subject_id', $addressIds);
                });
            }
        })
        ->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    protected $appends = ['signatory'];

    public function getSignatoryAttribute()
    {
        $sig = $this->oic()->with('designationable.designation:id,name,others','designationable.assigned:id,name,others')->where('is_oic', 1)->where('is_active', 1)->first();
        if(!$sig){
            $sig = $this->signa()->with('designationable.designation:id,name,others','designationable.assigned:id,name,others')->where('is_oic', 0)->where('is_active', 1)->first();
        }
        return $sig;
    }

    public function signa()
    {
        return $this->hasOne('App\Models\OrgSignatory', 'user_id');
    }

    public function oic()
    {
        return $this->hasOne('App\Models\OrgSignatory', 'oic_id');
    }

}
