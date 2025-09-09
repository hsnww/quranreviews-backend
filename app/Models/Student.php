<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{

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
