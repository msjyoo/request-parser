<?php
/*
 * Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
 * Released under the MIT license; see LICENSE
 * https://github.com/sekjun9878/request-parser
 */

namespace sekjun9878\RequestParser;

use ReflectionClass;
use ReflectionProperty;

/**
 * A Request object is a stub class that is writable by the public scope - to be exported later on.
 *
 * RequestState is an object that is very similar to the Request object, the only difference being that RequestState
 * has properties that are directly accessible by the public scope.
 *
 * This is in order for the RequestParser to conveniently make the request object by writing directly to it, without
 * the final object also being writable by the public scope.
 *
 * A RequestState object can be converted to a Request object by exporting and then using __set_state.
 *
 * @package sekjun9878\RequestParser
 * @author Michael Yoo <michael@yoo.id.au>
 * @see https://github.com/sekjun9878/request-parser
 */
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

	/**
	 * Exports the properties of this stub class to an array to be used in __set_state.
	 *
	 * @return array
	 */
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