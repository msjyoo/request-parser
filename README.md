RequestParser [![Build Status](https://circleci.com/gh/sekjun9878/request-parser.svg?circle-token=:circle-token)](https://circleci.com/gh/sekjun9878/request-parser)
=============
[![Latest Stable Version](https://poser.pugx.org/sekjun9878/request-parser/v/stable.svg)](https://packagist.org/packages/sekjun9878/request-parser) [![Total Downloads](https://poser.pugx.org/sekjun9878/request-parser/downloads.svg)](https://packagist.org/packages/sekjun9878/request-parser) [![Latest Unstable Version](https://poser.pugx.org/sekjun9878/request-parser/v/unstable.svg)](https://packagist.org/packages/sekjun9878/request-parser) [![License](https://poser.pugx.org/sekjun9878/request-parser/license.svg)](https://packagist.org/packages/sekjun9878/request-parser)

RequestParser is a PHP library for parsing raw HTTP requests.

This library is not a complete to-specification implementation of the HTTP/1.1 protocol (because doing that would be extremely difficult and unnecessary), but implements most of the spec and should be good enough for the purposes of a specific-purpose HTTP server such as an API server for a PHP CLI application.

If in doubt, try it and see if it works for your purpose!  But don't come knocking on my door if you accidentally manage to blow up your back yard... somehow.

# Features
- Easy to use
- Unit tested
- Comes in both Composer and PHAR versions
- Supports Transfer-Encoding: Chunked
- Batteries included!
	- We provide examples for you to copy paste and,
	- We return a default Request object for you to use instantly

# Examples
Here is a quick example to demonstrate how easy it is to instantly get started:
```
$requestParser = new RequestParser;
$requestParser->addData("GET /democracy/init HTTP/1.1\r\nUser-Agent: The Illuminati/2.0\r\nContent-Length: 10\r\n\r\nOver 9000!");

$request = Request::create($requestParser->exportRequestState());

var_dump($request->getHeaders());
var_dump($request->getPOST());
```

You can find more examples in the `examples/` folder. The library is simple enough to use
straight away without a documentation, but one is coming soon hopefully.

# Installation
## Composer
request-parser is PSR-4 compliant and can be installed using Composer. Simply add sekjun9878/request-parser to your composer.json file. *Composer is the sane alternative to PEAR. It is excellent for managing dependencies in larger projects.*
```
{
    "require": {
        "sekjun9878/request-parser": "~1.0"
    }
}
```

or

```
php composer.phar require sekjun9878/request-parser ~1.0
```
## PHAR
A [PHP Archive](http://php.net/manual/en/book.phar.php) (or .phar) file is available for [downloading](https://github.com/sekjun9878/request-parser/releases/latest).  Simply [download](https://github.com/sekjun9878/request-parser/releases/latest) the .phar, drop it into your project, and include it like you would any other php file.  *This method is ideal for smaller projects, one off scripts, and quick API hacking.*

```
require_once(__DIR__."/request-parser.phar");
```

# Downloads
For installation, see the installation notes above.

Download latest stable request-parser.phar [here](https://github.com/sekjun9878/request-parser/releases/latest).
Download latest master request-parser.phar [here](https://www.michael.yoo.id.au/projects/request-parser/api/download-latest-build.php).

Additional links:

API to return the URL to download the latest master build from: https://www.michael.yoo.id.au/projects/request-parser/api/latest-build-url.php

# License
```
Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
Released under the MIT license; see LICENSE
https://github.com/sekjun9878/request-parser

This project contains portions of source code from other projects; see LICENSE.
```