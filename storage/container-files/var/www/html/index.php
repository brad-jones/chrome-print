<?php

// Load up composer
require('vendor/autoload.php');

// Import some classes
use Silex\Application;
use ChromePrint\XdoTool;
use ChromePrint\Printer;
use Gears\String as Str;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Initialise Silex
$app = new Application();
$app['debug'] = true;

/**
 * Displays a nice graphical home page.
 *
 * Okay so it's pretty basic for now... maybe later we might jazz it up.
 */
$app->get('/', function (Application $app)
{
	return
	'
		<!DOCTYPE html>
		<html>
			<head>
				<link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.6.0/pure-min.css">
			</head>
			<body style="text-align:center;">
				<h1>Google Chrome Print is running yay!</h1>
				<p>
					To submit a document for printing,
					create a request like this one:
					<a href="/print/a4/portrait?url=file:///var/www/html/example-page/index.html">
						/print/a4/portrait?url=file:///var/www/html/example-page/index.html
					</a>
				</p>
				<p>
					For debugging purposes you may watch the printing
					process by opening a link like this one:
					<a href="/watch/99">/watch/99</a> in a second window / tab.
				</p>
			</body>
		</html>
	';
});

/**
 * Allows you to watch the printing process live.
 *
 * @param int $display The X display number to watch.
 *
 * > NOTE: If running inside a non-server based virtual machine (Eg: VirtualBox)
 * > or on slow hardware, you may find the screenshots do not refresh quick
 * > enough. And you don't see anything. In such as case adjust the refresh time
 * > from 500ms to a greater value. Anything faster than 500ms fails in my
 * > experience regardless of performance.
 */
$app->get('/watch/{display}', function (Application $app, $display)
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
							var time = new Date().getTime();
							var nextImg = "/screenshot/'.$display.'?timestamp=" + time;
							$(".screenshot").attr("src", nextImg);
						}, 500);
					});
				</script>
			</head>
			<body style="text-align:center;">
				<h1>Google Chrome Print X Virtual Frame Buffer</h1>
				<img class="screenshot" src="/screenshot/'.$display.'" style="height:500px">
			</body>
		</html>
	';
})->assert('display', '\d+');

/**
 * Takes a screenshot of the X virtual frame buffer.
 *
 * @param int $display The X display number to take a screenshot of.
 *
 * @return PNG Response
 */
$app->get('/screenshot/{display}', function (Application $app, $display)
{
	return new Response
	(
		(new XdoTool($display))->takeScreenShot(),
		200,
		['Content-Type' => 'image/png']
	);
})->assert('display', '\d+');

/**
 * Prints your supplied HTML document to PDF
 *
 * @get string $url Keeping in mind that Google Chrome is running inside a
 *                    docker container, if Google Chrome can get to your URL,
 *                    we can print it for you.
 *
 * @param string $size This is the paper size and directly corresponds to
 *                     Google Chromes print options, it can be one of the
 *                     following: a4, a3, letter, legal, tabloid.
 *
 * @param string $layout Again this directly corresponds to Google Chomes layout
 *                       option and can be either portrait or landscape.
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
$app->get('/print/{size}/{layout}', function (Application $app, $size, $layout)
{
	if (!$app['request']->query->has('url'))
	{
		throw new RuntimeException
		(
			'You must supply a URL for us to print!'
		);
	}

	$url = $app['request']->query->get('url');

	return new Response
	(
		(new Printer())->printWithUrl($url, $size, $layout), 200,
		['Content-Type' => 'application/pdf']
	);
})
->assert('size', 'a4|a3|letter|legal|tabloid')
->assert('layout', 'portrait|landscape');

// Run the app
$app->run();
