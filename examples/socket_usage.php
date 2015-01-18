<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use sekjun9878\RequestParser\RequestParser;
use sekjun9878\RequestParser\Request;

$address = "127.0.0.1";
$port = 8080;

$listenSocket = stream_socket_server("tcp://{$address}:{$port}");

$connectionSocket = stream_socket_accept($listenSocket);

$requestParser = new RequestParser();

$error = false;

while(!$requestParser->isFullyRead())
{
	$data = fread($connectionSocket, RequestParser::READ_SIZE);

	if(!$requestParser->addData($data)) //Invalid empty HTTP Request
	{
		fclose($connectionSocket);
		fclose($listenSocket);
		exit(1);
	}
}

if($requestParser->getStatus() !== RequestParser::OK)
{
	switch($requestParser->getStatus())
	{
		case RequestParser::BAD_REQUEST:
			fwrite($connectionSocket, "HTTP/1.1 400 Bad Request\r\nContent-Type: text/html; charset=utf-8\r\nContent-Length: " . mb_strlen("<html><head><title>400 Bad Request</title></head><body><h1>400 Bad Request</h1></body></html>") . "\r\n\r\n" . "<html><head><title>400 Bad Request</title></head><body><h1>400 Bad Request</h1></body></html>");
			fclose($connectionSocket);
			fclose($listenSocket);
			exit(1);
			break;
		case RequestParser::INTERNAL_SERVER_ERROR:
		default:
			fwrite($connectionSocket, "HTTP/1.1 500 Internal Server Error\r\nContent-Type: text/html; charset=utf-8\r\nContent-Length: " . mb_strlen("<html><head><title>500 Internal Server Error</title></head><body><h1>500 Internal Server Error</h1></body></html>") . "\r\n\r\n" . "<html><head><title>500 Internal Server Error</title></head><body><h1>500 Internal Server Error</h1></body></html>");
			fclose($connectionSocket);
			fclose($listenSocket);
			exit(1);
			break;
	}
}

$request = Request::create($requestParser->exportRequestState());

var_dump($request);

fwrite($connectionSocket, "HTTP/1.1 200 OK\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Length: ".mb_strlen("Hello World!")."\r\n\r\nHello World!");
fclose($connectionSocket);
fclose($listenSocket);