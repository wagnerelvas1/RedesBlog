<?php

namespace App\Http\Requests\Community;

use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Community;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityRequest extends FormRequest
{
    use ValidatesAttachments;

    /**
     * Names that would collide with existing routes.
     *
     * @var array<int, string>
     */
    public const RESERVED_NAMES = [
        'admin', 'api', 'c', 'u', 'settings', 'login', 'register', 'logout',
        'saved', 'communities', 'posts', 'comments', 'home', 'about', 'help',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('create', Community::class) === true;
    }

    /**
     * The `unique` rule is case-insensitive because `name` is `citext`.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9_]{3,21}$/',
                'not_in:'.implode(',', self::RESERVED_NAMES),
                'unique:communities,name',
            ],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'rules' => ['nullable', 'string', 'max:10000'],
            'avatar' => array_merge(['nullable'], $this->singleImageRules()),
            'banner' => array_merge(['nullable'], $this->singleImageRules()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name must be 3 to 21 letters, numbers or underscores.',
            'name.not_in' => 'That community name is reserved.',
        ];
    }
}
