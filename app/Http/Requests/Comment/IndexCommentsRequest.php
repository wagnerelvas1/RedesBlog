<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class IndexCommentsRequest extends FormRequest
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
            'sort' => ['nullable', 'string', 'in:best,new,top,old,controversial'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'cursor' => ['nullable', 'string'],
        ];
    }
}
