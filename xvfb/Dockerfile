# Chrome Print - xvfb
# ==============================================================================
# This container runs Google Chrome inside an X Virtual Frame Buffer or XVFB.

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# Add our container files
ADD ["container-files","/"]

# Install packages
RUN yum update -y && yum install -y \
	google-chrome-stable \
	xorg-x11-server-Xvfb \
	&& yum clean all

# Config for the xvfb-run wrapper
ENV SCREEN_WIDTH 1360
ENV SCREEN_HEIGHT 1020
ENV SCREEN_DEPTH 24
ENV DISPLAY :99.0

# Make sure our xvfb-run wrapper is executable
RUN chmod +x /usr/local/bin/xvfb-run-wrapper.sh

# Start the virtual frame buffer with google chrome running inside
CMD ["/usr/local/bin/xvfb-run-wrapper.sh"]
