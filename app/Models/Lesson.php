<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'title',
        'content',
        'file_path',
        'file_name',
        'file_type',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'lesson_id');
    }

    public function completions()
    {
        return $this->hasMany(LessonCompletion::class);
    }
}
