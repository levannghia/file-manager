<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DestroyFileRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return array_merge(parent::rules(),[
            'all' => 'nullable|bool',
            'ids.*' => Rule::exists(File::class, 'id')->where(function($query){
                $query->where('created_by', Auth::id());
            })
        ]);
    }
}
