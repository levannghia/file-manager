<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FileController extends Controller
{
    public function myFiles() {
        return Inertia::render('MyFiles');
    }

    public function createFolder(StoreFolderRequest $requset) {
        $data = $requset->validated();
        $parent = $requset->parent;

        if(!$parent){
            $parent = $this->getBoot();
        }

        $file = new File();
        $file->is_folder = 1;
        $file->name = $data['name'];
        $parent->appendNode($file);
    }

    public function getBoot(){
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->firstOrFail();

    }
}