<?php

namespace sekjun9878\RequestParser\Test;

use PHPUnit_Framework_TestCase;
use ReflectionObject;
use ReflectionProperty;
use sekjun9878\RequestParser\Request;
use sekjun9878\RequestParser\RequestState;

class RequestTest extends PHPUnit_Framework_TestCase
{
	public function testRequestPropertiesEqualRequestStateProperties()
	{
		$requestState = new RequestState;
		$requestStateReflection = new ReflectionObject($requestState);

		$request = new Request;
		$requestReflection = new ReflectionObject($request);

		$requestStateProperties = $requestStateReflection->getProperties(ReflectionProperty::IS_PUBLIC);
		$requestProperties = $requestReflection->getProperties(ReflectionProperty::IS_PRIVATE);

		//Rebuild array to contain only the property name
		$requestStatePropertiesNames = array();
		$requestPropertiesNames = array();

		foreach($requestStateProperties as $requestStateProperty)
		{
			$requestStatePropertiesNames[] = $requestStateProperty->getName();
		}

		foreach($requestProperties as $requestProperty)
		{
			$requestPropertiesNames[] = $requestProperty->getName();
		}

		$diff = array_diff($requestPropertiesNames, $requestStatePropertiesNames);
		$this->assertEmpty($diff,
			"Request has properties that are not present in RequestState:".PHP_EOL.print_r($diff, true));
	}

	public function testRequestSetStateFromRequestStateExport()
	{
		$value = sha1((string) mt_rand());

		$requestState = new RequestState;

		$requestStateReflection = new ReflectionObject($requestState);

		$requestStateProperties = $requestStateReflection->getProperties(ReflectionProperty::IS_PUBLIC);

		foreach($requestStateProperties as $requestStateProperty)
		{
			$requestStateProperty->setValue($requestState, $value);
		}

		$request = Request::__set_state($requestState->exportState());

		$requestReflection = new ReflectionObject($request);
		$requestMethods = $requestReflection->getMethods();

		foreach($requestMethods as $requestMethod)
		{
			if(substr($requestMethod->getName(), 0, 3) === "get")
			{
				$this->assertEquals($value, $requestMethod->invoke($request),
					"Getter Method '{$requestMethod->getName()}' does not equal expected $value after __set_state()");
			}
		}
	}

	public function testRequestCreateEqualsRequestSetState()
	{
		$value = sha1((string) mt_rand());

		$requestState = new RequestState;

		$requestStateReflection = new ReflectionObject($requestState);

		$requestStateProperties = $requestStateReflection->getProperties(ReflectionProperty::IS_PUBLIC);

		foreach($requestStateProperties as $requestStateProperty)
		{
			$requestStateProperty->setValue($requestState, $value);
		}

		$request1 = Request::__set_state($requestState->exportState());
		$request2 = Request::create($requestState->exportState());

		$this->assertEquals($request1, $request2);
	}
}