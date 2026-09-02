<?php

namespace App\Http\Requests\Vote;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $votable = $this->route('post') ?? $this->route('comment');

        return $this->user()?->can('vote', $votable) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', 'in:-1,1'],
        ];
    }
}
