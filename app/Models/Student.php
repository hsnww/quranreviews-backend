<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'institution',
        'phone',
        'dob',
        'memorized_parts',
        'preferred_review_days',
        'review_quarters_per_day',
        'new_memorization_mode',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function memorization()
    {
        return $this->hasMany(StudentMemorization::class);
    }
    public function memorizedParts()
    {
        return $this->hasMany(StudentMemorization::class);
    }
    public function reviewPlans()
    {
        return $this->hasMany(ReviewPlan::class);
    }



}
