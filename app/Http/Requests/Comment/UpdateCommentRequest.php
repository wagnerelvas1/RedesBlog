<?php

namespace App\Http\Requests\Comment;

use App\Http\Requests\Concerns\ValidatesAttachments;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('comment')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required_without_all:image,keep_image', 'nullable', 'string', 'max:10000'],
            'image' => array_merge(['nullable'], $this->singleImageRules()),
            'keep_image' => ['boolean'],
        ];
    }
}
