<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SecureCredential extends Model
{
    protected $fillable = [
        'job_id',
        'key_name',
        'username',
        'password',
        'notes',
        'expires_at',
        'is_deleted',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    // Dynamic Encrypted Username Accessor & Mutator
    public function getUsernameAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = $value ? Crypt::encryptString($value) : null;
    }

    // Dynamic Encrypted Password Accessor & Mutator
    public function getPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    // Dynamic Encrypted Notes Accessor & Mutator
    public function getNotesAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setNotesAttribute($value)
    {
        $this->attributes['notes'] = $value ? Crypt::encryptString($value) : null;
    }
}
