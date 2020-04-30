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
use App\Mail\FacebookOfflineConvertionErrorMail;

class FacebookController extends Controller {

    private $check_na_value = 'N/A';

    public function getCsvBook($file_path, $contents, $eventname) {


        $eventsetID = 'https://graph.facebook.com/v6.0/' . OFFLINE_EVENT_SET_ID[$eventname] . '/events';

        Log::info('event id. : ' . OFFLINE_EVENT_SET_ID[$eventname]);
        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);
//         Log::info($lines);
        $array = array();
        $totalprocessed = 0;
        $api_field_list = ['phone', 'email'];
        $fields = [];
        $values = [];
        $row = 0;
        $lot = 1;
        $numberoflot = 1500;

        // get data
        foreach ($lines as $line) {
            if (!empty($line)) {
                if ($row == 0) {
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                } else {
                    // actual values
                    $values[] = str_getcsv($line);
                }
                $row++;
            }
        }

        if ($numberoflot > count($values)) {
            $numberoflot = count($values) - 1;
        }
        $cnt_values = count($values);
        $cnt_fields = count($fields);
        $success_data = [];
        $error_data = [];

        // re format data
        $res = ['access_token' => FACEBOOKTOKEN,
            'upload_tag' => 'store_data',
            'data' => []];

        Log::info('Event ' . $eventname . ' total : ' . count($values));
        for ($i = 0; $i < $cnt_values; $i++) {
            $data = [];
            $match_keys = [];
            $event_time = 0;

            $currency = 'THB';
            $value = 0.0;
            $custom_data = [];

            for ($j = 0; $j < $cnt_fields; $j++) {

                if (in_array($fields[$j], $api_field_list)) {

                    $field_value = trim($values[$i][$j]);
                    if (!empty($field_value) && $field_value != $this->check_na_value) {
                        $field_name = $fields[$j];

                        $match_keys[$field_name] = hash("sha256", $field_value);
                    }
                } else if ($fields[$j] == 'timeStamp') {
                    $field_value = trim($values[$i][$j]);
                    if (!empty($field_value) && $field_value != $this->check_na_value) {
                        $event_time = strtotime($field_value);
                    }
                } else if ($fields[$j] == 'currency') {
                    $field_value = trim($values[$i][$j]);
                    if (!empty($field_value) && $field_value != $this->check_na_value) {
                        $currency = $field_value;
                    }
                } else if ($fields[$j] == 'value') {
                    $field_value = trim($values[$i][$j]);
                    if (!empty($field_value) && $field_value != $this->check_na_value) {
                        $value = $field_value;
                    }
                } else {
                    $field_value = trim($values[$i][$j]);
                    if (!empty($field_value) && $field_value != $this->check_na_value) {
                        $field_name = $fields[$j];
                        $custom_data[$field_name] = $field_value;
                    }
                }
            }
            $data['match_keys'] = json_encode($match_keys);
            $data['event_time'] = $event_time;
            $data['event_name'] = $eventname;
            $data['currency'] = $currency;
            $data['value'] = $value;
            $data['custom_data'] = json_encode($custom_data);

            $data['contents'] = [];
            $res['data'][] = $data;

            if (($i > 0 && ($i % $numberoflot == 0)) || ($i == ($cnt_values - 1))) {
                Log::info('Lot no. : ' . $lot);
//                Log::info('data : '. json_encode($res['data']));
                try {
//                Log::info(json_encode($res));
                    $response_data = clientPostRequest($eventsetID, ($res));
                    Log::info(json_encode($response_data));
                    // check status            
                    $totalprocessed += $response_data->num_processed_entries;
                } catch (\Exception $e) {
                    Log::error('-------------ERROR DATA-------------');
                    $error_desc = $e->getMessage();
                    $startloop = $i - $numberoflot;


                    //$startloop . ' - ' . $i
                    for ($x = $startloop; $x <= $i; $x++) {

                        $error_data[] = $values[$x];
                    }
                    //  Log::info('error no.' . count($error_data));
                    Log::error($e->getMessage());
                }

                $res = ['access_token' => FACEBOOKTOKEN,
                    'upload_tag' => 'store_data',
                    'data' => []];
                $lot++;
            }
        }
//        Log::info('total =' . $totalprocessed);
//
        Log::info('Total success : ' . $totalprocessed);
        Log::info('Total error : ' . count($error_data));
//
//        // Send email when error
        if (count($error_data) > 0) {

            // upload error file to S3
            $now = date('YmdHis');
            // $fileName = "New_Cus_$now.csv";
            $fileName = $this->getOnlyFilename($file_path);

            $error_file_url = $this->createCsvFileToS3(FACEBOOKOFFLINE_BOOK_PATH, '/error/', $fileName, $fields_arr, $error_data);
            Log::info('URL error file : ' . $error_file_url);


            $detail = [];
            $detail['type'] = 'BOOK';
            $detail['total_error'] = count($error_data);
            $detail['file_url'] = $error_file_url;
            $detail['error_desc'] = $error_desc;

            $list_mail_recv = explode("||", SEND_MAIL_TO);
            $cnt_mail = 0;
            $mail_to = '';
            $mail_cc = [];
            foreach ($list_mail_recv as $key => $value) {
                if ($cnt_mail == 0) {
                    $mail_to = $value;
                } else {
                    $mail_cc[] = $value;
                }
                $cnt_mail++;
            }
            Mail::to($mail_to)->cc($mail_cc)->send(new FacebookOfflineConvertionErrorMail($detail));
        }
//
//        // if(count($success_data) > 0){
//        // Move archieve file
        $this->moveArcheiveFile($file_path);
//        // }
//
        return $totalprocessed;
    }

    public function getCsvWalk($file_path, $contents, $eventname) {


        $eventsetID = 'https://graph.facebook.com/v6.0/' . OFFLINE_EVENT_SET_ID[$eventname] . '/events';


        // $lines = explode(PHP_EOL, $contents);
        $lines = explode("\n", $contents);

        $array = array();
        $totalprocessed = 0;
        $api_field_list = ['phone', 'email'];
        $fields = [];
        $values = [];
        $row = 0;
        $lot = 1;
        $numberoflot = 1500;

        Log::info(' : ' . $numberoflot);
        // get data
        foreach ($lines as $line) {
            if (!empty($line)) {
                if ($row == 0) {
                    // colume
                    $fields_arr = str_getcsv($line);
                    $fields = str_getcsv($line);
                } else {
                    // actual values
                    $values[] = str_getcsv($line);
                }
                $row++;
            }
        }
        if ($numberoflot > count($values)) {
            $numberoflot = count($values) - 1;
        }

        $cnt_values = count($values);
        $cnt_fields = count($fields);
        $success_data = [];
        $error_data = [];

        // re format data
        $res = ['access_token' => FACEBOOKTOKEN,
            'upload_tag' => 'store_data',
            'data' => []];

        Log::info('Event ' . $eventname . ' total : ' . count($values));
        for ($i = 0; $i < $cnt_values; $i++) {
            $data = [];
            $match_keys = [];
            $event_time = 0;

            $custom_data = [];

            for ($j = 0; $j < $cnt_fields; $j++) {

                if (in_array($fields[$j], $api_field_list)) {

                    $field_value = trim($values[$i][$j]);
                    if (!empty($field_value) && $field_value != $this->check_na_value) {
                        $field_name = $fields[$j];

                        $match_keys[$field_name] = hash("sha256", $field_value);
                    }
                } else if ($fields[$j] == 'timeStamp') {
                    $field_value = trim($values[$i][$j]);
                    if (!empty($field_value) && $field_value != $this->check_na_value) {
                        $event_time = strtotime($field_value);
                    }
                } else {
                    $field_value = trim($values[$i][$j]);
                    if (!empty($field_value) && $field_value != $this->check_na_value) {
                        $field_name = $fields[$j];
                        $custom_data[$field_name] = $field_value;
                    }
                }
            }
            $data['match_keys'] = json_encode($match_keys);
            $data['event_time'] = $event_time;
            $data['event_name'] = $eventname;
            $data['custom_data'] = json_encode($custom_data);
            $data['contents'] = [];
            $res['data'][] = $data;

            if (($i > 0 && ($i % $numberoflot == 0)) || ($i == ($cnt_values - 1))) {
                Log::info('Lot no. : ' . $lot);
//                Log::info('data : '. json_encode($res['data']));
                try {
//                Log::info(json_encode($res));
                    $response_data = clientPostRequest($eventsetID, ($res));
                    Log::info(json_encode($response_data));
                    // check status            
                    $totalprocessed += $response_data->num_processed_entries;
                } catch (\Exception $e) {
//                    print_r($e);
//                    exit;
                    $error_desc = $e->getMessage();
                    $startloop = $i - $numberoflot;
                    //$startloop . ' - ' . $i
                    for ($x = $startloop; $x <= $i; $x++) {
                        $error_data[] = $values[$x];
                    }
                    //    Log::info('error no.' . count($error_data));
                    Log::error($e->getMessage());
//                    Log::error(json_encode($error_data));
                }

                $res = ['access_token' => FACEBOOKTOKEN,
                    'upload_tag' => 'store_data',
                    'data' => []];
                $lot++;
            }
        }

        Log::info('Total success : ' . $totalprocessed);
        Log::info('Total error : ' . count($error_data));
//        // Send email when error
        if (count($error_data) > 0) {

            // upload error file to S3
            $now = date('YmdHis');
            // $fileName = "New_Cus_$now.csv";
            $fileName = $this->getOnlyFilename($file_path);
            $error_file_url = $this->createCsvFileToS3(FACEBOOKOFFLINE_WALK_PATH, '/error/', $fileName, $fields_arr, $error_data);
            Log::info('URL error file : ' . $error_file_url);
            $detail = [];
            $detail['type'] = 'WALK';
            $detail['total_error'] = count($error_data);
            $detail['file_url'] = $error_file_url;
            $detail['error_desc'] = $error_desc;

            $list_mail_recv = explode("||", SEND_MAIL_TO);
            $cnt_mail = 0;
            $mail_to = '';
            $mail_cc = [];
            foreach ($list_mail_recv as $key => $value) {
                if ($cnt_mail == 0) {
                    $mail_to = $value;
                } else {
                    $mail_cc[] = $value;
                }
                $cnt_mail++;
            }
            Mail::to($mail_to)->cc($mail_cc)->send(new FacebookOfflineConvertionErrorMail($detail));
        }
//

//         Move archieve file
        $this->moveArcheiveFile($file_path);

        return $totalprocessed;
    }

    private function createCsvFileToS3($path, $path_type, $file_name, $fields, $values) {

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

    private function getOnlyFilename($file_path) {
        $file_arr = explode('/', $file_path);
        return $file_arr[count($file_arr) - 1];
    }

    private function moveArcheiveFile($file_path) {

        $file_arr = explode('/', $file_path);
        $file_name = $file_arr[count($file_arr) - 1];
        unset($file_arr[count($file_arr) - 1]);
        $path = implode('/', $file_arr);
        $move_to_path = $path . '/success/' . $file_name;
        Storage::disk('s3')->move($file_path, $move_to_path);
    }

}
