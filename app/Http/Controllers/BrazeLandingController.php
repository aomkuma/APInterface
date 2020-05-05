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

class BrazeLandingController extends Controller
{
    //
    public function processAVRO($path){
    	// echo $path;
    	// $tmp_f = tmpfile();
    	
    	// fwrite($tmp_f, Storage::disk('s3')->get($path));

  //   	$tmpfname = tempnam("/storage", "FOO");

		// $handle = fopen($tmpfname, "w");
		// fwrite($handle, Storage::disk('s3')->get($path));
    	// Log::info(Storage::disk('s3')->get($path));exit;
    	// echo Storage::disk('local')->get('dataexport.avro');exit;
    	// $data_reader = DataIO::open_file('storage/app/dataexport.avro');
    	$read_io = new StringIO(Storage::disk('s3')->get($path));
    	$data_reader = new DataIOReader($read_io, new IODatumReader());
		echo "from file:\n";
		// Read each datum
		foreach ($data_reader->data() as $datum) {
		    echo var_export($datum, true) . "\n";
		}
		$data_reader->close();
    }
}
