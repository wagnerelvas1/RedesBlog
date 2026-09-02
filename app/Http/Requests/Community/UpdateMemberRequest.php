<?php

namespace App\Http\Requests\Community;

use App\Enums\CommunityRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Role changes require the creator; ban changes require any admin.
 */
class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $community = $this->route('community');

        if ($user === null) {
            return false;
        }

        if ($this->has('role') && ! $user->can('manageAdmins', $community)) {
            return false;
        }

        if ($this->has('banned') && ! $user->can('manageMembers', $community)) {
            return false;
        }

        return $this->has('role') || $this->has('banned');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['sometimes', Rule::enum(CommunityRole::class)],
            'banned' => ['sometimes', 'boolean'],
        ];
    }
}
