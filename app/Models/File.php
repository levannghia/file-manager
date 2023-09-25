<?php

namespace App\Models;

use App\Traits\HasCreatorAndUpdater;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory, NodeTrait, SoftDeletes, HasCreatorAndUpdater;

    public function user() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent() {
        return $this->belongsTo(File::class, 'parent_id');
    }

    public function starred(){
        return $this->hasOne(StarredFile::class, 'file_id', 'id')->where('user_id', Auth::id());
    }

    public function getOwnerAttribute($value)
    {
        return $this->attributes['created_by'] == Auth::id() ? 'me' : $this->user->name;
    }

    public function getFileSize(){
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $this->size > 0 ? floor(log($this->size, 1024)) : 0;

        return number_format($this->size / pow(1024, $power), 2, '.', ','). ' ' . $units[$power];
    }

    public function isOwnedBy($userId): bool
    {
        return $this->created_by == $userId;
    }

    public function isRoot(){
        return $this->parent_id === null;
    }

    protected static function boot(){
        parent::boot();
        static::creating(function($model){
            if(!$model->parent){
                return;
            }
            $model->path = (!$model->parent->isRoot() ? $model->parent->path . '/' : '') . Str::slug($model->name);
        });

        // static::deleted(function($model){
        //     if(!$model->is_folder){
        //         Storage::delete($model->storage_path);
        //     }
        // });
    }

    public function moveToTrash(){
        $this->deleted_at = Carbon::now();
        return $this->save();
    }

    public function deleteForever(){
        $this->deleteFileFromStorage([$this]);
        $this->forceDelete();
    }

    public function deleteFileFromStorage(Array $files){
        foreach ($files as $file) {
            if($file->is_folder){
                $this->deleteFileFromStorage($file->children);
            }else{
                Storage::delete($file->storage_path);
            }
        }
    }
}
