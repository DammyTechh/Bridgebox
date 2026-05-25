<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'section_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'section_id');
    }
}
