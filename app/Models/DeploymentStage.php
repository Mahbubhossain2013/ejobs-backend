<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentStage extends Model
{
    protected $fillable = [
        'deployment_id',
        'stage_name',
        'stage_label',
        'status',
        'order',
        'started_at',
        'completed_at',
        'deadline',
        'document_path',
        'remarks',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'date',
        'completed_at' => 'date',
        'deadline' => 'date',
        'metadata' => 'array',
    ];

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
