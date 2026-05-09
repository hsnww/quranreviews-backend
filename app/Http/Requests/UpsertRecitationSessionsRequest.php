<?php

namespace App\Http\Requests;

use App\Models\RecitationPlan;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertRecitationSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sessions' => ['required', 'array', 'min:1'],
            'sessions.*.date' => ['required', 'date'],
            'sessions.*.prayer_name' => ['required', 'in:fajr,dhuhr,asr,maghrib,isha'],
            'sessions.*.execution_status' => ['sometimes', 'in:scheduled,completed,skipped'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var RecitationPlan|null $plan */
            $plan = $this->route('plan');
            if ($plan === null) {
                return;
            }

            $start = Carbon::parse($plan->start_date)->startOfDay();
            $end = Carbon::parse($plan->end_date)->endOfDay();

            foreach ($this->input('sessions', []) as $index => $session) {
                $date = Carbon::parse($session['date']);
                if ($date->lt($start) || $date->gt($end)) {
                    $validator->errors()->add("sessions.{$index}.date", 'تاريخ الجلسة خارج مدى الخطة.');
                }
            }
        });
    }
}
