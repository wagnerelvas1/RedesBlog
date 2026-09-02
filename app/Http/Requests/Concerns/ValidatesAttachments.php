<?php

namespace App\Http\Requests\Concerns;

/**
 * Shared image-upload validation rules.
 *
 * Every upload in the application funnels through these rules so mime types,
 * file size and dimension limits stay identical across posts, comments,
 * community images and avatars.
 */
trait ValidatesAttachments
{
    /**
     * Rules for a repeated image field (`images`, `images.*`).
     *
     * @return array<string, array<int, string>>
     */
    protected function imageRules(int $max = 1, string $field = 'images'): array
    {
        return [
            $field => ['sometimes', 'array', 'max:'.$max],
            $field.'.*' => $this->singleImageRules(),
        ];
    }

    /**
     * Rules for one optional image field.
     *
     * @return array<int, string>
     */
    protected function singleImageRules(): array
    {
        return [
            'file',
            'image',
            'mimes:jpg,jpeg,png,webp,gif',
            'max:5120',
            'dimensions:max_width=4000,max_height=4000',
        ];
    }
}
