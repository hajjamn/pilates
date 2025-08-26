<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Carbon\Carbon;

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

    public function clients()
    {
        return $this->belongsToMany(\App\Models\User::class, 'lesson_users')
            ->withPivot(['deleted_at'])
            ->using(\App\Models\LessonUser::class)
            ->wherePivotNull('deleted_at');
    }

    public function scopePast($query)
    {
        return $query->where('starts_at', '<=', now());
    }

    public function scopeFuture($query)
    {
        return $query->where('starts_at', '>', now());
    }

    public function scopeCanceled($query)
    {
        return $query->where('canceled', true);
    }

    public function scopeActive($query)
    {
        return $query->where('canceled', false);
    }

    public function getUsersAttribute()
    {
        return $this->lessonUsers->map->user;
    }

    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->hasRole('admin') || $user->hasRole('cliente')) {
            return $q; // nessun limite aggiuntivo
        }

        if ($user->hasRole('operatore')) {
            return $q->where('operator_id', $user->id);
        }

        // Altri ruoli non mappati: prudente → non filtriamo (adatta se necessario)
        return $q;
    }

    public function scopeOnDay(Builder $q, $day): Builder
    {
        $date = $day instanceof Carbon ? $day->toDateString() : (string) $day;
        return $q->whereDate('starts_at', $date);
    }

    public function scopeInMonth(Builder $q, $month): Builder
    {
        $start = $month instanceof Carbon
            ? $month->copy()->startOfMonth()
            : Carbon::createFromFormat('Y-m', (string) $month)->startOfMonth();

        $end = $start->copy()->endOfMonth();

        return $q->whereBetween('starts_at', [$start, $end]);
    }

    public function scopeInRoom(Builder $q, $roomId): Builder
    {
        return $q->when($roomId, fn($qq) => $qq->where('room_id', $roomId));
    }



}
