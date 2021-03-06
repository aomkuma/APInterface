<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\FacebookController;
use App\Mail\FacebookOfflineConvertionMail;
use Mail;

class JobFacebook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        Log::info('Begin manual run job facebook');

        $total_walk_success = 0;
        $total_purchase_success = 0;
        $total_walk_success = $this->processFiles(FACEBOOKOFFLINE_WALK_PATH, 'Lead');
        $total_purchase_success = $this->processFiles(FACEBOOKOFFLINE_PURCHASE_PATH, 'Purchase');

        $detail = [];
        $detail['type'] = 'Facebook offline conversion Daily Notifications';
        $detail['total_purchase_success'] = $total_purchase_success;
        $detail['total_walk_success'] = $total_walk_success;


        $list_mail_recv = explode("||", SEND_MAIL_TO);
        $cnt_mail = 0;
        $mail_to = '';
        $mail_cc = [];
        foreach ($list_mail_recv as $key => $value) {
            if ($cnt_mail == 0) {
                $mail_to = $value;
            } else {
                $mail_cc[] = $value;
            }
            $cnt_mail++;
        }
        Mail::to($mail_to)->cc($mail_cc)->send(new FacebookOfflineConvertionMail($detail));
    }

    private function processFiles($EVENT_PATH,$eventname) {
//        $exp = explode("/", $EVENT_PATH);
        Log::info('Eventname : ' . $eventname);

        $list = Storage::disk('s3')->files($EVENT_PATH);

        $facebook_controller = new FacebookController();

        $total_success = 0;

        foreach ($list as $key => $value) {


            $contents = Storage::disk('s3')->get($value);

            if ($eventname == 'Purchase') {
                $total_success = $facebook_controller->getCsvBook($value, $contents, $eventname);
            } else {
                $total_success = $facebook_controller->getCsvWalk($value, $contents, $eventname);
            }
        }

        try {

            $file_name = 'laravel-' . date('Y-m-d') . '.log';

            $log_file_name = 'storage/logs/' . $file_name;
            $log_file = fopen($log_file_name, 'r');
            $content = fread($log_file, filesize($log_file_name));

            $storageInstance = Storage::disk('s3');

            $S3_file_path = FACEBOOKOFFLINE_LOG . '/' . $file_name;
            $putFileOnStorage = $storageInstance->put($S3_file_path, $content);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
        }

        return $total_success;
    }
}
