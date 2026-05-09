<?php

namespace App\Services;

use App\Models\QuranVerse;
use Illuminate\Validation\ValidationException;

class RecitationSegmentValidator
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public function validate(array $payload): void
    {
        $startSurah = (int) $payload['start_surah'];
        $startAyah = (int) $payload['start_ayah'];
        $endSurah = (int) $payload['end_surah'];
        $endAyah = (int) $payload['end_ayah'];

        if ($endSurah < $startSurah || ($endSurah === $startSurah && $endAyah < $startAyah)) {
            throw ValidationException::withMessages([
                'end_ayah' => ['نهاية المقطع لا يمكن أن تسبق بدايته.'],
            ]);
        }

        if ($startSurah !== $endSurah && $endSurah !== $startSurah + 1) {
            throw ValidationException::withMessages([
                'end_surah' => ['المقطع يجب أن يكون داخل سورة واحدة أو بين سورتين متتاليتين فقط.'],
            ]);
        }

        $startExists = QuranVerse::query()
            ->where('sora', $startSurah)
            ->where('ayah', $startAyah)
            ->exists();
        $endExists = QuranVerse::query()
            ->where('sora', $endSurah)
            ->where('ayah', $endAyah)
            ->exists();

        if (!$startExists) {
            throw ValidationException::withMessages([
                'start_ayah' => ['آية البداية غير موجودة.'],
            ]);
        }

        if (!$endExists) {
            throw ValidationException::withMessages([
                'end_ayah' => ['آية النهاية غير موجودة.'],
            ]);
        }
    }
}
