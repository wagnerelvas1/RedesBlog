<?php

namespace App\Http\Requests\Community;

use App\Models\Community;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Deleting also demands a freshly confirmed password, enforced by the
 * `password.confirm` middleware on the route.
 */
class DeleteCommunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('community')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $community = $this->route('community');

        return [
            'confirm_name' => [
                'required',
                'string',
                Rule::in([$community instanceof Community ? $community->name : null]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm_name.in' => 'The name you typed does not match this community.',
        ];
    }
}
