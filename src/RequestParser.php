<?php
/*
 * Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
 * Released under the MIT license; see LICENSE.txt
 * https://github.com/sekjun9878/request-parser
 *
 * This software is a modified version of:
 *
 * Copyright (c) 2011, Trust for Conservation Innovation
 * Released under the MIT license; see LICENSE.txt
 * http://github.com/youngj/httpserver
 */

namespace sekjun9878\RequestParser;

class RequestParser
{
	/** @var RequestState A RequestState object that can be exported later to a Request object. */
	protected $request;

	// internal fields to track the state of reading the HTTP request
	private $cur_state = 0;
	private $header_buf = '';
	private $content_len = 0;
	private $content_len_read = 0;

	private $is_chunked = false;
	private $chunk_state = 0;
	private $chunk_len_remaining = 0;
	private $chunk_trailer_remaining = 0;
	private $chunk_header_buf = '';

	const READ_CHUNK_HEADER = 0;
	const READ_CHUNK_DATA = 1;
	const READ_CHUNK_TRAILER = 2;

	const READ_HEADERS = 0;
	const READ_CONTENT = 1;
	const READ_COMPLETE = 2;

	const READ_SIZE = 30000;

	public function __construct()
	{
		$this->request = new RequestState;
	}

	/*
	 * Reads a chunk of a HTTP request from a client socket.
	 */
	public function addData($data) //TODO: Rename this to something a bit more appropriate, like queue to read or something.
	{
		switch($this->cur_state)
		{
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::READ_HEADERS:
				if(!isset($this->request->startTime))
				{
					$this->request->startTime = microtime(true);
				}

				$this->header_buf .= $data;

				if(strlen($this->header_buf) < 4)
				{
					//TODO: 400 Bad Request
					break;
				}

				$end_headers = strpos($this->header_buf, "\r\n\r\n", 4);
				if($end_headers === false)
				{
					//TODO: 400 Bad Request
					break;
				}

				// parse HTTP request line
				$end_req = strpos($this->header_buf, "\r\n");
				$requestLine = substr($this->header_buf, 0, $end_req);//TODO: ?
				$req_arr = explode(' ', $requestLine, 3);

				$this->request->method = $req_arr[0];
				$this->request->url = $req_arr[1];
				$this->request->protocolVersion = $req_arr[2];

				$parsedUrl = parse_url($this->request->url);

				$this->request->scheme = $parsedUrl['scheme'];
				$this->request->host = $parsedUrl['host'];
				$this->request->port = $parsedUrl['port'];
				$this->request->user = $parsedUrl['user'];
				$this->request->pass = $parsedUrl['pass'];
				$this->request->path = urldecode($parsedUrl['path']);
				$this->request->query = urldecode($parsedUrl['query']);
				$this->request->fragment = urldecode($parsedUrl['fragment']);

				parse_str($this->request->query, $this->request->_GET);

				// parse HTTP headers
				$start_headers = $end_req + 2;

				$headers_str = substr($this->header_buf, $start_headers, $end_headers - $start_headers);
				$this->request->headers = static::parseHeader($headers_str);

				if(isset($this->request->headers['Transfer-Encoding']))
				{
					$this->is_chunked = $this->request->headers['Transfer-Encoding'] == 'chunked';

					unset($this->request->headers['Transfer-Encoding']);

					$this->content_len = 0;
				}
				else
				{
					$this->content_len = (int) $this->request->headers['Content-Length'];
				}

				$start_content = $end_headers + 4; // $end_headers is before last \r\n\r\n

				$data = substr($this->header_buf, $start_content);
				$this->header_buf = '';

				$this->cur_state = self::READ_CONTENT;

			// fallthrough to READ_CONTENT with leftover data
			case self::READ_CONTENT:
				if($this->is_chunked)
				{
					$this->readChunkedData($data);
				}
				else
				{
					$this->request->body .= $data;
					$this->content_len_read += strlen($data);

					// On Request Read Complete
					if($this->content_len - $this->content_len_read <= 0)
					{
						$this->cur_state = self::READ_COMPLETE;

						switch($this->request->headers['Content-Type'])
						{
							case 'application/x-www-form-urlencoded':
								parse_str($this->request->body, $this->request->_POST);
								break;
							case 'application/json':
								$this->request->_POST = json_decode($this->request->body, true);
								break;
							default:
								$this->request->_POST = NULL;//NULL here because false can be "false"...?
						}
					}
				}
				break;
			case self::READ_COMPLETE:
				break;
		}
	}

