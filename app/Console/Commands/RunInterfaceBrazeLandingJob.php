<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use League\Flysystem\AwsS3v2\AwsS3Adapter;

use Storage;

use App\Http\Controllers\BrazeLandingController;

use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class RunInterfaceBrazeLandingJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:braze_landing_job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Braze Landing Job';
    protected $count_file = 0;
    protected $process_date = '';
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
        $this->process_date = '2020-04-08';//Carbon::now()->add(-1, 'days')->format('Y-m-d');
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

        $list = Storage::disk('s3')->files($path);
        // echo 'Total files in ' . $path . ' ' . count($list) . "\n";

        if(count($list) == 0){
            $this->processOnlyFolder($path);
        }else{

            $total_data = 0;
            foreach ($list as $key => $value) {
                // echo $value . "\n";

                if(strpos($value, '.avro')!== false){
                    $this->count_file++;
                    Log::info($this->count_file . ' File name : ' . $value);
                    $landing_con = new BrazeLandingController;
                    $total_data += $landing_con->processAVRO($value, $this->process_date);
                    // exit;
                    // print_r($list);
                }
            }

            Log::info("Summary event data : " . $total_data);
        }
    }
}
