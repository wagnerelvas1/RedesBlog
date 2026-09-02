<?php

namespace App\Http\Requests\Community;

use App\Enums\CommunityRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMembers', $this->route('community')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(CommunityRole::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
