<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesTitleCaseFields;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoogleCompleteProfileRequest extends FormRequest
{
    use NormalizesTitleCaseFields;

    /**
     * @var list<string>
     */
    protected array $titleCase = [
        'first_name',
        'last_name',
        'address',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->profile_completed;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $before = (new Carbon)->subYears(16)->format('Y-m-d');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'nic' => [
                'required',
                'string',
                'max:16',
                Rule::unique(User::class)->ignore($this->user()->id)->whereNull('deleted_at'),
            ],
            'contact_number' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:'.$before],
            'address' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'You must be 16 years or older to register.',
        ];
    }
}
