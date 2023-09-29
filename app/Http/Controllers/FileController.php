<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddFavouritsRequest;
use App\Http\Requests\FileActionRequest;
use App\Http\Requests\ShareFilesRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\TrashFileRequest;
use App\Http\Resources\FileResource;
use App\Jobs\UploadFileToCloudJob;
use App\Mail\SharedFileMail;
use App\Models\File;
use App\Models\FileShare;
use App\Models\StarredFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FileController extends Controller
{
    public function myFiles(Request $request, string $folder = null)
    {

        if ($folder) {
            $folder = File::query()->where('created_by', Auth::id())->where('path', $folder)->firstOrFail();
        }

        if (!$folder) {
            $folder = $this->getBoot();
        }

        $search = $request->get('search');
        $favourites = (int)$request->get('favourites');

        $query = File::query()->select('files.*')
            ->with(['starred'])
            ->where('files.created_by', Auth::id())
            ->where('_lft', '!=', 1)
            ->orderBy('is_folder', 'desc')
            ->orderBy('files.created_at', 'desc')
            ->orderBy('files.id', 'desc');

        if($search){
            $query->where('name', 'like', "%$search%");
        }else{
            $query->where('parent_id', $folder->id);
        }

        if($favourites === 1){
            $query->join('starred_files', 'starred_files.file_id', 'files.id')->where('starred_files.user_id', Auth::id());
        }

        $files = $query->paginate(12);
        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);
        $folder = new FileResource($folder);

        return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors'));
    }

    public function trash(Request $request)
    {
        $files = File::onlyTrashed()->where('created_by', Auth::id())->orderBy('is_folder', 'desc')->orderBy('deleted_at', 'desc')->paginate(12);
        $files = FileResource::collection($files);
        if ($request->wantsJson()) {
            return $files;
        }
        return Inertia::render('Trash', compact('files'));
    }

    public function createFolder(StoreFolderRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        if (!$parent) {
            $parent = $this->getBoot();
        }

        $file = new File();
        $file->is_folder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);
    }

    public function store(StoreFileRequest $request)
    {
        $data = $request->validated();
        $fileTree = $request->file_tree;
        $user = $request->user();
        $parent = $request->parent;

        if (!$parent) {
            $parent = $this->getBoot();
        }

        if (!empty($fileTree)) {
            $this->saveFileTree($fileTree, $parent, $user);
        } else {
            foreach ($data['files'] as $key => $file) {
                $this->saveFile($file, $parent, $user);
            }
        }

        // dd($data, $parent);
    }

    public function saveFileTree($fileTree, $parent, $user)
    {
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {
                $folder = new File();
                $folder->is_folder = 1;
                $folder->name = $name;
                $parent->appendNode($folder);

                $this->saveFileTree($file, $folder, $user);
            } else {
                $this->saveFile($file, $parent, $user);
            }
        }
    }

    public function saveFile($file, $parent, $user)
    {
        $path = $file->store('/files/' . $user->id, 'local');
        $model = new File();
        $model->storage_path = $path;
        $model->is_folder = false;
        $model->name = $file->getClientOriginalName();
        $model->mime = $file->getMimeType();
        $model->size = $file->getSize();
        $model->uploaded_on_cloud = 0;
        $parent->appendNode($model);

        //To do start backgoround job upload file
        UploadFileToCloudJob::dispatch($model);
    }

    public function download(FileActionRequest $request)
    {
        $data = $request->validated();
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];
        $parent = $request->parent;

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        if ($all) {
            $url = $this->createZip($parent->children);
            $fileName = $parent->name . '.zip';
        } else {
            [$url, $fileName] = $this->getDownloadUrl($ids, $parent->name);
        }

        return [
            "url" => $url,
            "fileName" => $fileName
        ];
    }

    public function downloadSharedWithMe(FileActionRequest $request)
    {
        $data = $request->validated();
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        $file = File::getSharedWithMe()->get();

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }
        $zipName = 'share_with_me';

        if ($all) {
            $url = $this->createZip($file);
            $fileName = $zipName . '.zip';
        } else {
            [$url, $fileName] = $this->getDownloadUrl($ids, $zipName);
        }

        return [
            "url" => $url,
            "fileName" => $fileName
        ];
    }

    public function downloadSharedByMe(FileActionRequest $request)
    {
        $data = $request->validated();
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        $file = File::getSharedByMe()->get();

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }
        $zipName = 'share_with_me';

        if ($all) {
            $url = $this->createZip($file);
            $fileName = $zipName . '.zip';
        } else {
            [$url, $fileName] = $this->getDownloadUrl($ids, $zipName);
        }

        return [
            "url" => $url,
            "fileName" => $fileName
        ];
    }

    public function getDownloadUrl(array $ids, $zipName){
        if (count($ids) == 1) {
            $file = File::find($ids[0]);
            if ($file->is_folder) {
                if ($file->children->count() == 0) {
                    return ['message' => 'The folder is empty.'];
                }

                $url = $this->createZip($file->children);
                $fileName = $file->name . '.zip';
            } else {
                $dest = 'public/' . pathinfo($file->storage_path, PATHINFO_BASENAME);
                Storage::copy($file->storage_path, $dest);
                $url = asset(Storage::url($dest));
                $fileName = $file->name;
            }
        } else {
            $files = File::query()->whereIn('id', $ids)->get();
            $url = $this->createZip($files);
            $fileName = $zipName . '.zip';
        }

        return [$url, $fileName];
    }

    public function destroy(FileActionRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        if ($data['all']) {
            $children = $parent->children;
            foreach ($children as $child) {
                $child->moveToTrash();
            }
        } else {
            foreach ($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if ($file) {
                    $file->moveToTrash();
                }
            }
        }
        // dd($request->all());
        return redirect()->route('myFiles', ['folder' => $parent->path]);
    }

    public function createZip($files)
    {
        $zipPath = 'zip/' . Str::random() . '.zip';
        $publicPath = "public/$zipPath";
        if (!is_dir(dirname($publicPath))) {
            Storage::makeDirectory(dirname($publicPath));
        }

        $zipFile = Storage::path($publicPath);
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip($zip, $files);
        }

        $zip->close();

        return asset(Storage::url($zipPath));
    }

    private function addFilesToZip($zip, $files, $ancestors = '')
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->addFilesToZip($zip, $file->children, $ancestors . $file->name . '/');
            } else {
                $localPath = Storage::path($file->storage_path);
                // if ($file->uploaded_on_cloud == 1) {
                //     $dest = pathinfo($file->storage_path, PATHINFO_BASENAME);
                //     $content = Storage::get($file->storage_path);
                //     Storage::disk('public')->put($dest, $content);
                //     $localPath = Storage::disk('public')->path($dest);
                // }

                $zip->addFile($localPath, $ancestors . $file->name);
            }

            // dump($ancestors . $file->name);
        }
    }


    public function deleteForever(TrashFileRequest $request)
    {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed()->get();
            foreach ($children as $child) {
                $child->deleteForever();
            }
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->deleteForever();
            }
        }
        return redirect()->route('folder.trash');
    }

    public function restore(TrashFileRequest $request)
    {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed()->get();
            foreach ($children as $child) {
                $child->restore();
            }
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->restore();
            }
        }
        return redirect()->route('folder.trash');
    }

    public function share(ShareFilesRequest $request){
        $data = $request->validated();
        $parent = $request->parent;
        $all = $data['all'] ?? false;
        $email = $data['email'];
        $ids = $data['ids'] ?? [];

        if(!$all && empty($ids)){
            return [
                'message' => 'Please select files to share'
            ];
        }

        $user = User::query()->where('email', $email)->first();

        if(!$user){
            return redirect()->back();
        }

        $dataInsert = [];

        if($all){
            $files = $parent->children;
        }else{
            $files = File::find($ids);
        }

        $ids = Arr::pluck($files, 'id');
        // dd($ids);
        $existingFilesId = FileShare::query()->whereIn('file_id', $ids)->where('user_id', $user->id)->get()->keyBy('file_id');
        // dd($existingFilesId);

        foreach ($files as $file) {
            if($existingFilesId->has($file->id)){
                continue;
            }

            $dataInsert[] = [
                "file_id" => $file->id,
                "user_id" => $user->id,
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now()
            ];
        }

        FileShare::insert($dataInsert);
        Mail::to($user)->send(new SharedFileMail($user, Auth::user(), $files));
        return redirect()->back();
    }

    public function addToFavourites(AddFavouritsRequest $request)
    {
        $data = $request->validated();
        $id = $data['id'];
        $file = File::findOrFail($id);

        $starredFile = StarredFile::query()->where('file_id', $file->id)->where('user_id', Auth::id())->first();

        if ($starredFile) {
            $starredFile->delete();
        } else {
            $dataInsert = [
                'file_id' => $file->id,
                'user_id' => Auth::id(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            StarredFile::create($dataInsert);
        }
        return redirect()->back();
    }

    public function sharedWithMe(Request $request){
        $search = $request->get('search');
        $query = File::getSharedWithMe();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $files = $query->paginate(12);
        $files = FileResource::collection($files);
        if ($request->wantsJson()) {
            return $files;
        }
        // dd($files);
        return Inertia::render('SharedWithMe', compact('files'));
    }

    public function sharedByMe(Request $request)
    {
        $search = $request->get('search');
        $query = File::getSharedByMe();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $files = $query->paginate(12);
        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('SharedByMe', compact('files'));
    }

    public function getBoot()
    {
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->first();
    }
}
