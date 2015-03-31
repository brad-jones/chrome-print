<?php

// Load up composer
require('vendor/autoload.php');

// Import some classes
use Silex\Application;
use ChromePrint\XdoTool;
use Gears\String as Str;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Initialise Silex
$app = new Application();
$app['debug'] = true;

// Add our XdoTool helper class into the Silex app.
$app['xdo'] = $app->share(function () { return new XdoTool(); });

/**
 * Displays a nice graphical home page.
 *
 * This allows the user to monitor what google chrome is actually doing by
 * watching the screenshots as they refresh. It also allows the user to
 * test the service by submiting a HTML document to be converted to PDF.
 */
$app->get('/', function (Application $app)
{
	return
	'
		<!DOCTYPE html>
		<html>
			<head>
				<link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.6.0/pure-min.css">
				<script src="http://code.jquery.com/jquery-2.1.3.min.js"></script>
				<script>
					$(window).on("load", function()
					{
						setInterval(function()
						{
							$(".screenshot").attr("src", "/screenshot?timestamp="+new Date().getTime());
						}, 500);
					});
				</script>
			</head>
			<body style="text-align:center;">
				<h1>Google Chrome Print is running yay!</h1>
				<img class="screenshot" src="/screenshot" style="height:800px">
			</body>
		</html>
	';
});

/**
 * Takes a screenshot of the X virtual frame buffer.
 *
 * @return PNG Response
 */
$app->get('/screenshot', function (Application $app)
{
	$image = $app['xdo']->takeScreenShot();
	
	return new Response($image, 200, ['Content-Type' => 'image/png']);
});

/**
 * Prints your supplied HTML document to PDF
 *
 * @param string url Keeping in mind that Google Chrome is running inside a
 *                   docker container, if Google Chrome can get to your URL,
 *                   we can print it for you.
 *
 * Further notes about the connectivity of docker containers.
 * To access the docker host use a url like: http://docker/ but make sure your
 * host firewall allows the docker bridge to talk to your host.
 *
 * If you want to access another docker container make sure it's ports are
 * exposed and it has been linked correctly  and you should be able to use the
 * link alias as the hostname.
 *
 * @return PDF Response
 */
$app->get('/print/{url}', function (Application $app, $url)
{
	return new Response
	(
		$app['xdo']->printWithUrl($url), 200,
		['Content-Type' => 'application/pdf']
	);
});

$app->get('/print/{url}/{size}/{layout}/{wait}', function (Application $app, $url, $size, $layout, $wait)
{
	return new Response
	(
		$app['xdo']->printWithUrl($url, $size, $layout, $wait), 200,
		['Content-Type' => 'application/pdf']
	);
})
->assert('size', 'a4|a3|letter|legal|tabloid')
->assert('layout', 'portrait|landscape')
->assert('wait', '\d+');

// Run the app
$app->run();
