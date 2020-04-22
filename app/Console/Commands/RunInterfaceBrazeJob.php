<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use League\Flysystem\AwsS3v2\AwsS3Adapter;

use Storage;

use PhpOffice\PhpSpreadsheet;

use App\Http\Controllers\JobProcessController;

use Illuminate\Support\Facades\Log;

use App\Mail\DailyNotificationMail;

use Mail;

class RunInterfaceBrazeJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:braze_job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interface with S3 to transfer data to Braze system ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        Log::info("Daily start processing job...");
        // $this->testSendMail();
        // exit;
        // $contents = Storage::disk('s3')->get('Braze/Export/Purchase/Purchase_Booking_20200318.csv');
        // Log::debug($contents);
        // exit;
        // Storage::disk('s3')->makeDirectory(NEW_USER_PATH.'/success');
        // Storage::disk('s3')->makeDirectory(NEW_USER_PATH.'/error');
        // Storage::disk('s3')->makeDirectory(UPDATE_USER_PATH.'/success');
        // Storage::disk('s3')->makeDirectory(UPDATE_USER_PATH.'/error');
        // Storage::disk('s3')->makeDirectory(EVENT_PATH.'/success');
        // Storage::disk('s3')->makeDirectory(EVENT_PATH.'/error');
        // Storage::disk('s3')->makeDirectory(PURCHASE_PATH.'/success');
        // Storage::disk('s3')->makeDirectory(PURCHASE_PATH.'/error');
        // Storage::disk('s3')->makeDirectory(DELETE_CUSTOMER_PATH.'/success');
        // Storage::disk('s3')->makeDirectory(DELETE_CUSTOMER_PATH.'/error');
        // Storage::disk('s3')->makeDirectory(SUB_EMAIL_PATH.'/success');
        // Storage::disk('s3')->makeDirectory(SUB_EMAIL_PATH.'/error');

        // Storage::disk('s3')->makeDirectory('Braze/Export/logs');
        // exit;
        // Storage::disk('s3')->move('Braze/Export/NewCustomer/New_Cus_20200301.csv','Braze/Export/NewCustomer/success/New_Cus_20200301.csv');
        // $list = Storage::disk('s3')->allFiles(PURCHASE_PATH);
        // Log::info($list);
        // exit;
        
        // Storage::disk('s3')->move('Braze/Export/NewCustomer/New_Cus_20200305.csv','Braze/Export/NewCustomer/success/New_Cus_20200305.csv');
        // Storage::disk('s3')->move('Braze/Export/UpdateCustomer/Update_Cus_20200305.csv','Braze/Export/UpdateCustomer/success/Update_Cus_20200305.csv');
        // Storage::disk('s3')->move('Braze/Export/Event/Event_20200305.csv','Braze/Export/Event/success/Event_20200305.csv');
        // Storage::disk('s3')->move('Braze/Export/Purchase/Purchase_Booking_20200305.csv','Braze/Export/Purchase/success/Purchase_Booking_20200305.csv');
        // Storage::disk('s3')->move('Braze/Export/NewCustomer/New_Cus_20200305.csv','Braze/Export/NewCustomer/success/New_Cus_20200305.csv');
        // Storage::disk('s3')->move('Braze/Export/NewCustomer/New_Cus_20200305.csv','Braze/Export/NewCustomer/success/New_Cus_20200305.csv');

        // New Customer
        Log::info("Daily start processing job...");

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
        
        // read canvas and campaign from braze
        
        // $job_controller = new JobProcessController();

        // $job_controller->getCampaignData();
        // $job_controller->getCanvasData();

        Log::info("Daily processed job...");
        // exit;

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

            // if($cnt_process == 0){

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

            // }

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

    private function testSendMail(){
        $jobs = [];
        $jobs['type'] = '"Test" Mail Daily Notifications';
        $jobs['total_new_cus_success'] = 0;
        $jobs['total_update_cus_success'] = 0;
        $jobs['total_event_success'] = 0;
        $jobs['total_purchase_success'] = 0;
        $jobs['total_sub_mail_success'] = 0;
        $jobs['total_del_cus_success'] = 0;

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

        // Mail::to(SEND_MAIL_TO)->send(new DailyNotificationMail($jobs));
    }
}
