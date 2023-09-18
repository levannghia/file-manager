<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasCreatorAndUpdater {
    protected static function bootHasCreatorAndUpdater() {
        static::creating(function($model){
            $model->created_by = Auth::user()->id;
            $model->updated_by = Auth::user()->id;
        });

        static::updating(function($model){
            $model->updated_by = Auth::user()->id;
        });
    }
}