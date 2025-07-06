<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalLessonUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'digital_lesson_id',
        'user_id',
        'unlocked_at'
    ];

    protected $casts = [
        'unlocked_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function digitalLesson()
    {
        return $this->belongsTo(DigitalLesson::class);
    }
}
