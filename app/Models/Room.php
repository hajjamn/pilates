<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description'
    ];

    public function weeklyAvailabilities()
    {
        return $this->hasMany(WeeklyAvailability::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function machines()
    {
        return $this->hasMany(Machine::class);
    }
}