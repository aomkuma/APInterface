<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use League\Flysystem\AwsS3v2\AwsS3Adapter;

use Storage;

use App\Http\Controllers\BrazeLandingController;

use Illuminate\Support\Facades\Log;

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
        $this->processOnlyFolder(LANDING_PATH);
    }

    private function processOnlyFolder($path){

        $list = Storage::disk('s3')->directories($path);
        // echo 'Total folder in ' . $path . ' ' . count($list) . "\n";
        foreach ($list as $key => $value) {
            $this->processOnlyFiles($value);
        }
    }

    private function processOnlyFiles($path){

        $list = Storage::disk('s3')->files($path);
        // echo 'Total files in ' . $path . ' ' . count($list) . "\n";

        if(count($list) == 0){
            $this->processOnlyFolder($path);
        }else{
            foreach ($list as $key => $value) {
                // echo $value . "\n";

                if(strpos($value, '.avro')!== false){
                    $landing_con = new BrazeLandingController;
                    $landing_con->processAVRO($value);
                    exit;
                    // print_r($list);
                }
            }
        }
    }
}
