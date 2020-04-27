<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\FacebookController;
use App\Mail\FacebookOfflineConvertionMail;
use Mail;

class FacebookCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:fbcmd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        $total_walk_success = 0;
        $total_book_success = 0;
        $total_walk_success = $this->processFiles(FACEBOOKOFFLINE_WALK_PATH, 'Lead');
        $total_book_success = $this->processFiles(FACEBOOKOFFLINE_BOOK_PATH, 'Purchase');

        $detail = [];
        $detail['type'] = 'Facebook offline conversion Daily Notifications';
        $detail['total_book_success'] = $total_book_success;
        $detail['total_walk_success'] = $total_walk_success;


        $list_mail_recv = explode("||", 'zeropro500@gmail.com');
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

    private function processFiles($EVENT_PATH, $eventname) {

        Log::info('Eventname : ' . $eventname);

        $list = Storage::disk('s3')->files($EVENT_PATH);

        $facebook_controller = new FacebookController();

        $total_success = 0;

        foreach ($list as $key => $value) {

 
            $contents = Storage::disk('s3')->get($value);

            if($eventname =='Lead'){
                 $total_success = $facebook_controller->getCsvWalk($value, $contents, $eventname);
            }else{
                $total_success = $facebook_controller->getCsvBook($value, $contents, $eventname);
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
