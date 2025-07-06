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

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'attended',
                'added_by_user_id',
                'paid',
                'paid_to_user_id',
                'user_package_id',
                'counted',
                'created_at',
                'updated_at',
                'deleted_at'
            ])
            ->withTimestamps()
            ->using(LessonUser::class);
    }
}
