<?php

namespace App\Http\Controllers;
use Auth;
use Hash;
use Redirect;
use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
	public function login(){
		return view('login');
	}

    public function authenLogin(Request $r){
    	$userdata = array(
	        'username'     => $r->get('username'),
	        'password'  => $r->get('password')
	    );
    	// print_r($userdata);exit;
	    if (Auth::attempt($userdata)) {

	        // validation successful!
	        // redirect them to the secure section or whatever
	        // return Redirect::to('secure');
	        // for now we'll just echo success (even though echoing in a controller is bad)
	        // echo 'SUCCESS!';

	        return Redirect::to('jobs');

	    } else {        

	        // validation not successful, send back to form 
	        return Redirect::to('login');

	    }
    }
}