	private function readChunkedData($data)
	{
		while(isset($data[0])) // keep processing chunks until we run out of data
		{
			switch($this->chunk_state)
			{
				/** @noinspection PhpMissingBreakStatementInspection */
				case self::READ_CHUNK_HEADER:
					$this->chunk_header_buf .= $data;
					$data = "";

					$end_chunk_header = strpos($this->chunk_header_buf, "\r\n");
					if($end_chunk_header === false) // still need to read more chunk header
					{
						break;
					}

					// done with chunk header
					$chunk_header = substr($this->chunk_header_buf, 0, $end_chunk_header);

					list($chunk_len_hex) = explode(";", $chunk_header, 2);

					$this->chunk_len_remaining = intval($chunk_len_hex, 16);

					$this->chunk_state = self::READ_CHUNK_DATA;

					$data = substr($this->chunk_header_buf, $end_chunk_header + 2);
					$this->chunk_header_buf = '';

					if($this->chunk_len_remaining == 0)
					{
						$this->cur_state = self::READ_COMPLETE;
						$this->request->headers['Content-Length'] = array($this->content_len);

						// todo: this is where we should process trailers...
						return;
					}

				// fallthrough to READ_CHUNK_DATA with leftover data
				case self::READ_CHUNK_DATA:
					if(strlen($data) > $this->chunk_len_remaining)
					{
						$chunk_data = substr($data, 0, $this->chunk_len_remaining);
					}
					else
					{
						$chunk_data = $data;
					}

					$this->content_len += strlen($chunk_data);
					$this->request->body .= $chunk_data;
					$data = substr($data, $this->chunk_len_remaining);
					$this->chunk_len_remaining -= strlen($chunk_data);

					if($this->chunk_len_remaining == 0)
					{
						$this->chunk_trailer_remaining = 2;
						$this->chunk_state = self::READ_CHUNK_TRAILER;
					}
					break;
				case self::READ_CHUNK_TRAILER: // each chunk ends in \r\n, which we ignore
					$len_to_read = min(strlen($data), $this->chunk_trailer_remaining);

					$data = substr($data, $len_to_read);
					$this->chunk_trailer_remaining -= $len_to_read;

					if($this->chunk_trailer_remaining == 0)
					{
						$this->chunk_state = self::READ_CHUNK_HEADER;
					}

					break;
			}
		}
	}

	/*
	 * Returns true if a full HTTP request has been read by addData().
	 */
	public function isFullyRead()
	{
		return $this->cur_state == self::READ_COMPLETE;
	}

	public function exportRequestState()
	{
		return $this->request->exportState();
	}

	public static function parseHeader($headers_str)
	{
		$headers_arr = explode("\r\n", $headers_str);

		$headers = array();

		foreach($headers_arr as $header_str)//Notice the diff between singular and plural '$header'
		{
			$header_arr = explode(": ", $header_str, 2);
			if(sizeof($header_arr) == 2)
			{
				$header_name = $header_arr[0];
				$value = $header_arr[1];

				if(!isset($headers[$header_name]))
				{
					$headers[$header_name] = $value;
				}
				else
				{
					if(!is_array($headers[$header_name]))
					{
						$headers[$header_name] = array($headers[$header_name]);
					}

					$headers[$header_name][] = $value;
				}
			}
		}

		return $headers;
	}
}
