<?php
/*
 * Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
 * Released under the MIT license; see LICENSE
 * https://github.com/sekjun9878/request-parser
 */

namespace sekjun9878\RequestParser;

/**
 * A usable Request object. See RequestState.php for more detail.
 *
 * @package sekjun9878\RequestParser
 * @author Michael Yoo <michael@yoo.id.au>
 * @see https://github.com/sekjun9878/request-parser
 */
class Request
{
	/** @var string */
	private $method;

	/** @var string */
	private $protocolVersion;

	/** @var string */
	private $url;

	/** @var string */
	private $scheme;

	/** @var string */
	private $host;

	/** @var integer */
	private $port;

	/** @var string */
	private $user;

	/** @var string */
	private $pass;

	/** @var string */
	private $path;

	/** @var string */
	private $query;

	/** @var string */
	private $fragment;

	/** @var float */
	private $startTime;

	/** @var array */
	private $_GET = array();

	/** @var array */
	private $_POST = array();

	/** @var array */
	private $headers;

	/** @var string */
	private $body;

	/**
	 * @param array $array
	 * @return static
	 */
	public static function create(array $array)
	{
		return static::__set_state($array);
	}

	/**
	 * @param array $array
	 * @return static
	 */
	public static function __set_state(array $array)
	{
		$request = new static;
		$reflection = new \ReflectionObject($request);

		foreach($array as $key => $value)
		{
			$property = $reflection->getProperty($key);
			$property->setAccessible(true);
			$property->setValue($request, $value);
		}

		return $request;
	}

	/**
	 * @return string
	 */
	public function getScheme()
	{
		return $this->scheme;
	}

	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getPass()
	{
		return $this->pass;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * @return string
	 */
	public function getFragment()
	{
		return $this->fragment;
	}

	/**
	 * @return float
	 */
	public function getStartTime()
	{
		return $this->startTime;
	}

	/**
	 * @return array
	 */
	public function getGET()
	{
		return $this->_GET;
	}

	/**
	 * @return array
	 */
	public function getPOST()
	{
		return $this->_POST;
	}

	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @return string
	 */
	public function getProtocolVersion()
	{
		return $this->protocolVersion;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}
}