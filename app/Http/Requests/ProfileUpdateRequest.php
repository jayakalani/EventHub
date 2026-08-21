<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesTitleCaseFields;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $before = (new Carbon)->subYears($this->minimumAge())->format('Y-m-d');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'nic' => ['required', 'string', 'max:20', Rule::unique(User::class)->ignore($this->user()->id)],
            'contact_number' => ['required', 'string', 'max:15'],
            'date_of_birth' => ['required', 'date', 'before:'.$before],
            'address' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'You must be '.$this->minimumAge().' years or older.',
        ];
    }

    /**
     * Organizers, admins, and CROs must be 18+. Attendees stay at 16+.
     */
    private function minimumAge(): int
    {
        $user = $this->user();

        if ($user->isOrganizer() || $user->isAdmin() || $user->isCro()) {
            return 18;
        }

        return 16;
    }
}
