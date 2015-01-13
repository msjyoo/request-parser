<?php

namespace sekjun9878\RequestParser\Test;

use PHPUnit_Framework_TestCase;
use sekjun9878\RequestParser\Request;
use sekjun9878\RequestParser\RequestParser;
use sekjun9878\RequestParser\RequestState;

class RequestParserTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @param string $data
	 * @param array $expected
	 *
	 * @dataProvider providerNormalData
	 */
	public function testNormalData($data, $expected)
	{
		$requestParser = new RequestParser;

		$result = $requestParser->addData($data);

		$this->assertTrue($result);
		$this->assertTrue($requestParser->isFullyRead());
		$this->assertEquals(RequestParser::OK, $requestParser->getStatus());

		$exported = $requestParser->exportRequestState();

		$this->assertEquals(true, isset($exported['startTime']));
		$this->assertEquals(true, is_float($exported['startTime']));
		unset($exported['startTime']); // Unset StartTime because that value can't be provided from external source.

		$this->assertEquals($expected, $exported);
	}

	public function testEmptyData()
	{
		$requestParser = new RequestParser;

		$result = $requestParser->addData("");

		$this->assertFalse($result);
		$this->assertFalse($requestParser->isFullyRead());
		$this->assertEquals(RequestParser::NOT_SET, $requestParser->getStatus());

		$exported = $requestParser->exportRequestState();
		$requestState = new RequestState;

		$this->assertEquals(false, isset($exported['startTime']));

		$this->assertEquals($requestState->exportState(), $exported);
	}

	public function testFalseData()
	{
		$requestParser = new RequestParser;

		$result = $requestParser->addData(false);

		$this->assertFalse($result);
		$this->assertFalse($requestParser->isFullyRead());
		$this->assertEquals(RequestParser::NOT_SET, $requestParser->getStatus());

		$exported = $requestParser->exportRequestState();
		$requestState = new RequestState;

		$this->assertEquals(false, isset($exported['startTime']));

		$this->assertEquals($requestState->exportState(), $exported);
	}

	public function testDataWithHeaderLessThan4Characters()
	{
		$requestParser = new RequestParser;

		$result = $requestParser->addData("cat");

		$this->assertTrue($result);
		$this->assertTrue($requestParser->isFullyRead());
		$this->assertEquals(RequestParser::BAD_REQUEST, $requestParser->getStatus());

		$exported = $requestParser->exportRequestState();
		$requestState = new RequestState;
		$requestStateExported = $requestState->exportState();
		unset($requestStateExported['startTime']);

		$this->assertEquals(true, isset($exported['startTime']));
		$this->assertEquals(true, is_float($exported['startTime']));
		unset($exported['startTime']); // Unset StartTime because that value can't be provided from external source.

		$this->assertEquals($requestStateExported, $exported);
	}

	public function testDataWithNoEndingDoubleNewline()
	{
		$requestParser = new RequestParser;

		$result = $requestParser->addData("GET /democracy/init HTTP/1.1\r\nUser-Agent: The Illuminati/2.0\r\nContent-Length: 10");

		$this->assertTrue($result);
		$this->assertTrue($requestParser->isFullyRead());
		$this->assertEquals(RequestParser::BAD_REQUEST, $requestParser->getStatus());

		$exported = $requestParser->exportRequestState();
		$requestState = new RequestState;
		$requestStateExported = $requestState->exportState();
		unset($requestStateExported['startTime']);

		$this->assertEquals(true, isset($exported['startTime']));
		$this->assertEquals(true, is_float($exported['startTime']));
		unset($exported['startTime']); // Unset StartTime because that value can't be provided from external source.

		$this->assertEquals($requestStateExported, $exported);
	}

	public function testChunkedData()
	{
		$data = "GET /democracy/init HTTP/1.1\r\nUser-Agent: The Illuminati/2.0\r\nContent-Type: text/plain\r\nTransfer-Encoding: chunked\r\n\r\n1a; ignore-stuff-here\r\nabcdefghijklmnopqrstuvwxyz\r\n10\r\n1234567890abcdef\r\n0\r\n";

		$requestParser = new RequestParser;

		$result = $requestParser->addData($data);

		$this->assertTrue($result);
		$this->assertTrue($requestParser->isFullyRead());
		$this->assertEquals(RequestParser::OK, $requestParser->getStatus());
	}

	public function providerNormalData()
	{
		return array(
			//Example socket_usage.php
			array(
				"GET /democracy/init HTTP/1.1\r\nUser-Agent: The Illuminati/2.0\r\nContent-Length: 10\r\n\r\nOver 9000!",
				array(
					'method' => 'GET',
					'protocolVersion' => 'HTTP/1.1',
					'url' => '/democracy/init',
					'scheme' => NULL,
					'host' => NULL,
					'port' => NULL,
					'user' => NULL,
					'pass' => NULL,
					'path' => '/democracy/init',
					'query' => NULL,
					'fragment' => NULL,
					'_GET' =>
						array(),
					'_POST' =>
						array(),
					'headers' =>
						array(
							'User-Agent' => 'The Illuminati/2.0',
							'Content-Length' => '10',
						),
					'body' => 'Over 9000!',
				),
			),
			//All Fields with urldecoded path and form-type data
			array(
				"POST https://anonymous:password@example.org:8080/urlencoded/start%26%25%23end?hello=world&one=two#title HTTP/1.1\r\nUser-Agent: TestClient/1.0\r\nContent-Length: 13\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\nbodygoes=here",
				array(
					'method' => 'POST',
					'protocolVersion' => 'HTTP/1.1',
					'url' => 'https://anonymous:password@example.org:8080/urlencoded/start%26%25%23end?hello=world&one=two#title',
					'scheme' => 'https',
					'host' => 'example.org',
					'port' => 8080,
					'user' => 'anonymous',
					'pass' => 'password',
					'path' => '/urlencoded/start&%#end',
					'query' => 'hello=world&one=two',
					'fragment' => 'title',
					'_GET' =>
						array(
							'hello' => 'world',
							'one' => 'two',
						),
					'_POST' =>
						array(
							'bodygoes' => 'here',
						),
					'headers' =>
						array(
							'User-Agent' => 'TestClient/1.0',
							'Content-Length' => '13',
							'Content-Type' => 'application/x-www-form-urlencoded',
						),
					'body' => 'bodygoes=here',
				),
			),
			//JSON body input
			array(
				"POST http://example.org/test HTTP/1.1\r\nUser-Agent: TestClient/1.0\r\nContent-Length: 18\r\nContent-Type: application/json\r\n\r\n{\"hello\": \"world\"}",
				array(
					'method' => 'POST',
					'protocolVersion' => 'HTTP/1.1',
					'url' => 'http://example.org/test',
					'scheme' => 'http',
					'host' => 'example.org',
					'port' => NULL,
					'user' => NULL,
					'pass' => NULL,
					'path' => '/test',
					'query' => NULL,
					'fragment' => NULL,
					'_GET' =>
						array(),
					'_POST' =>
						array(
							'hello' => 'world',
						),
					'headers' =>
						array(
							'User-Agent' => 'TestClient/1.0',
							'Content-Length' => '18',
							'Content-Type' => 'application/json',
						),
					'body' => "{\"hello\": \"world\"}",
				),
			),
		);
	}
}