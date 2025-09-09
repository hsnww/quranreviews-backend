<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewPlan extends Model
{
    protected $fillable = [
        'student_id',
        'day_number',
        'from_verse_id',
        'to_verse_id',
        'type',
    ];

}
