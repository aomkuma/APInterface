<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\JobInterfaceBraze;
use App\Jobs\JobFacebook;
use App\Jobs\JobInterfaceBrazeLanding;

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
        JobInterfaceBraze::dispatch()->onQueue('braze_export');//->onQueue('feed-parse-workers');
        // \Queue::push(new JobInterfaceBraze());
    	return view('job-result');
    }
    public function processFacebook(){
    	// return view('job');
    	// \Artisan::call("command:braze_job");
        // call_in_background("command:braze_job");
        // $exitCode = \Artisan::call('queue:work');
        JobFacebook::dispatch()->onQueue('facebook');//->onQueue('feed-parse-workers');
        // \Queue::push(new JobInterfaceBraze());
    	return view('facebook-result');
    }

    public function processBrazeLanding(){
        // return view('job');
        // \Artisan::call("command:braze_job");
        // call_in_background("command:braze_job");
        // $exitCode = \Artisan::call('queue:work');
        JobInterfaceBrazeLanding::dispatch()->onQueue('braze_landing');//->onQueue('feed-parse-workers');
        // \Queue::push(new JobInterfaceBraze());
        return view('job-result');
    }
}
