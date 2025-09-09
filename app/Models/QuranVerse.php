<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranVerse extends Model
{
    // app/Models/QuranVerse.php

    protected $fillable = [
        'sora', 'ayah', 'text', 'page', 'hizb', 'qrtr', 'jozo'
    ];

    public function surah()
    {
        return $this->belongsTo(Surah::class, 'sora', 'id');
    }
}
