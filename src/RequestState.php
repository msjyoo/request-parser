<?php
/*
 * Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
 * Released under the MIT license; see LICENSE.txt
 * https://github.com/sekjun9878/request-parser
 */

namespace sekjun9878\RequestParser;

use ReflectionClass;
use ReflectionProperty;

class RequestState
{
	/** @var string */
	public $method;

	/** @var string */
	public $protocolVersion;

	/** @var string */
	public $url;

	/** @var string */
	public $scheme;

	/** @var string */
	public $host;

	/** @var integer */
	public $port;

	/** @var string */
	public $user;

	/** @var string */
	public $pass;

	/** @var string */
	public $path;

	/** @var string */
	public $query;

	/** @var string */
	public $fragment;

	/** @var float */
	public $startTime;

	/** @var array */
	public $_GET = array();

	/** @var array */
	public $_POST = array();

	/** @var array */
	public $headers;

	/** @var string */
	public $body;

	public function exportState()
	{
		$properties = array();

		$reflection = new ReflectionClass($this);
		$reflectionProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

		foreach($reflectionProperties as $reflectionProperty)
		{
			$properties[$reflectionProperty->getName()] = $reflectionProperty->getValue($this);
		}

		return $properties;
	}
}