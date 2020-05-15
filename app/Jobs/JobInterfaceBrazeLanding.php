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


class JobInterfaceBrazeLanding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $count_file = 0;
    protected $process_date = '';
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
        // echo 'Total files in ' . $path . ' ' . count($list) . "\n";

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
                    
                }
            }

            Log::channel($format_type)->info("Summary event data : " . $total_data);
        }
    }
}
