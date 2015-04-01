#!/bin/bash

# Set some config, I don't see any reason for any of this to change.
# Thats the great thing about containers, I can just hard code stuff
# and nobody will know haha.
export PROFILE=/home/chrome/google-chrome-profile
export SCREEN_WIDTH="1360"
export SCREEN_HEIGHT="1020"
export SCREEN_DEPTH="24"
export GEOMETRY="$SCREEN_WIDTH""x""$SCREEN_HEIGHT""x""$SCREEN_DEPTH"

# Shutdown function
function shutdown {
	kill -s SIGTERM $NODE_PID
	wait $NODE_PID
}

# Start x virtual frame buffer with google chrome inside it
xvfb-run --server-num="$DISPLAY" --server-args="-screen 0 $GEOMETRY -ac +extension RANDR" \
	/usr/bin/google-chrome --no-first-run --no-default-browser-check --no-sandbox --user-data-dir $PROFILE &
NODE_PID=$!

# More shutdown related stuff
trap shutdown SIGTERM SIGINT
wait $NODE_PID
