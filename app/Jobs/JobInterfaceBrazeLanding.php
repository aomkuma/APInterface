<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use League\Flysystem\AwsS3v2\AwsS3Adapter;

use Storage;

use App\Http\Controllers\BrazeLandingController;

use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use App\Mail\BrazeLandingMail;

use Mail;

class JobInterfaceBrazeLanding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $count_file = 0;
    protected $process_date = '';
    protected $event_list = [];
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
        $this->process_date = Carbon::now()->add(-1, 'days')->format('Y-m-d');
        $this->processOnlyFolder(LANDING_PATH);

        // Send mail
        $list_mail_recv = explode("||", SEND_MAIL_TO);
        $cnt_mail = 0;
        $mail_to = '';
        $mail_cc = [];
        foreach ($list_mail_recv as $key => $value) {
            if($cnt_mail == 0){
                $mail_to = $value;
            }else{
                $mail_cc[] = $value;
            }
            $cnt_mail++;
        }
        Mail::to($mail_to)->cc($mail_cc)->send(new BrazeLandingMail($this->event_list));

    }

    private function processOnlyFolder($path){

        $list = Storage::disk('s3')->directories($path);
        // echo 'Total folder in ' . $path . ' ' . count($list) . "\n";
        foreach ($list as $key => $value) {

            if(strpos($value, 'date=') !== false){
                if(strpos($value, 'date=' . $this->process_date) !== false){
                    $this->processOnlyFiles($value);
                }
            }else{
                $this->processOnlyFiles($value);
            }

        }
    }

    private function processOnlyFiles($path){

        $format_type = 'Landing';
        $list = Storage::disk('s3')->files($path);

        if(count($list) == 0){
            $this->processOnlyFolder($path);
        }else{

            $total_data = 0;
            $index = 0;
            foreach ($list as $key => $value) {
                // echo $value . "\n";

                if(strpos($value, '.avro')!== false){
                    $this->count_file++;
                    $index++;
                    Log::channel($format_type)->info($this->count_file . ' File name : ' . $value);
                    $landing_con = new BrazeLandingController;
                    $total_data += $landing_con->processAVRO($value, $this->process_date, $index);
                    // exit;
                    // print_r($list);
                    
                    $event_list[]['total_files'] = $total_data;

                }
            }

            // Log::channel($format_type)->info("Summary event data : " . $total_data);
            if($index > 0){
                $this->event_list[] = [
                                    'event_name' => $this->getEventName($path)
                                    , 'total_files' => $index
                                ];
            }
        }
    }

    private function getEventName($file_path){

        $file_arr = explode('/', $file_path);
        $path_name = '';
        if(count($file_arr) >= 5){
            $path_name = $file_arr[4];
            $path_name = str_replace('event_type=', '', $path_name);
        }
        return $path_name;
    }
}
