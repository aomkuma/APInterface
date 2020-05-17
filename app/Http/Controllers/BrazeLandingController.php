<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use League\Flysystem\AwsS3v2\AwsS3Adapter;

use Storage;

use Avro\DataIO\DataIO;
use Avro\DataIO\DataIOReader;
use Avro\DataIO\DataIOWriter;
use Avro\Datum\IODatumReader;
use Avro\Datum\IODatumWriter;
use Avro\IO\StringIO;
use Avro\Schema\Schema;

use Illuminate\Support\Facades\Log;

use League\Csv\Writer;

class BrazeLandingController extends Controller
{
    //
    public function processAVRO($path, $process_date){
    	// echo $path;
    	$format_type = 'Landing';

    	$read_io = new StringIO(Storage::disk('s3')->get($path));
    	$data_reader = new DataIOReader($read_io, new IODatumReader());
		
		// Log::info('Total data : '. count($data_reader->data()));
		// Read each datum
		$cnt = 0;
		$labels = [];
		$values = [];
		foreach ($data_reader->data() as $datum) {
		    
		    $data = [];
		    if($cnt == 0){
		    	foreach ($datum as $key => $value) {
		    		if($key == 'properties'){
			    		// $value_json_decode = json_decode($value, true);
			    		// if($value_json_decode){
			    		// 	foreach ($value_json_decode as $key => $value) {
			    		// 		$labels[] = $key;
			    		// 		$data[] = $value;
			    		// 	}
			    		// }
			    	}else{
			    		$labels[] = $key;
			    		$data[] = $value;
			    	}
		    	}
		    }else{
		    	foreach ($datum as $key => $value) {
		    		if($key == 'properties'){
			    		// $value_json_decode = json_decode($value, true);
			    		// if($value_json_decode){
			    		// 	foreach ($value_json_decode as $key => $value) {
			    		// 		$data[] = $value;
			    		// 	}
			    		// }
			    	}else{
			    		$data[] = $value;
			    	}
		    	}
		    }

		    $values[] = $data;
		    $cnt++;
		}
		// print_r($labels);
		// print_r($values);
		$data_reader->close();

		$file_result = $this->createDestinationFilename($path);
		$file_name = $file_result['file_name'];
		$path_name = $file_result['path_name'];

		$error_file_url = $this->createCsvFileToS3($path_name, $file_name, $process_date, $labels, $values);
		Log::channel($format_type)->info("Total data : " . $cnt);
		return $cnt;
		// exit;
    }

    private function createCsvFileToS3($path_name, $file_name, $process_date, $fields, $values){

    	$storageInstance = Storage::disk('local');
    	$base_file_path = 'Braze/Landing/EventData/' . $path_name . '/' . $process_date . '__' . $file_name;
        $putFileOnStorage = $storageInstance->put($base_file_path, '');
        $fileContent = $storageInstance->get($base_file_path);
        $writer = Writer::createFromString($fileContent, 'w');
        $writer->insertOne($fields);
        $writer->insertAll($values);
        $csvContent = $writer->getContent();
        $putFileOnStorage = $storageInstance->put($base_file_path, $csvContent);

        return $storageInstance->url($base_file_path);

    }

    private function createDestinationFilename($file_path){
        $file_arr = explode('/', $file_path);
        $file_name = $file_arr[count($file_arr) - 1];

        $path_name = $file_arr[4];
        $path_name = str_replace('event_type=', '', $path_name);

        return ['file_name' => str_replace('.avro', '.csv', $file_name), 'path_name' => $path_name];
    }
}
