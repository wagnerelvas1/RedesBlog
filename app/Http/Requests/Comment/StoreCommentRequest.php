<?php

namespace App\Http\Requests\Comment;

use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCommentRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()?->can('create', [Comment::class, $this->route('post')]) === true;
    }

    /**
     * A comment needs a body, an image, or both.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required_without:image', 'nullable', 'string', 'max:10000'],
            'image' => array_merge(['nullable'], $this->singleImageRules()),
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    /**
     * The parent must live on the same post and must not be deleted.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentId = $this->input('parent_id');
            $post = $this->route('post');

            if ($parentId === null || ! $post instanceof Post) {
                return;
            }

            $parent = Comment::query()->where('id', $parentId)->first();

            if ($parent === null || $parent->post_id !== $post->id) {
                $validator->errors()->add('parent_id', 'That comment does not belong to this post.');

                return;
            }

            if ($parent->deleted_at !== null) {
                $validator->errors()->add('parent_id', 'You cannot reply to a deleted comment.');
            }
        });
    }
}
