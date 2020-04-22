<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use League\Csv\Writer;
use League\Csv\Reader;
use League\Csv\CannotInsertRecord;
use Illuminate\Support\Facades\Storage;
use App\Mail\MailJobsCreated;

use Illuminate\Support\Facades\Log;

use Mail;

class JobProcessController extends Controller
{
    //

    private $check_na_value = 'N/A'; 

     public function getCsvNewUser($file_path, $contents){

        $format_type = 'NewCustomer';
        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);
        // Log::info($lines);
        $array = array();

        $api_field_list = ['external_id'/*, 'alias_name', 'alias_label'*/];
        $fields = [];
        $values = [];
        $row = 0;

        // get data
        foreach ($lines as $line) {
            if(!empty($line)){
                if($row == 0){
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                }else{
                    // actual values
                    $values[] = str_getcsv($line);
                }
                $row++;
            }
        }

        Log::channel($format_type)->info('total new users : '. count($values));

        $success_data = [];
        $error_data = [];

        // re format data
        for($i = 0; $i < count($values); $i++){

            $item = [];
            for($j = 0; $j <count($fields); $j++){
                // if(in_array($fields[$j], $api_field_list)){

                    // $field_name = $fields[$j];
                    // $item[$field_name] = $values[$i][$j];
                    // $item['alias_name'] = 'null';
                    // $item['alias_label'] = 'null';
                // }
                if(in_array($fields[$j], $fields_arr)){

                    $field_value = trim($values[$i][$j]);
                    if(!empty($field_value) && $field_value != $this->check_na_value){
                        $field_name = $fields[$j];
                        $item[$field_name] = $field_value;  
                    }
                    
                }
            }
            
            $res = ['api_key' => BRAZE_API_KEY,
                    'user_aliases' => [$item]];

            try{
                // Log::info(json_encode($res));
                $response_data = clientPostRequest(BRAZE_URL_NEW_USER, ($res));
                Log::channel($format_type)->info(json_encode($response_data));

                // check status            
                if($response_data->aliases_processed){
                	// Keep success
                	$success_data[] = $values[$i];
                }else{
                	// Keep error
                	$error_data[] = $values[$i];
                    Log::channel($format_type)->error('-------------ERROR DATA-------------');
                    Log::channel($format_type)->error(json_encode($res));
                    // Log::error(json_encode($response_data));
                }

            }catch(\Exception $e){
                $error_data[] = $values[$i];
                Log::channel($format_type)->error($e->getMessage());
                Log::channel($format_type)->error(json_encode($res));
                $error_desc = $e->getMessage();
            }

            // sleep(1);
            usleep(30000);
        }

        Log::channel($format_type)->info('Total success : ' . count($success_data));
        Log::channel($format_type)->info('Total error : ' . count($error_data));

        // Send email when error
        if(count($error_data) > 0){

            // upload error file to S3
            $now = date('YmdHis');
            // $fileName = "New_Cus_$now.csv";
            $fileName = $this->getOnlyFilename($file_path);

            $error_file_url = $this->createCsvFileToS3(NEW_USER_PATH, '/error/', $fileName, $fields_arr, $error_data);
            Log::channel($format_type)->info('URL error file : ' . $error_file_url);


        	$jobs = [];
        	$jobs['type'] = 'New Customer';
        	$jobs['total_error'] = count($error_data);
        	$jobs['file_url'] = $error_file_url;
            $jobs['error_desc'] = $error_desc;

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
        	Mail::to($mail_to)->cc($mail_cc)->send(new MailJobsCreated($jobs));

        }

        // if(count($success_data) > 0){
            // Move archieve file
            $this->moveArcheiveFile($file_path);
        // }

