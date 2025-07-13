<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'birth_date'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
    ];

    // Reserved lessons
    public function lessonUsers()
    {
        return $this->hasMany(LessonUser::class);
    }

    // Bought digital lessons
    public function digitalLessons()
    {
        return $this->belongsToMany(DigitalLesson::class)
            ->withPivot(['unlocked_at', 'deleted_at'])
            ->withTimestamps()
            ->using(DigitalLessonUser::class);
    }

    // Bought packages
    public function packages()
    {
        return $this->hasMany(UserPackage::class);
    }

    // Weekly availability (only for operators)
    public function weeklyAvailabilities()
    {
        return $this->hasMany(WeeklyAvailability::class);
    }

    // Operated lessons (only operators)
    public function operatedLessons()
    {
        return $this->hasMany(Lesson::class, 'operator_id');
    }

    public function getLessonsAttribute()
    {
        return $this->lessonUsers->map->lesson;
    }

    /**
     * === Accessors / Scopes (future ideas) ===
     */

    // Esempio di accessor futuro: nome completo
    // public function getFullNameAttribute()
    // {
    //     return "{$this->first_name} {$this->last_name}";
    // }

    // Scope per filtrare solo clienti
    // public function scopeClients($query)
    // {
    //     return $query->role('cliente');
    // }
}
