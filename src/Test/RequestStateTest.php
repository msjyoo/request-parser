<?php

namespace sekjun9878\RequestParser\Test;

use PHPUnit_Framework_TestCase;
use ReflectionObject;
use ReflectionProperty;
use sekjun9878\RequestParser\Request;
use sekjun9878\RequestParser\RequestState;

class RequestStateTest extends PHPUnit_Framework_TestCase
{
	public function testRequestStateHasAllProperties()
	{
		$expectedProperties = array(
			'method',
			'protocolVersion',
			'url',
			'scheme',
			'host',
			'port',
			'user',
			'pass',
			'path',
			'query',
			'fragment',
			'startTime',
			'_GET',
			'_POST',
			'headers',
			'body',
		);

		$requestState = new RequestState;
		$reflection = new ReflectionObject($requestState);

		$properties_obj = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

		//Rebuild array to contain only the property name
		$properties = array();

		foreach($properties_obj as $property_obj)
		{
			$properties[] = $property_obj->getName();
		}

		foreach($expectedProperties as $expectedPropertyKey => $expectedPropertyName)
		{
			$propertyKey = array_search($expectedPropertyName, $properties);
			$this->assertNotFalse($propertyKey, "Expected Property '$expectedPropertyName' Not Found");

			unset($expectedProperties[$expectedPropertyKey]);
			unset($properties[$propertyKey]);
		}

		$this->assertEquals(0, count($expectedProperties), "Logic Error In Test: foreach should have unset entire array");
		$this->assertEquals(0, count($properties), "Test Error: Not all properties have been expected");
	}

	public function testRequestStateExportsProperties()
	{
		$requestState = new RequestState;

		$requestState->method = "GET";
		$requestState->scheme = "http";
		$requestState->path = "/slug-character-with-000-numbers";
		$requestState->query = NULL;
		$requestState->_POST['session_key'] = "buffer_overflow_attack";
		$requestState->user = false;

		$state = $requestState->exportState();

		$this->assertEquals("GET", $state['method']);
		$this->assertEquals("http", $state['scheme']);
		$this->assertEquals("/slug-character-with-000-numbers", $state['path']);
		$this->assertEquals(NULL, $state['query']);
		$this->assertEquals(array(
			'session_key' => "buffer_overflow_attack"
		), $state['_POST']);
		$this->assertEquals(false, $state['user']);
	}

	public function testRequestStatePropertiesEqualRequestProperties()
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

		$diff = array_diff($requestStatePropertiesNames, $requestPropertiesNames);
		$this->assertEmpty($diff,
			"RequestState has properties that are not present in Request:".PHP_EOL.print_r($diff, true));
	}
}