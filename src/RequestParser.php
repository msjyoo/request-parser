<?php
/*
 * Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
 * Released under the MIT license; see LICENSE
 * https://github.com/sekjun9878/request-parser
 *
 * This software is a modified version of:
 *
 * Copyright (c) 2011, Trust for Conservation Innovation
 * Released under the MIT license; see LICENSE
 * http://github.com/youngj/httpserver
 */

namespace sekjun9878\RequestParser;

/**
 * RequestParser is a class to parse HTTP raw requests.
 *
 * @package sekjun9878\RequestParser
 * @author Michael Yoo <michael@yoo.id.au>
 * @author Jesse Young
 * @see https://github.com/sekjun9878/request-parser
 */
class RequestParser
{
	/** @var RequestState A RequestState object that can be exported later to a Request object. */
	protected $request;

	// internal fields to track the state of reading the HTTP request
	private $cur_state = self::READ_HEADERS;
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

	const READ_DISABLED = 0;
	const READ_HEADERS = 1;
	const READ_CONTENT = 2;
	const PROCESS_CONTENT = 3;
	const READ_COMPLETE = 4;

	const READ_SIZE = 30000;

	// HTTP Parse Errors
	/*
	 * These may look like HTTP status codes, but they're just codes that report the status of the parser.
	 */
	const BAD_REQUEST = 4;
	const INTERNAL_SERVER_ERROR = 5;
	const OK = 1;
	const NOT_SET = NULL;

	private $status = self::NOT_SET;

	public function __construct()
	{
		$this->request = new RequestState;
	}

	/**
	 * Add data to the Parser. Accepts both entire requests or a series of parts of requests.
	 *
	 * @param string $data
	 * @return bool
	 */
	public function addData($data)
	{
		if($data === false or $data == "") //Invalid empty HTTP Request
		{
			return false;
		}

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
					$this->cur_state = self::READ_DISABLED;
					$this->status = self::BAD_REQUEST;
					//TODO: TODO below
					/*
					 * Multiple errors are not yet supported as errors will be overwritten.
					 */
					break;
				}

				$end_headers = strpos($this->header_buf, "\r\n\r\n", 4);
				if($end_headers === false)
				{
					$this->cur_state = self::READ_DISABLED;
					$this->status = self::BAD_REQUEST;
					break;
				}

				// parse HTTP request line
				$end_req = strpos($this->header_buf, "\r\n");
				$requestLine = substr($this->header_buf, 0, $end_req);//RequestLine isn't really required here.
				$req_arr = explode(' ', $requestLine, 3);

				$this->request->method = $req_arr[0];
				$this->request->url = $req_arr[1];
				$this->request->protocolVersion = $req_arr[2];

				$parsedUrl = parse_url($this->request->url);

				$this->request->scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : NULL;
				$this->request->host = isset($parsedUrl['host']) ? $parsedUrl['host'] : NULL;
				$this->request->port = isset($parsedUrl['port']) ? $parsedUrl['port'] : NULL;
				$this->request->user = isset($parsedUrl['user']) ? $parsedUrl['user'] : NULL;
				$this->request->pass = isset($parsedUrl['pass']) ? $parsedUrl['pass'] : NULL;
				$this->request->path = isset($parsedUrl['path']) ? urldecode($parsedUrl['path']) : NULL;
				$this->request->query = isset($parsedUrl['query']) ? urldecode($parsedUrl['query']) : NULL;
				$this->request->fragment = isset($parsedUrl['fragment']) ? urldecode($parsedUrl['fragment']) : NULL;

				parse_str($this->request->query, $this->request->_GET);

				// parse HTTP headers
				$start_headers = $end_req + 2;

				$headers_str = substr($this->header_buf, $start_headers, $end_headers - $start_headers);
				$this->request->headers = static::parseHeader($headers_str);

				if(isset($this->request->headers['Transfer-Encoding']))
				{
					$this->is_chunked = $this->request->headers['Transfer-Encoding'] == 'chunked';

					unset($this->request->headers['Transfer-Encoding']);//TODO: Why?

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
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::READ_CONTENT:
				if($this->is_chunked)
				{
					if(!$this->readChunkedData($data))//If false
					{
						break;
					}
					//If chunked data reading is complete, fall through to PROCESS_CONTENT
				}
				else
				{
					$this->request->body .= $data;
					$this->content_len_read += strlen($data);

					// On Request Read Complete
					if($this->content_len - $this->content_len_read > 0)
					{
						break;//If there is more content to read, don't fallthrough to the next case statement.
					}
				}
				$this->cur_state = self::PROCESS_CONTENT;
				//fallthrough to PROCESS_CONTENT
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::PROCESS_CONTENT:
				if(isset($this->request->headers['Content-Type']))
				{
					switch($this->request->headers['Content-Type'])
					{
						case 'application/x-www-form-urlencoded':
							parse_str($this->request->body, $this->request->_POST);
							break;
						case 'application/json':
							$this->request->_POST = json_decode($this->request->body, true);
							break;
						default:
							$this->request->_POST = array();//NULL here because false can be "false"...?
					}
				}
				else
				{
					$this->request->_POST = array();
				}

				$this->status = self::OK;
				$this->cur_state = self::READ_COMPLETE;
				//fallthrough to READ_COMPLETE
			case self::READ_COMPLETE:
				break;
			case self::READ_DISABLED:
				break;
		}

		return true;
	}

	/**
	 * A private method to read chunked HTTP requests.
	 *
	 * @param string $data
	 * @return bool
	 */
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

					$chunk_len_hex = explode(";", $chunk_header, 2)[0];

					$this->chunk_len_remaining = intval($chunk_len_hex, 16);

					$this->chunk_state = self::READ_CHUNK_DATA;

					$data = substr($this->chunk_header_buf, $end_chunk_header + 2/* two for carriage return */);
					$this->chunk_header_buf = '';

					if($this->chunk_len_remaining == 0)//If this is the terminating chunk
					{
						$this->cur_state = self::PROCESS_CONTENT;
						$this->request->headers['Content-Length'] = $this->content_len;

						// todo: this is where we should process trailers...
						return true;
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

		return false;
	}

	/**
	 * Returns true if a full HTTP request has been read by addData().
	 *
	 * @return bool
	 */
	public function isFullyRead()
	{
		return ($this->cur_state === self::READ_COMPLETE) || ($this->cur_state === self::READ_DISABLED);
	}

	/**
	 * Export the state of the RequestState. Contains the parsed data.
	 *
	 * @return array
	 */
	public function exportRequestState()
	{
		return $this->request->exportState();
	}

	/**
	 * Returns the HTTP status code of the Parser. This is need to distinguish between fatal errors (false and exit)
	 * and graceful errors (status codes)
	 *
	 * @return null|int
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Parse a raw HTTP header string to an array.
	 *
	 * @param $headers_str
	 * @return array
	 */
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
					/*
					 * TODO: TODO: Change so that more than one headers can be set and used e.g. Set-Cookie
					 * https://github.com/youngj/httpserver/commit/0aa0e411d5c3bc6171c6e39d9b248415320c5060
					 * Currently, arrays are returned on more than two headers
					 * which would cause an error.
					 */
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
