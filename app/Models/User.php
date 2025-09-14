<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Propaganistas\LaravelPhone\PhoneNumber;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'birth_date'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'phone' => 'string'
    ];

    public function lessonUsers()
    {
        return $this->hasMany(LessonUser::class);
    }

    public function digitalLessons()
    {
        return $this->belongsToMany(DigitalLesson::class)
            ->withPivot(['unlocked_at', 'deleted_at'])
            ->withTimestamps()
            ->using(DigitalLessonUser::class);
    }

    public function packages()
    {
        return $this->hasMany(UserPackage::class);
    }

    public function weeklyAvailabilities()
    {
        return $this->hasMany(WeeklyAvailability::class);
    }

    public function operatedLessons()
    {
        return $this->hasMany(Lesson::class, 'operator_id');
    }

    /* public function getLessonsAttribute()
    {
        return $this->lessonUsers->map->lesson;
    } */

    public function lessonsAsClient()
    {
        return $this->belongsToMany(\App\Models\Lesson::class, 'lesson_users')
            ->withPivot([
                'attended',
                'added_by_user_id',
                'paid',
                'paid_to_user_id',
                'user_package_id',
                'counted',
                'deleted_at',
            ])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getRemainingPackageLessonsAttribute(): int
    {
        return (int) $this->packages()
            ->active()
            ->sum('lessons_remaining');
    }

    public function scopeClients($query)
    {
        return $query->role('cliente');
    }

    public function scopeOperators($query)
    {
        return $query->role('operatore');
    }

    public function scopeAdmin($query)
    {
        return $query->role('admin');
    }

    // app/Models/User.php

    // app/Models/User.php
    public function setPhoneAttribute($value)
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            $this->attributes['phone'] = null;
            return;
        }

        // Usa l'helper del pacchetto: normalizza in E.164 o ritorna null se invalido
        $e164 = phone($raw, 'IT', 'E164'); // ← helper di laravel-phone
        $this->attributes['phone'] = $e164 ?: null;
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

}
