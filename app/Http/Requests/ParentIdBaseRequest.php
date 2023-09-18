<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ParentIdBaseRequest extends FormRequest
{

    public ?File $parent = null;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->parent = File::query()->where('id', $this->input('parent_id'))->first();

        if ($this->parent && !$this->parent->isOwnedBy(Auth::id())) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'parent_id' => [
                Rule::exists(File::class, 'id')
                    ->where(function (Builder $query) {
                        return $query
                            ->where('is_folder', '=', '1')
                            ->where('created_by', '=', Auth::id());
                    })
            ]
        ];
    }
}
