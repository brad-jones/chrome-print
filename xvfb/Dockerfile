# Chrome Print - xvfb
# ==============================================================================
# This container runs Google Chrome inside an X Virtual Frame Buffer or XVFB.
# It exposes the X server socket to a second container which then controls
# Chrome using xdotool.

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# Update base image
RUN yum update -y && yum clean all

# Add the Google Chrome Yum Repo
ADD ["google-chrome.repo", "/etc/yum.repos.d/google-chrome.repo"]

# Install packages
RUN yum install -y \
	google-chrome-stable \
	xorg-x11-server-Xvfb \
	&& yum clean all

# Add our xvfb wrapper, thanks Selenium guys.
# For some reason xvfb-run by it's self doesn't work
ADD xvfb-run-wrapper.sh /usr/local/bin/xvfb-run-wrapper.sh
RUN chmod +x /usr/local/bin/xvfb-run-wrapper.sh

# Config goes last, so we captalise on docker build cache.
ENV SCREEN_WIDTH 1360
ENV SCREEN_HEIGHT 1020
ENV SCREEN_DEPTH 24
ENV DISPLAY :99.0

# Start the virtual frame buffer with google chrome running inside
CMD ["/usr/local/bin/xvfb-run-wrapper.sh"]
