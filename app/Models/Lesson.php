<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_id',
        'operator_id',
        'starts_at',
        'max_clients',
        'canceled'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'canceled' => 'boolean'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function lessonUsers()
    {
        return $this->hasMany(LessonUser::class);
    }

    public function scopePast($query)
    {
        return $query->where('starts_at', '<=', now());
    }

    public function scopeFuture($query)
    {
        return $query->where('starts_at', '>', now());
    }

    public function scopeNotCanceled($query)
    {
        return $query->where('canceled', false);
    }

    public function getUsersAttribute()
    {
        return $this->lessonUsers->map->user;
    }

}
