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
	echo '<h1>Google Chrome Print is running yay!</h1><img src="/screenshot">';
});

$app->get('/screenshot', function () use ($app)
{
	exec('DISPLAY=:99 xdotool search --sync "Google Chrome" windowmove --sync 0 0');
	exec('DISPLAY=:99 xdotool search --sync "Google Chrome" windowsize --sync 100% 100%');
	exec('DISPLAY=:99 import -window root -quality 100 /tmp/screenshot.png');
	$im = imagecreatefrompng('/tmp/screenshot.png');
	header('Content-Type: image/png');
	imagepng($im);
	imagedestroy($im);
	exit;
});

$app->run();

/*
#!/bin/sh

# Run chrome like so in its own terminal / process / fork:
# xvfb-run --server-args='-screen 0, 1024x768x16' google-chrome file:///tmp/to-be-printed.html

# Maximise the chrome window, helps with mouse cords.
DISPLAY=:99 xdotool search --sync "Google Chrome" windowmove --sync 0 0
DISPLAY=:99 xdotool search --sync "Google Chrome" windowsize --sync 100% 100%

# Open Print Preview
DISPLAY=:99 xdotool search --sync "Google Chrome" key --clearmodifiers ctrl+p

# TODO: Set print settings. Ensure printer is set PDF.
# For now just rely on the defaults being remembered correctly.

# Move mouse to save button
DISPLAY=:99 xdotool mousemove --sync 300 150

# Wait for Chrome to open Print Preview
sleep 1

# Click save button
DISPLAY=:99 xdotool click 1

# Wait for the save file dialog
DISPLAY=:99 xdotool search --sync "Save File"

# Move mouse to filename box
DISPLAY=:99 xdotool mousemove --sync 1000 150

# Click into the filename box
DISPLAY=:99 xdotool click 1

# Select all text
DISPLAY=:99 xdotool key --clearmodifiers ctrl+a

# Delete all text
DISPLAY=:99 xdotool key --clearmodifiers Delete

# Now type our filename, including path
DISPLAY=:99 xdotool type "/tmp/printed.pdf"

# Move the mouse to the save button
DISPLAY=:99 xdotool mousemove --sync 797 722

# Click the save button
DISPLAY=:99 xdotool click 1

#sleep 1

# For debugging purposes we can use ImageMagick to take screen shots so we can actually see what is happening
#DISPLAY=:99 import -window root -quality 100 /tmp/screenshot.png
*/
