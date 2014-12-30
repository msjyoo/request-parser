RequestParser
=============

[![Build Status](https://circleci.com/gh/sekjun9878/request-parser.svg?circle-token=:circle-token)](https://circleci.com/gh/sekjun9878/request-parser)
[![Latest Stable Version](https://poser.pugx.org/sekjun9878/request-parser/v/stable.svg)](https://packagist.org/packages/sekjun9878/request-parser) [![Total Downloads](https://poser.pugx.org/sekjun9878/request-parser/downloads.svg)](https://packagist.org/packages/sekjun9878/request-parser) [![Latest Unstable Version](https://poser.pugx.org/sekjun9878/request-parser/v/unstable.svg)](https://packagist.org/packages/sekjun9878/request-parser) [![License](https://poser.pugx.org/sekjun9878/request-parser/license.svg)](https://packagist.org/packages/sekjun9878/request-parser)

RequestParser is a PHP library for parsing raw HTTP requests.

This library is not a complete to-specification implementation of the HTTP protocol, but should be good enough for the purposes of a specific-purpose HTTP server such as an API server for a PHP CLI application.

If in doubt, try it and see if it works for your purpose!  But don't come knocking on my door if you accidentally manage to blow up your back yard... somehow.

# Features
- Easy to use API
- Supports Transfer-Encoding: Chunked
- Batteries included!
	- We provide examples for you to copy paste and,
	- We return a default Request object for you to use instantly

# Examples
You can find examples in the `examples/` folder. The library is simple enough to use
straight away without a documentation, but one is coming soon hopefully.

# License

Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
Released under the MIT license; see LICENSE.txt
https://github.com/sekjun9878/request-parser

This software is a modified version of:

Copyright (c) 2011, Trust for Conservation Innovation
Released under the MIT license; see LICENSE.txt
http://github.com/youngj/httpserver