<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attended',
        'added_by_user_id',
        'paid',
        'paid_to_user_id',
        'user_package_id',
        'counted',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'counted' => 'boolean'
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    public function paidTo()
    {
        return $this->belongsTo(User::class, 'paid_to_user_id');
    }

    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class, 'user_package_id');
    }
}
