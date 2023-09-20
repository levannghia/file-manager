<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFileRequest extends ParentIdBaseRequest
{

    protected function prepareForValidation()
    {
        $paths = array_filter($this->relative_paths ?? [], fn ($f) => $f != null);

        $this->merge([
            'file_paths' => $paths,
            'folder_name' => $this->detectFolderName($paths)
        ]);
    }

    protected function passedValidation(){
        $data = $this->validated();
        $this->replace([
            'file_tree' => $this->buildFileTree($this->file_paths, $data['files'])
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'files.*' => [
                'required',
                'file',
                function ($attribute, $value, $fail) {
                    if (!$this->folder_name) {
                        $file = File::query()->where('name', $value->getClientOriginalName())
                            ->where('created_by', Auth::id())
                            ->where('parent_id', $this->parent_id)
                            ->whereNull('deleted_at')->exists();

                        if ($file) {
                            $fail('File "' . $value->getClientOriginalName() . '" already exists.');
                        }
                    }
                }
            ],
            'folder_name' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $folder = File::query()->where('name', $value)
                            ->where('created_by', Auth::id())
                            ->where('parent_id', $this->parent_id)
                            ->whereNull('deleted_at')->exists();

                        if ($folder) {
                            $fail('Folder "' . $value . '" already exists.');
                        }
                    }
                }
            ]
        ]);
    }

    private function buildFileTree($filePaths, $files){
        $filePaths = array_slice($filePaths, 0, count($files));
        $filePaths = array_filter($filePaths, fn ($f) => $f != null);
        $tree = [];

        /**
         * [
         *      test => [
         *          1.png
         *      ]
         * ]
         */

        foreach ($filePaths as $ind => $filePath) {
            $parts = explode('/', $filePath);
            $currentNode = &$tree;

            foreach ($parts as $i => $part) {
                if(!isset($currentNode[$part])){
                    $currentNode[$part] = [];
                }

                if($i === count($parts) - 1){
                    $currentNode[$part] = $files[$ind];
                }else{
                    $currentNode = &$currentNode[$part];
                }
            }
        }
        return $tree;
    }

    public function detectFolderName($paths)
    {
        if (!$paths) {
            return null;
        }

        $parts = explode('/', $paths[0]);
        return $parts[0];
    }
}
