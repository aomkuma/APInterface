<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('login');
});

Route::get('login', function () {
    return view('login');
});

Route::post('login', [ 'as' => 'login', 'uses' => 'UserController@login']);

Route::get('jobs', function () {
    return view('job');
});

Route::post('admin/login', 'UserController@authenLogin');
Route::get('jobs', 'JobController@job');
Route::post('admin/job/submit', 'JobController@processJob');
