<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Feed filters. GET routes that take input still need a FormRequest.
 */
class IndexPostsRequest extends FormRequest
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
            'sort' => ['nullable', 'string', 'in:hot,new,top,controversial'],
            'range' => ['nullable', 'string', 'in:day,week,month,year,all'],
            'cursor' => ['nullable', 'string'],
        ];
    }
}