        return count($success_data);
        
    }

    public function getCsvNewUserGroupSend($file_path, $contents){

        $format_type = 'NewCustomer';

        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);
        // Log::info($lines);
        $array = array();

        $api_field_list = ['external_id'/*, 'alias_name', 'alias_label'*/];
        $fields = [];
        $values = [];
        $row = 0;

        // get data
        foreach ($lines as $line) {
            if(!empty($line)){
                if($row == 0){
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                }else{
                    // actual values
                    $values[] = str_getcsv($line);
                }
                $row++;
            }
        }

        Log::channel($format_type)->info('total new users : '. count($values));

        $success_data = [];
        $error_data = [];
        $single_error_data = [];
        $error_desc = '';
        $error_desc_item = '';

        // re format data

        $cnt_values = count($values);
        $cnt_fields = count($fields);

        $res = ['api_key' => BRAZE_API_KEY, 'user_aliases' => []];

        for($i = 0; $i < $cnt_values; $i++){

            $item = [];
            for($j = 0; $j <$cnt_fields; $j++){

                if(in_array($fields[$j], $fields_arr)){

                    $field_value = trim($values[$i][$j]);
                    if(!empty($field_value) && $field_value != $this->check_na_value){
                        $field_name = $fields[$j];
                        $item[$field_name] = $field_value;  
                    }
                    
                }
            }

            $res['user_aliases'][] = $item;
            
            if( ($i > 0 && ($i%49 == 0)) || ($i == ($cnt_values - 1))){

                Log::channel($format_type)->info('Loop send : '. $i);
                // Log::info(json_encode($res));
                
                try{
                    // Log::info(json_encode($res));
                    $response_data = clientPostRequest(BRAZE_URL_NEW_USER, ($res));
                    Log::channel($format_type)->info(json_encode($response_data));

                    // check status            
                    if($response_data->aliases_processed){
                        // Keep success
                        $success_data[] = $res['user_aliases'];
                    }else{
                        // Keep error
                        $error_data[] = $res['user_aliases'];
                        Log::channel($format_type)->error('-------------ERROR DATA-------------');
                        Log::channel($format_type)->error(json_encode($res));
                        // Log::error(json_encode($response_data));
                    }

                }catch(\Exception $e){
                    // $error_data[] = $res['user_aliases'];
                    Log::channel($format_type)->error($e->getMessage());
                    Log::channel($format_type)->error(json_encode($res['user_aliases']));
                    $error_desc = $e->getMessage();

                    $cnt_error = 0;
                    foreach ($res['user_aliases'] as $err_k => $err_v) {
                        
                        $origin_data_index = ($i - (count($res['user_aliases']) - $cnt_error)) + 1;
                        $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                        $cnt_error++;
                    }
                }

                // sleep(1);
                usleep(30000);
                $res = ['api_key' => BRAZE_API_KEY, 'user_aliases' => []];

            }

        }

        Log::channel($format_type)->info('Total success : ' . count($success_data));
        Log::channel($format_type)->info('Total sent error : ' . count($error_data));
        Log::channel($format_type)->info('Total data error : ' . count($single_error_data));

        // Send email when error
        if(count($error_data) > 0 || count($single_error_data) > 0){

            // upload error file to S3
            $now = date('YmdHis');
            // $fileName = "New_Cus_$now.csv";
            $fileName = $this->getOnlyFilename($file_path);

            $fileName = $this->createErrorFileName($fileName);            
        
            $new_error_data = $this->restructureErrorData($error_data, $single_error_data);

            $error_file_url = $this->createCsvFileToS3(NEW_USER_PATH, '/error/', $fileName, $fields_arr, $new_error_data);
            Log::channel($format_type)->info('URL error file : ' . $error_file_url);

            $total_error = count($error_data) + count($single_error_data);

            $jobs = [];
            $jobs['type'] = 'New Customer';
            $jobs['total_error'] = $total_error;
            $jobs['file_url'] = $error_file_url;
            $jobs['error_desc'] = $error_desc;
            
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
            Mail::to($mail_to)->cc($mail_cc)->send(new MailJobsCreated($jobs));
        }

        // if(count($success_data) > 0){
            // Move archieve file
            $this->moveArcheiveFile($file_path);
        // }

        return count($success_data);
        
    }

    public function getCsvUpdateUser($file_path, $contents){

        $format_type = 'UpdateCustomer';
        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);
        // Log::info($lines);
        // exit;
        $array = array();

        $fields = [];
        $values = [];
        $row = 0;

        // get data
        foreach ($lines as $line) {
            if(!empty($line)){
                if($row == 0){
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                }else{
                    // actual values
                    $values[] = str_getcsv($line);
                }
                $row++;
            }
        }
        Log::channel($format_type)->info('total update users : '. count($values));
        // exit;
        $success_data = [];
        $error_data = [];
        $single_error_data = [];
        $error_desc = '';
        $error_desc_item = '';

        // re format data
        $cnt_values = count($values);
        $cnt_fields = count($fields);

        // $send_values = ['attributes' => []];
        $res = ['api_key' => BRAZE_API_KEY,'attributes' => []];
        
        for($i = 0; $i < $cnt_values; $i++){

            $item = [];
            for($j = 0; $j < $cnt_fields; $j++){
                if(in_array($fields[$j], $fields_arr)){

                    $field_value = trim($values[$i][$j]);
                    if(!empty($field_value) && $field_value != $this->check_na_value){
                        $field_name = $fields[$j];
                        $item[$field_name] = $field_value;  
                    }
                    
                }
            }
            
            $res['attributes'][] = $item;

            if( ($i > 0 && ($i%50 == 0)) || ($i == ($cnt_values - 1))){

                Log::channel($format_type)->info('Loop send : '. $i);
                // if($i == 7696){
                    // Log::info($res);
                    // exit;
                
                    try{
                        // Log::info(json_encode($res));//exit;
                        $response_data = clientPostRequest(BRAZE_URL_UPDATE_USER, ($res));
                        Log::channel($format_type)->info(json_encode($response_data));

                        // check status            
                        if($response_data->attributes_processed){
                            // Keep success
                            $success_data[] = $res['attributes'];

                            if(isset($response_data->errors)){
                                foreach ($response_data->errors as $err_k => $err_v) {
                                    $error_desc_item = $err_v->type;
                                    $error_index = $err_v->index;
                                    Log::channel($format_type)->error($error_desc_item);
                                    Log::channel($format_type)->error($res['attributes'][$error_index]);

                                    $origin_data_index = ($i - (count($res['attributes']) - $error_index)) + 1;
                                    $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                                }
                            }

                        }else{
                            // Keep error
                            $error_data[] = $res['attributes'];
                            Log::channel($format_type)->error('-------------ERROR DATA-------------');
                            Log::channel($format_type)->error(json_encode($res));
                            // Log::error(json_encode($response_data));
                        }

                    }catch(\Exception $e){
                        // $error_data[] = $res['attributes'];
                        Log::channel($format_type)->error($e->getMessage());
                        Log::channel($format_type)->error(json_encode($res['attributes']));
                        $error_desc = $e->getMessage();

                        $cnt_error = 0;
                        foreach ($res['attributes'] as $err_k => $err_v) {
                            
                            $origin_data_index = ($i - (count($res['attributes']) - $cnt_error)) + 1;
                            $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                            $cnt_error++;
                        }
                    }

                    // sleep(1);
                    usleep(30000);
                    
                    // $send_values = [];
                    
                    // exit;
                // }

                $res = ['api_key' => BRAZE_API_KEY,'attributes' => []];
                
            }
            /**/

        }
        // exit;
        Log::channel($format_type)->info('Total sent success : ' . count($success_data));
        Log::channel($format_type)->info('Total sent error : ' . count($error_data));
        Log::channel($format_type)->info('Total data error : ' . count($single_error_data));

        // Send email when error
        if(count($error_data) > 0 || count($single_error_data) > 0){

            // upload error file to S3
            $now = date('YmdHis');
            // $fileName = "Update_Cus_$now.csv";
            $fileName = $this->getOnlyFilename($file_path);
            
            $fileName = $this->createErrorFileName($fileName);            
        
            $new_error_data = $this->restructureErrorData($error_data, $single_error_data);

            $error_file_url = $this->createCsvFileToS3(UPDATE_USER_PATH, '/error/', $fileName, $fields_arr, $new_error_data);
            Log::channel($format_type)->info('URL error file : ' . $error_file_url);

            $total_error = count($error_data) + count($single_error_data);

            $jobs = [];
            $jobs['type'] = 'Update Customer';
            $jobs['total_error'] = $total_error;
            $jobs['file_url'] = $error_file_url;
            $jobs['error_desc'] = $error_desc;

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
            Mail::to($mail_to)->cc($mail_cc)->send(new MailJobsCreated($jobs));

        }

        // if(count($success_data) > 0){
            // Move archieve file
            $this->moveArcheiveFile($file_path);
        // }

        return count($success_data);
        
    }

    public function getCsvEvent($file_path, $contents){

        $format_type = 'Event';
        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);
        // Log::info($lines);
        // exit;
        $array = array();

        $fields = [];
        $values = [];
        $row = 0;

        // get data
        foreach ($lines as $line) {
            if(!empty($line)){
                if($row == 0){
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                }else{
                    // actual values
                    $values[] = str_getcsv($line);
                }
                $row++;
            }
        }

        Log::channel($format_type)->info('total events : '. count($values));
        // Log::info($fields);
        // exit;
        $success_data = [];
        $error_data = [];
        $single_error_data = [];
        $error_desc = '';
        $error_desc_item = '';

        // re format data
        $cnt_values = count($values);
        $cnt_fields = count($fields);

        $res = ['api_key' => BRAZE_API_KEY, 'events' => []];

        for($i = 0; $i < $cnt_values; $i++){

            $item = [];
            $item['properties'] = [];
            for($j = 0; $j <$cnt_fields; $j++){
                if(in_array($fields[$j], $fields_arr)){

                    $field_value = trim($values[$i][$j]);
                    if(!empty($field_value) && $field_value != $this->check_na_value){
                        $field_name = $fields[$j];

                        if($field_name == 'project_id' || $field_name == 'project_name' || $field_name == 'lead_type'){
                            
                            $item['properties'][$field_name] = $field_value;  

                        }else{

                            $item[$field_name] = $field_value; 
                               
                        }
                    }
                    
                }
            }
            
            $res['events'][] = $item;

            // exit;
            if( ($i > 0 && ($i%50 == 0)) || ($i == ($cnt_values - 1))){

                Log::channel($format_type)->info('Loop send : '. $i);
                
                $response_data = [];
                try{

                    // Log::info(json_encode($res));
                    $response_data = clientPostRequest(BRAZE_URL_EVENT, ($res));
                    Log::channel($format_type)->info(json_encode($response_data));

                    // check status            
                    if($response_data->events_processed){
                        // Keep success
                        $success_data[] = $res['events'];
                        if(isset($response_data->errors)){
                            foreach ($response_data->errors as $err_k => $err_v) {
                                $error_desc_item = $err_v->type;
                                $error_index = $err_v->index;
                                Log::channel($format_type)->error($error_desc_item);
                                Log::channel($format_type)->error($res['events'][$error_index]);

                                $origin_data_index = ($i - (count($res['events']) - $error_index)) + 1;
                                $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                            }
                        }

                    }else{
                        // Keep error
                        $error_data[] = $res['events'];
                        Log::channel($format_type)->error('-------------ERROR DATA-------------');
                        Log::channel($format_type)->error(json_encode($res));
                        // Log::error(json_encode($response_data));

                        if(isset($response_data->errors)){
                            foreach ($response_data->errors as $err_k => $err_v) {
                                $error_desc_item = $err_v->type;
                                $error_index = $err_v->index;
                                Log::channel($format_type)->error($error_desc_item);
                                Log::channel($format_type)->error($res['events'][$error_index]);

                                $origin_data_index = ($i - (count($res['events']) - $error_index)) + 1;
                                $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                            }
                        }
                    }

                }catch(\Exception $e){
                    // $error_data[] = $res['events'];
                    Log::channel($format_type)->error($e->getMessage());
                    Log::channel($format_type)->error(json_encode($res['events']));
                    $error_desc = $e->getMessage();

                    $cnt_error = 0;
                    foreach ($res['events'] as $err_k => $err_v) {
                        
                        $origin_data_index = ($i - (count($res['events']) - $cnt_error)) + 1;
                        $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                        $cnt_error++;
                    }
                }

                // sleep(1);
                usleep(25000);

                $res = ['api_key' => BRAZE_API_KEY, 'events' => []];

            }

        }

        Log::channel($format_type)->info('Total success : ' . count($success_data));
        Log::channel($format_type)->info('Total error : ' . count($error_data));
        Log::channel($format_type)->info('Total data error : ' . count($single_error_data));

        // Send email when error
        if(count($error_data) > 0 || count($single_error_data) > 0){

            // upload error file to S3
            $now = date('YmdHis');
            $fileName = $this->getOnlyFilename($file_path);
            
            $fileName = $this->createErrorFileName($fileName);            

            $new_error_data = $this->restructureErrorData($error_data, $single_error_data);

            $error_file_url = $this->createCsvFileToS3(EVENT_PATH, '/error/', $fileName, $fields_arr, $new_error_data);
            Log::channel($format_type)->info('URL error file : ' . $error_file_url);

            $total_error = count($error_data) + count($single_error_data);

            $jobs = [];
            $jobs['type'] = 'Event';
            $jobs['total_error'] = $total_error;
            $jobs['file_url'] = $error_file_url;
            $jobs['error_desc'] = $error_desc;

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
            Mail::to($mail_to)->cc($mail_cc)->send(new MailJobsCreated($jobs));

        }

        // if(count($success_data) > 0){
            // Move archieve file
            $this->moveArcheiveFile($file_path);
        // }
        
        return count($success_data);

    }

    public function getCsvPurchase($file_path, $contents){

        $format_type = 'Purchase';
        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);
        // Log::info($lines);
        // exit;
        $array = array();

        $fields = [];
        $values = [];
        $row = 0;

        // get data
        foreach ($lines as $line) {
            if(!empty($line)){
                if($row == 0){
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                }else{
                    // actual values
                    $values[] = str_getcsv($line);
                }
                $row++;
            }
        }

        Log::channel($format_type)->info('total purchases : '. count($values));

        // Log::info($fields);
        // exit;
        $success_data = [];
        $error_data = [];
        $single_error_data = [];
        $error_desc = '';
        $error_desc_item = '';

        // re format data

        $cnt_values = count($values);
        $cnt_fields = count($fields);

        $res = ['api_key' => BRAZE_API_KEY, 'purchases' => []];

        for($i = 0; $i < $cnt_values; $i++){

            $item = [];
            for($j = 0; $j < $cnt_fields; $j++){
                if(in_array($fields[$j], $fields_arr)){

                    $field_value = trim($values[$i][$j]);
                    if(!empty($field_value) && $field_value != $this->check_na_value){
                        $field_name = $fields[$j];
                        if($field_name == 'unit_name' || $field_name == 'project_name' || $field_name == 'data_property'){

                            $item['properties'][$field_name] = $field_value;  

                        }else{

                            if($field_name == 'price'){
                                $item[$field_name] = floatval(number_format($field_value, 2, ".", ""));
                            }else if($field_name == 'quantity'){
                                $item[$field_name] = intval($field_value);
                            }else{
                                $item[$field_name] = $field_value;
                            }
                             

                        }
                          
                    }
                    
                }

                // if($i == 2){
                //     $item['currency'] = 'N/A';
                // }

            }

            $res['purchases'][] = $item;

            if( ($i > 0 && ($i%50 == 0)) || ($i == ($cnt_values - 1))){

                $response_data = [];

                try{

                    // Log::info(json_encode($res));
                    $response_data = clientPostRequest(BRAZE_URL_PURCHASE, ($res));
                    Log::channel($format_type)->info(json_encode($response_data));

                    // check status            
                    if($response_data->purchases_processed){
                        // Keep success
                        $success_data[] = $res['purchases'];

                        if(isset($response_data->errors)){
                            foreach ($response_data->errors as $err_k => $err_v) {
                                $error_desc_item = $err_v->type;
                                $error_index = $err_v->index;
                                Log::channel($format_type)->error($error_desc_item);
                                Log::channel($format_type)->error($res['purchases'][$error_index]);

                                $origin_data_index = ($i - (count($res['purchases']) - $error_index)) + 1;
                                $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                            }
                        }

                    }else{
                        // Keep error
                        $error_data[] = $res['purchases'];
                        Log::channel($format_type)->error('-------------ERROR DATA-------------');
                        Log::channel($format_type)->error(json_encode($res));
                        // Log::channel($format_type)->error(json_encode($response_data));
                        if(isset($response_data->errors)){
                            foreach ($response_data->errors as $err_k => $err_v) {
                                $error_desc_item = $err_v->type;
                                $error_index = $err_v->index;
                                Log::channel($format_type)->error($error_desc_item);
                                Log::channel($format_type)->error($res['purchases'][$error_index]);

                                $origin_data_index = ($i - (count($res['purchases']) - $error_index)) + 1;
                                $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                            }
                        }
                    }

                }catch(\Exception $e){
                    // $error_data[] = $res['purchases'];
                    Log::channel($format_type)->error('Exception Case :: ' . $e->getMessage());
                    Log::channel($format_type)->error(json_encode($res['purchases']));
                    $error_desc = $e->getMessage();

                    $cnt_error = 0;
                    foreach ($res['purchases'] as $err_k => $err_v) {
                        
                        $origin_data_index = ($i - (count($res['purchases']) - $cnt_error)) + 1;
                        $single_error_data[] = $values[$origin_data_index]; //$res['attributes'][$error_index];
                        $cnt_error++;
                    }
                    
                }

                // sleep(1);
                usleep(25000);

                $res = ['api_key' => BRAZE_API_KEY, 'purchases' => []];
            }
        }

        Log::channel($format_type)->info('Total success : ' . count($success_data));
        Log::channel($format_type)->info('Total error : ' . count($error_data));
        Log::channel($format_type)->info('Total data error : ' . count($single_error_data));

        // Send email when error
        if(count($error_data) > 0 || count($single_error_data) > 0){

            // upload error file to S3
            $now = date('YmdHis');
            $fileName = $this->getOnlyFilename($file_path);
        
            $fileName = $this->createErrorFileName($fileName);            
        
            $new_error_data = $this->restructureErrorData($error_data, $single_error_data);
            
            $error_file_url = $this->createCsvFileToS3(PURCHASE_PATH, '/error/', $fileName, $fields_arr, $new_error_data);
            Log::info('URL error file : ' . $error_file_url);

            $total_error = count($error_data) + count($single_error_data);

            $jobs = [];
            $jobs['type'] = 'Purchase';
            $jobs['total_error'] = $total_error;
            $jobs['file_url'] = $error_file_url;
            $jobs['error_desc'] = $error_desc;
            
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
            Mail::to($mail_to)->cc($mail_cc)->send(new MailJobsCreated($jobs));

        }

        // if(count($success_data) > 0){
            // Move archieve file
            $this->moveArcheiveFile($file_path);
        // }
        
        return count($success_data);
    }

    public function getCsvDeleteCustomer($file_path, $contents){

        $format_type = 'DeleteCustomer';

        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);
        // Log::info($lines);
        // exit;
        $array = array();

        $fields = [];
        $values = [];
        $row = 0;

        // get data
        foreach ($lines as $line) {
            if(!empty($line)){
                if($row == 0){
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                }else{
                    // actual values
                    if(!empty($line)){
                        $values[] = str_getcsv($line)[0];    
                    }
                }
                $row++;
                // if($row == 2){
                //     break;
                // }
            }
        }
        Log::channel($format_type)->info('total delete users : '. count($values));
        // print_r($values);
        // Log::info($fields);
        // exit;
        $success_data = [];
        $error_data = [];
        // $item = [];

        // re format data
        // for($i = 0; $i < count($values); $i++){

            
        //     for($j = 0; $j <count($fields); $j++){
        //         if(in_array($fields[$j], $fields_arr)){

        //             $field_value = trim($values[$i][$j]);
        //             if(!empty($field_value) && $field_value != $this->check_na_value){
        //                 $item[] = $field_value;  
        //             }
                    
        //         }
        //     }
        // }

        $res = ['api_key' => BRAZE_API_KEY,
                'external_ids' => [
                        $values
                    ]
                ];

        try{

            // Log::info(json_encode($res));
            $response_data = clientPostRequest(BRAZE_URL_DEL_USER, ($res));
            Log::channel($format_type)->info(json_encode($response_data));

            // check status            
            if($response_data->deleted){
                // Keep success
                $success_data = $values;
            }else{
                // Keep error
                $error_data = $values;
                Log::channel($format_type)->error('-------------ERROR DATA-------------');
                Log::channel($format_type)->error(json_encode($res));
                // Log::channel($format_type)->error(json_encode($response_data));
            }

        }catch(\Exception $e){
            $error_data = $values;
            Log::channel($format_type)->error($e->getMessage());
            $error_desc = $e->getMessage();
        }

        Log::channel($format_type)->info('Total success : ' . count($success_data));
        Log::channel($format_type)->info('Total error : ' . count($error_data));

        // Send email when error
        if(count($error_data) > 0){

            // upload error file to S3
            $now = date('YmdHis');
            $fileName = $this->getOnlyFilename($file_path);
            
            $fileName = $this->createErrorFileName($fileName);            
        
            $error_file_url = $this->createCsvFileToS3(DELETE_CUSTOMER_PATH, '/error/', $fileName, $fields_arr, $error_data);
            Log::info('URL error file : ' . $error_file_url);


            $jobs = [];
            $jobs['type'] = 'Delete Customer';
            $jobs['total_error'] = count($error_data);
            $jobs['file_url'] = $error_file_url;
            $jobs['error_desc'] = $error_desc;

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
            Mail::to($mail_to)->cc($mail_cc)->send(new MailJobsCreated($jobs));

        }

        // if(count($success_data) > 0){
            // Move archieve file
            $this->moveArcheiveFile($file_path);
        // }
        
        return count($success_data);

    }

    public function getCsvSubMail($file_path, $contents){

        $format_type = 'SubMail';

        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);
        // Log::info($lines);
        // exit;
        $array = array();

        $fields = [];
        $values = [];
        $row = 0;

        // get data
        foreach ($lines as $line) {
            if(!empty($line)){
                if($row == 0){
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                }else{
                    // actual values
                    $values[] = str_getcsv($line);
                }
                $row++;
            }
        }

        Log::channel($format_type)->info('total mail status : '. count($values));

        // Log::info($fields);
        // exit;
        $success_data = [];
        $error_data = [];
        $error_desc = '';

        // re format data
        for($i = 0; $i < count($values); $i++){

            $item = ['api_key' => BRAZE_API_KEY];
            for($j = 0; $j <count($fields); $j++){
                if(in_array($fields[$j], $fields_arr)){

                    $field_value = trim($values[$i][$j]);
                    if(!empty($field_value) && $field_value != $this->check_na_value){
                        $field_name = $fields[$j];
                        $item[$field_name] = $field_value;
                    }
                }
            }

            $res = $item;

            // if($res['subscription_state'] == 'opted-in'){
            // Log::info(json_encode($res));//exit;

            try{

                $response_data = clientPostRequest(BRAZE_URL_SUB_MAIL, ($res));
                Log::channel($format_type)->info(json_encode($response_data));

                // check status            
                if($response_data->message == 'success'){
                    // Keep success
                    $success_data[] = $values[$i];
                }else{
                    // Keep error
                    $error_data[] = $values[$i];
                    Log::channel($format_type)->error('-------------ERROR DATA-------------');
                    Log::channel($format_type)->error(json_encode($res));
                    // Log::channel($format_type)->error(json_encode($response_data));
                }

            }catch(\Exception $e){
                $error_data[] = $values[$i];
                Log::channel($format_type)->error($e->getMessage());
                Log::channel($format_type)->error(json_encode($res));
                $error_desc = $e->getMessage();
            }
            // }
            // exit;

            // sleep(1);
            usleep(25000);
            
        }

        Log::channel($format_type)->info('Total success : ' . count($success_data));
        Log::channel($format_type)->info('Total error : ' . count($error_data));

        // Send email when error
        if(count($error_data) > 0){

            // upload error file to S3
            $now = date('YmdHis');
            $fileName = $this->getOnlyFilename($file_path);

            $fileName = $this->createErrorFileName($fileName);            
        
            $error_file_url = $this->createCsvFileToS3(SUB_EMAIL_PATH, '/error/', $fileName, $fields_arr, $error_data);
            Log::channel($format_type)->info('URL error file : ' . $error_file_url);


            $jobs = [];
            $jobs['type'] = 'Sub Mail';
            $jobs['total_error'] = count($error_data);
            $jobs['file_url'] = $error_file_url;
            $jobs['error_desc'] = $error_desc;

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
            Mail::to($mail_to)->cc($mail_cc)->send(new MailJobsCreated($jobs));

        }

        // if(count($success_data) > 0){
            // Move archieve file
            $this->moveArcheiveFile($file_path);
        // }

        return count($success_data);
        
    }

    public function getCampaignData(){
        $req = ['api_key' => BRAZE_API_KEY];
        $response_data = clientGetRequest(BRAZE_URL_CAMPAIGN . '?api_key=' . BRAZE_API_KEY . '&campaign_id=MDM-0010o00002QPpq4AAD', $req);
        Log::info(json_encode($response_data));
    }

    public function getCanvasData(){

    }

    private function createCsvFileToS3($path, $path_type, $file_name, $fields, $values){

    	$storageInstance = Storage::disk('s3');
    	$file_path = $path . $path_type . $file_name;
        $putFileOnStorage = $storageInstance->put($file_path, '');
        $fileContent = $storageInstance->get($file_path);
        $writer = Writer::createFromString($fileContent, 'w');
        $writer->insertOne($fields);
        $writer->insertAll($values);
        $csvContent = $writer->getContent();
        $putFileOnStorage = $storageInstance->put($file_path, $csvContent);

        return $storageInstance->url($file_path);

    }

    private function moveArcheiveFile($file_path){

        $file_arr = explode('/', $file_path);
        $file_name = $file_arr[count($file_arr) - 1];
        unset($file_arr[count($file_arr) - 1]);
        $path = implode('/', $file_arr);
        $move_to_path = $path. '/success/' . $file_name;
        Storage::disk('s3')->move($file_path, $move_to_path);

    }

    private function getOnlyFilename($file_path){
        $file_arr = explode('/', $file_path);
        return $file_arr[count($file_arr) - 1];
    }

    private function createErrorFileName($origin_file_name){
        $file_arr = explode('.', $origin_file_name);
        $file_ext = $file_arr[1];
        $file_name = $file_arr[0] . '_error';
        return $file_name . '.' . $file_ext;
    }

    

    private function restructureErrorData($error_data, $single_error_data = []){
        $new_error_data = [];

        foreach ($error_data as $key => $value) {
            foreach ($value as $key_1 => $value_1) {
                $new_error_data[] = $value_1;
            }

        }

        foreach ($single_error_data as $key => $value) {
            $new_error_data[] = $value;
        }

        return $new_error_data;
    }

}
