<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalLesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'video_url',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'unlocked_at',
                'deleted_at'
            ])
            ->withTimestamps()
            ->using(DigitalLessonUser::class);
    }
}
