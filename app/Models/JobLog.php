<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_name',
        'job_type',
        'status',
        'output',
        'error_message',
        'started_at',
        'finished_at',
        'duration',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Scope per job di tipo queue
     */
    public function scopeQueueJobs($query)
    {
        return $query->where('job_type', 'queue');
    }

    /**
     * Scope per job di tipo cron
     */
    public function scopeCronJobs($query)
    {
        return $query->where('job_type', 'cron');
    }

    /**
     * Scope per job falliti
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope per job in esecuzione
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope per job completati con successo
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Calcola la durata se non è impostata
     */
    public function getDurationAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }

        if ($this->started_at && $this->finished_at) {
            return $this->started_at->diffInSeconds($this->finished_at);
        }

        return null;
    }
}
