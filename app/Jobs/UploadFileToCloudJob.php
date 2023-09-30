<?php

namespace App\Jobs;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadFileToCloudJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected File $file)
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $model = $this->file;
        if(!$model->uploaded_on_cloud){
            $localPath = Storage::disk('local')->path($model->storage_path);
            Log::debug('Upload file on S3: ' . $localPath);
            try {
                $success = Storage::put($model->storage_path, Storage::disk('local')->get($model->storage_path));
                if($success){
                    Log::debug("Uploaded. Updating the database");
                    $model->uploaded_on_cloud = 1;
                    $model->saveQuietly();
                }else{
                    Log::debug('Unable to upload file to S3');
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }
}
