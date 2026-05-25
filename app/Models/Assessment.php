<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    public const TYPE_QUIZ = 'quiz';
    public const TYPE_EXAM = 'exam';

    protected $fillable = [
        'school_class_id',
        'subject_id',
        'topic_id',
        'type',
        'title',
        'description',
        'time_limit_minutes',
        'total_mark',
        'pass_mark',
        'retake_attempts',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class, 'assessment_id');
    }

    public function attempts()
    {
        return $this->hasMany(AssessmentAttempt::class, 'assessment_id');
    }
}
