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
        'lesson_id',
        'user_id',
        'contacted',
        'paid_at',
        'lesson_price',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'counted' => 'boolean',
        'is_active' => 'boolean',
        'contacted' => 'boolean',
        'paid_at' => 'datetime',
        'lesson_price' => 'float',
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

    public function scopeActive($q)
    {
        return $q->whereNull('deleted_at');
    }
    public function scopeForLesson($q, int $lessonId)
    {
        return $q->where('lesson_id', $lessonId);
    }
    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopePaid($q)
    {
        return $q->whereNotNull('paid_at');
    }

    public function scopeUnpaid($q)
    {
        return $q->whereNull('paid_at');
    }

    public function scopeNotCovered($q)
    {
        // prenotazioni non coperte da pacchetto
        return $q->whereNull('user_package_id');
    }

    public function scopeBetweenDate($q, $from, $to, $column = 'paid_at')
    {
        // accetta stringhe 'Y-m-d' o Carbon; usa DATE(colonna) per range giornalieri
        return $q->whereBetween(\DB::raw("DATE($column)"), [
            $from instanceof \Carbon\Carbon ? $from->toDateString() : (string) $from,
            $to instanceof \Carbon\Carbon ? $to->toDateString() : (string) $to,
        ]);
    }

    // 🔹 helper: prezzo effettivo (snapshot o default da config)
    public function getEffectiveLessonPriceAttribute()
    {
        $default = (float) config('billing.lesson_price', 0.0);
        return $this->lesson_price !== null ? (float) $this->lesson_price : $default;
    }
}
