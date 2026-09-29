<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'join_date',
        'status',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];
}