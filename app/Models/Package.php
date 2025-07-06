<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'total_lessons',
        'price'
    ];

    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }
}
