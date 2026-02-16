<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'auto_detected',
        'spam_score',
        'detection_reasons',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'auto_detected' => 'boolean',
        'detection_reasons' => 'array',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending($query)
    {
        return $query->where('status', config('status.report.pending'));
    }

    public function scopeResolved($query)
    {
        return $query->where('status', config('status.report.resolved'));
    }

    public function scopeAutoDetected($query)
    {
        return $query->where('auto_detected', true);
    }

    public function scopeManual($query)
    {
        return $query->where('auto_detected', false);
    }

    public function scopeHighSpamScore($query, int $threshold = 70)
    {
        return $query->where('spam_score', '>=', $threshold);
    }
}
