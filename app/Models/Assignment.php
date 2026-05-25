<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'description',
        'due_at',
        'max_points',
        'pass_mark',
        'retake_attempts',
        'allow_late',
        'late_mark',
        'late_due_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'late_due_at' => 'datetime',
        'allow_late' => 'boolean',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }
}
