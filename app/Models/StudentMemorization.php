<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMemorization extends Model
{
    protected $fillable = [
        'student_id',
        'from_verse_id',
        'to_verse_id',
        'note',
        'type',
        'verified',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fromVerse()
    {
        return $this->belongsTo(QuranVerse::class, 'from_verse_id');
    }

    public function toVerse()
    {
        return $this->belongsTo(QuranVerse::class, 'to_verse_id');
    }
}
