<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string filters for the community index.
 */
class IndexCommunitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'in:members,new,name'],
            'filter' => ['nullable', 'string', 'in:all,joined'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
