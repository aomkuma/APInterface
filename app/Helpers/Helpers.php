<?php

	use GuzzleHttp\Client as GuzzleClient;
	use GuzzleHttp\HandlerStack;
	use GuzzleHttp\Middleware;
	use Psr\Http\Message\RequestInterface;

	function clientPostRequest($url, $obj){

		$stack = HandlerStack::create();
		// my middleware
		$stack->push(Middleware::mapRequest(function (RequestInterface $request) {
		    $contentsRequest = (string) $request->getBody();
		    //var_dump($contentsRequest);
		    // print_r($contentsRequest);
		    return $request;
		}));

		$headers = ['Content-Type' => 'application/json'];
		$client = new GuzzleClient([
	        'headers' => $headers,
	        'handler' => $stack
	    ]);

		

	    $r = $client->request('POST', $url, 
	        [GuzzleHttp\RequestOptions::JSON => $obj]
	    );

	    $body = $r->getBody();
	    $result = json_decode($body);

	    return $result;
	}

	function clientGetRequest($url, $obj){

		$headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
		$client = new GuzzleClient([
	        'headers' => $headers
	    ]);

	    $r = $client->request('GET', $url, 
	        [GuzzleHttp\RequestOptions::FORM_PARAMS => $obj]
	    );

	    
	    $body = $r->getBody();
	    $result = json_decode($body);

	    return $result;
	}