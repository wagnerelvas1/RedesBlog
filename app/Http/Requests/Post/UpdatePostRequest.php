<?php

namespace App\Http\Requests\Post;

use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePostRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('post')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:300'],
            'body' => ['nullable', 'string', 'max:40000'],
            'existing_images' => ['sometimes', 'array', 'max:10'],
            'existing_images.*' => ['integer'],
            ...$this->imageRules(10),
        ];
    }

    /**
     * Guards that kept ids really belong to this post and that the combined
     * image count stays within the limit.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $post = $this->route('post');

            if (! $post instanceof Post) {
                return;
            }

            /** @var array<int, int> $keep */
            $keep = array_map('intval', (array) $this->input('existing_images', []));

            if ($keep !== []) {
                $owned = $post->attachments()->whereIn('id', $keep)->pluck('id')->all();

                if (count($owned) !== count(array_unique($keep))) {
                    $validator->errors()->add(
                        'existing_images',
                        'One of the selected images does not belong to this post.',
                    );
                }
            }

            $incoming = count((array) $this->file('images', []));

            if (count(array_unique($keep)) + $incoming > 10) {
                $validator->errors()->add('images', 'A post may hold at most 10 images.');
            }
        });
    }
}
