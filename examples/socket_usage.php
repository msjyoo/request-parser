<?php

require_once(__DIR__.'/../vendor/autoload.php');

use sekjun9878\RequestParser\RequestParser;
use sekjun9878\RequestParser\Request;

$requestParser = new RequestParser();

while(!$requestParser->isFullyRead())
{
	$data = fread($this->connectionSocket, RequestParser::READ_SIZE);

	if($data === false or $data == "") //Invalid empty HTTP Request
	{
		//Kill the connection
	}

	$requestParser->addData($data);
}

$this->request = Request::create($requestParser->exportRequestState());