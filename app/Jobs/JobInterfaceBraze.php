<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use League\Flysystem\AwsS3v2\AwsS3Adapter;

use Storage;

use PhpOffice\PhpSpreadsheet;

use App\Http\Controllers\JobProcessController;

use Illuminate\Support\Facades\Log;

use App\Mail\DailyNotificationMail;

use Mail;

class JobInterfaceBraze implements ShouldQueue
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
        // Log::info(UPDATE_USER_PATH);
        $total_new_cus_success = 0;
        $total_update_cus_success = 0;
        $total_event_success = 0;
        $total_purchase_success = 0;
        $total_sub_mail_success = 0;
        $total_del_cus_success = 0;

        $total_new_cus_success = $this->processFiles(NEW_USER_PATH, 'NewCustomer');
        $total_update_cus_success = $this->processFiles(UPDATE_USER_PATH, 'UpdateCustomer');
        $total_event_success = $this->processFiles(EVENT_PATH, 'Event');
        $total_purchase_success = $this->processFiles(PURCHASE_PATH, 'Purchase');
        $total_sub_mail_success = $this->processFiles(SUB_EMAIL_PATH, 'SubMail');
        $total_del_cus_success = $this->processFiles(DELETE_CUSTOMER_PATH, 'DeleteCustomer');

        Log::info("Processed job...");

        try{

            $file_name = date('Y-m-d') . '.log';

            $log_file_name = 'storage/logs/InterfaceBraze-' . $file_name;
            $log_file = fopen($log_file_name, 'r');
            $content = fread($log_file, filesize($log_file_name));
            // echo $content;exit;
            $storageInstance = Storage::disk('s3');
            
            $S3_file_path = LOG_PATH . '/' . $log_file_name;
            $putFileOnStorage = $storageInstance->put($S3_file_path, $content);

        }catch(\Exception $e){

            Log::error($e->getMessage());
            
        }
        // Send mail daily summary

        $jobs = [];
        $jobs['type'] = 'Daily Notifications';
        $jobs['total_new_cus_success'] = $total_new_cus_success;
        $jobs['total_update_cus_success'] = $total_update_cus_success;
        $jobs['total_event_success'] = $total_event_success;
        $jobs['total_purchase_success'] = $total_purchase_success;
        $jobs['total_sub_mail_success'] = $total_sub_mail_success;
        $jobs['total_del_cus_success'] = $total_del_cus_success;

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
        Mail::to($mail_to)->cc($mail_cc)->send(new DailyNotificationMail($jobs));
       
    }

    private function processFiles($JOB_PATH, $format_type){

        Log::channel($format_type)->info('Process type : ' . $format_type);

        $list = Storage::disk('s3')->files($JOB_PATH);

        $job_controller = new JobProcessController();

        $total_success = 0;

        $cnt_process = 0;
        foreach ($list as $key => $value) { 

            if($cnt_process == 1){

                Log::channel($format_type)->info('Process file : ' . $value);
                $contents = Storage::disk('s3')->get($value);
                // Log::channel($format_type)->debug($contents);
                // exit;
                
                switch ($format_type) {
                    case 'NewCustomer':
                        $total_success += $job_controller->getCsvNewUserGroupSend($value, $contents);
                        break;
                    case 'UpdateCustomer':
                        $total_success += $job_controller->getCsvUpdateUser($value, $contents);
                        break;
                    case 'Event':
                        $total_success += $job_controller->getCsvEvent($value, $contents);
                        break;
                    case 'Purchase':
                        $total_success += $job_controller->getCsvPurchase($value, $contents);
                        break;
                    case 'DeleteCustomer':
                        $total_success += $job_controller->getCsvDeleteCustomer($value, $contents);
                        break;
                    case 'SubMail':
                        $total_success += $job_controller->getCsvSubMail($value, $contents);
                        break;
                }

            }

            $cnt_process++;
            
        }

        try{

            $file_name = $format_type . '-' . date('Y-m-d') . '.log';

            $log_file_name = 'storage/logs/' . $file_name;
            $log_file = fopen($log_file_name, 'r');
            $content = fread($log_file, filesize($log_file_name));
            
            $storageInstance = Storage::disk('s3');
            
            $S3_file_path = LOG_PATH . '/' . $file_name;
            $putFileOnStorage = $storageInstance->put($S3_file_path, $content);

        }catch(\Exception $e){

            Log::error($e->getMessage());
            
        }

        return $total_success;

    }
}
