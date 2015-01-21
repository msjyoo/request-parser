<?php
/*
 * Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
 * Released under the MIT license; see LICENSE
 * https://github.com/sekjun9878/request-parser
 */

/* Package Version v1.0.1 */

if(class_exists('Phar'))
{
	Phar::mapPhar('request-parser.phar');

	spl_autoload_register(function ($name) {
		//Verify Input http://php.net/manual/en/language.oop5.autoload.php "contain some dangerous characters"
		if(!preg_match("/^[A-Za-z0-9_\\\\]+$/", $name))//Allow Alphanumeric + Underscore + Namespace Separator
			//Also, using four backslashes here http://stackoverflow.com/a/4025505
		{
			return false;
		}

		$namespace = explode("\\", $name);

		if($namespace[0] === "sekjun9878" and $namespace[1] === "RequestParser")
		{
			array_splice($namespace, 0, 2);//Remove the vendor and package part of namespace from array

			$path = implode("/", $namespace);

			if(file_exists("phar://request-parser.phar/src/{$path}.php"))
			{
				require_once("phar://request-parser.phar/src/{$path}.php");
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	});
}
else
{
	throw new RuntimeException("The PHAR extension is required to use this library in .phar format.");
}

__HALT_COMPILER();