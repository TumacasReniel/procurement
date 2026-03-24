<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    use LogsActivity;

    protected $guarded = [];
    protected $fillable = [
        'lastname', 
        'firstname',
        'middlename',
        'mobile',
        'mobile_hash',
        'avatar',
        'signature',
        'birthdate',
        'birthmonth',
        'sex_id',
        'suffix_id',
        'blood_id',
        'marital_id',
        'religion_id',
        'user_id', 
    ];
    protected $appends = ['name','fullname'];
    protected $encryptable = [
        'firstname',
        'middlename',
        'birthdate',
        'mobile'
    ];
    protected $hidden = [
        'mobile_hash'
    ];

    public function user()     { return $this->belongsTo(User::class); }
    public function sex()      { return $this->belongsTo(ListData::class, 'sex_id'); }
    public function suffix()   { return $this->belongsTo(ListData::class, 'suffix_id'); }
    public function blood()    { return $this->belongsTo(ListData::class, 'blood_id'); }
    public function marital()  { return $this->belongsTo(ListData::class, 'marital_id'); }
    public function religion() { return $this->belongsTo(ListData::class, 'religion_id'); }

    public function getFullnameAttribute()
    {
        $middleInitial = $this->middlename ? strtoupper($this->middlename[0]) . '.' : '';
        $name = trim("{$this->firstname} {$middleInitial} {$this->lastname}");
        if ($this->suffix?->name) {
            $name .= ', ' . $this->suffix->name;
        }
        return $name;
    }

    public function getNameAttribute()
    {
        $middleInitial = $this->middlename ? strtoupper($this->middlename[0]) . '.' : '';
        $parts = [trim($this->lastname) . ',', trim($this->firstname), $middleInitial, $this->suffix?->name];
        return implode(' ', array_filter($parts));
    }


    public function setAttribute($key, $value)
    {
        if (in_array($key, ['firstname', 'middlename', 'lastname','mobile']) && !is_null($value)) {
            $value = ucwords(strtolower($value));
        }

        if (in_array($key, $this->encryptable) && !is_null($value) && $value !== '') {
            $value = Crypt::encryptString($value);
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        if (in_array($key, $this->encryptable) && !is_null($value)) {
            try {
                return ucwords(Crypt::decryptString($value));
            } catch (\Throwable $e) {
                return $value;
            }
        }
        return $value;
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if (! empty($model->mobile)) {
                $plainMobile = $model->mobile;
                $normalized = preg_replace('/\D+/', '', $plainMobile);
                $model->mobile_hash = hash('sha256', $normalized);
            }
        });
    }

    public function getAvatarAttribute($value)
    {
        $defaultAvatar = asset('images/avatars/avatar.jpg');

        if (empty($value)) {
            return $defaultAvatar;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        try {
            if (Storage::disk('s3')->exists($value)) {
                return Storage::disk('s3')->url($value);
            }
        } catch (\Throwable $e) {
            // Fall back to local/public avatars when S3 availability checks fail.
        }

        if (Storage::disk('public')->exists($value)) {
            return Storage::disk('public')->url($value);
        }

        if (Storage::disk('public')->exists('images/avatars/' . $value)) {
            return asset('storage/images/avatars/' . $value);
        }

        if (file_exists(public_path('images/avatars/' . $value))) {
            return asset('images/avatars/' . $value);
        }

        return $defaultAvatar;
    }

    protected static $recordEvents = ['updated'];
    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly([
            'firstname',
            'lastname',
            'middlename',
            'suffix_id',
            'sex_id',
            'birthdate',
            'birthmonth',
            'mobile',
            'mobile_hash',
            'signature',
            'avatar'
        ])
        ->setDescriptionForEvent(fn(string $eventName) => "$eventName the profile information")
        ->useLogName('User Profile')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }
}
