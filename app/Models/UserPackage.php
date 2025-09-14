<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'package_id',
        'lessons_remaining',
        'purchased_at'
    ];

    protected $casts = [
        'purchased_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function lessonUser()
    {
        return $this->hasMany(LessonUser::class, 'user_package_id');
    }

    public function scopeActive($query)
    {
        return $query->where('lessons_remaining', '>', 0);
    }

    public function scopeExpired($query)
    {
        return $query->where('lessons_remaining', '<=', 0);
    }

}
