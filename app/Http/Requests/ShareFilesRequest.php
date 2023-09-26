<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareFilesRequest extends FileActionRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                'email' => ['required', 'email']
            ]
        );
    }
}
