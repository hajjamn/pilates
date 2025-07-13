<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklyAvailability extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'operator_id',
        'day_of_week',
        'start_time',
        'room_id',
        'active'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}