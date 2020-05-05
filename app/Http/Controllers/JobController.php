<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\JobInterfaceBraze;
use App\Console\Commands\FacebookCommand;
class JobController extends Controller
{
    public function __construct () {
        $this->middleware('auth');
    }

    public function job(){
    	return view('job');
    }

    public function processJob(){
    	// return view('job');
    	// \Artisan::call("command:braze_job");
        // call_in_background("command:braze_job");
        // $exitCode = \Artisan::call('queue:work');
        JobInterfaceBraze::dispatch();//->onQueue('feed-parse-workers');
        // \Queue::push(new JobInterfaceBraze());
    	return view('job-result');
    }
      public function processFacebook(){
    	// return view('job');
    	// \Artisan::call("command:braze_job");
        // call_in_background("command:braze_job");
        // $exitCode = \Artisan::call('queue:work');
        FacebookCommand::dispatch();//->onQueue('feed-parse-workers');
        // \Queue::push(new JobInterfaceBraze());
    	return view('facebook-result');
    }
}
