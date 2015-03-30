#!/bin/bash

export GEOMETRY="$SCREEN_WIDTH""x""$SCREEN_HEIGHT""x""$SCREEN_DEPTH"

function shutdown {
	kill -s SIGTERM $NODE_PID
	wait $NODE_PID
}

xvfb-run --server-args="$DISPLAY -screen 0 $GEOMETRY -ac +extension RANDR" \
	/usr/bin/google-chrome --no-first-run --no-default-browser-check --no-sandbox --start-maximized &
NODE_PID=$!

trap shutdown SIGTERM SIGINT
wait $NODE_PID
