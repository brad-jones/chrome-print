<?php

require('vendor/autoload.php');

/**
 * Intial API Structure and Ideas
 *
 * GET /print?url=http://example.org/document.html
 *
 * POST /print
 * html = html string
 *
 * Need to think about how google chrome
 * gets access to other assets in the html.
 */

$app = new \Slim\Slim();

$app->get('/', function () use ($app)
{
	echo 'Google Chrome Print is running yay!';
});

$app->run();
