<?php

namespace App\Http\Requests\Post;

use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()?->can('create', [Post::class, $this->route('community')]) === true;
    }

    /**
     * A title-only post is valid: images and body are both optional.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:300'],
            'body' => ['nullable', 'string', 'max:40000'],
            ...$this->imageRules(10),
        ];
    }
}
