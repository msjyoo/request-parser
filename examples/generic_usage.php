<?php

require_once(__DIR__.'/../vendor/autoload.php');

use sekjun9878\RequestParser\RequestParser;
use sekjun9878\RequestParser\Request;

$header_str = "GET /democracy/init HTTP/1.1\r\nUser-Agent: The Illuminati/2.0\r\nContent-Length: 10\r\n\r\nOver 9000!";

$requestParser = new RequestParser;

$requestParser->addData($header_str);

$request = Request::create($requestParser->exportRequestState());

var_dump($request->getHeaders());
var_dump($request->getBody());