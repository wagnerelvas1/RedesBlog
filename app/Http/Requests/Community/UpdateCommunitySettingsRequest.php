<?php

namespace App\Http\Requests\Community;

use App\Http\Requests\Concerns\ValidatesAttachments;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Note the absence of `name`: a community name is immutable, so it is neither
 * fillable on the model nor accepted here.
 */
class UpdateCommunitySettingsRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('community')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'rules' => ['nullable', 'string', 'max:10000'],
            'avatar' => array_merge(['nullable'], $this->singleImageRules()),
            'banner' => array_merge(['nullable'], $this->singleImageRules()),
            'remove_avatar' => ['boolean'],
            'remove_banner' => ['boolean'],
        ];
    }
}
