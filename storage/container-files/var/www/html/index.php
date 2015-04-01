<?php

// Load up composer
require('vendor/autoload.php');

// Import some classes
use Silex\Application;
use ChromePrint\XdoTool;
use Gears\String as Str;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Initialise Silex
$app = new Application();
$app['debug'] = true;

// Setup the Symfony Session Handler
$app->register(new SessionServiceProvider());

// Add our XdoTool helper class into the Silex app.
$app['xdo'] = $app->share(function (Application $app)
{
	return new XdoTool($app['session']);
});

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
							$(".screenshot").attr("src", "/screenshot?timestamp=" + new Date().getTime());
						}, 500);
					});
				</script>
			</head>
			<body style="text-align:center;">
				<h1>Google Chrome Print is running yay!</h1>
				<p>Below is a view into the X Virtual Frame Buffer.</p>
				<img class="screenshot" src="/screenshot" style="height:500px">
				<p>
					To submit a document for printing,
					create a request like this one:
					<a href="/print/a4/portrait/2?url=http://www.docker.com/">
						/print/a4/portrait/2?url=http://www.docker.com/
					</a>
				</p>
				<p>
					Open the link in another browser
					window and watch this one :)
				</p>
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
	return new Response
	(
		$app['xdo']->takeScreenShot(),
		200,
		['Content-Type' => 'image/png']
	);
});

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
 * @param int $wait This is one of the hackier parts of this project.
 *                  Because we are operating outside of the web page, we have
 *                  no idea when the page has finished loading. If we try and
 *                  print the page before it has fully loaded we may get
 *                  unexpected results. So the idea is that you will have a
 *                  rough idea of how long your page will take to load so you
 *                  can set a time in seconds for the amount of time we wait for
 *                  hitting the print button. I would like to investigate
 *                  Selenium further and / or an OCR solution. The last time I
 *                  looked at Selenium it had no way to print a document,
 *                  exactly why I created this project. But I might be able to
 *                  use it to tell us when the page has finished loading.
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
$app->get('/print/{size}/{layout}/{wait}', function (Application $app, $size, $layout, $wait)
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
		$app['xdo']->printWithUrl($url, $size, $layout, $wait), 200,
		['Content-Type' => 'application/pdf']
	);
})
->assert('size', 'a4|a3|letter|legal|tabloid')
->assert('layout', 'portrait|landscape')
->assert('wait', '\d+');

/**
 * Lazy mans version of the above.
 *
 * @get string url The url to print.
 *
 * > NOTE: All other settings are set at their defaults!
 *
 * @return PDF Response
 */
$app->get('/print', function (Application $app)
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
		$app['xdo']->printWithUrl($url), 200,
		['Content-Type' => 'application/pdf']
	);
});

// Run the app
$app->run();
