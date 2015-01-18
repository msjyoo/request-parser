<?php
/*
 * Copyright (c) 2014 Michael Yoo <michael@yoo.id.au>
 * Released under the MIT license; see LICENSE
 * https://github.com/sekjun9878/request-parser
 */

/*
 * This is a simple script to gather the url for the latest phar build from CircleCI.
 */

$CONTEXT =  stream_context_create(array(
	'http' => array(
		'method' => "GET",
		'header' => "User-Agent: BuildGrabberBot/1.0 (michael@yoo.id.au)\r\n".
			"Accept: application/json"
	)
));

$latest_build_information = json_decode(file_get_contents("https://circleci.com/api/v1/project/sekjun9878/request-parser/tree/master?&limit=1&filter=successful", false, $CONTEXT), true)[0]; //The query's limit is 1 anyway.

$latest_build_artifacts = json_decode(file_get_contents("https://circleci.com/api/v1/project/sekjun9878/request-parser/{$latest_build_information['build_num']}/artifacts", false, $CONTEXT), true);

foreach($latest_build_artifacts as $artifact)
{
	if($artifact['pretty_path'] === '$CIRCLE_ARTIFACTS/build/request-parser.phar')
	{
		if(isset($artifact['url']))
		{
			header("Location: {$artifact['url']}");
			exit();
		}
	}
}

http_response_code(500);
header("Content-Type: text/plain");
?>
The API server could not fetch the URL for the latest request-parser.phar.
This could either be due to a problem with the CircleCI REST API, or a fault with the API server.
Please contact Michael Yoo at michael@yoo.id.au.