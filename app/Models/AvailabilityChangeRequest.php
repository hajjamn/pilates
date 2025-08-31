<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AvailabilityChangeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'operator_id',
        'status',
        'effective_from',
        'payload',
        'reviewed_by',
        'reviewed_at',
        'reason',
        'applied_at',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'payload' => 'array',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id')->withTrashed();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    public function scopeRejected($q)
    {
        return $q->where('status', 'rejected');
    }

    public function scopeForOperator($q, $operatorId)
    {
        return $q->where('operator_id', $operatorId);
    }
}
