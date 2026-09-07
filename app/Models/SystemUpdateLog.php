<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemUpdateLog extends Model
{
    protected $fillable = ['from_version', 'to_version', 'status', 'notes', 'ip_address', 'performed_by'];
}
