# Chrome Print - xvfb
# ==============================================================================
# This container runs Google Chrome inside an X Virtual Frame Buffer or XVFB.

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# Update base image
RUN yum -y update && yum clean all

# Install the google chrome repo before running yum install
ADD ["container-files/etc/yum.repos.d","/etc/yum.repos.d"]

# Install packages
RUN yum install -y \
	google-chrome-stable \
	xorg-x11-server-Xvfb \
	&& yum clean all

# Install our xvfb-run wrapper
ADD ["container-files/usr/local/bin","/usr/local/bin"]
RUN chmod +x /usr/local/bin/xvfb-run-wrapper.sh

RUN useradd -ms /bin/bash chrome
RUN chown -R chrome:chrome /home/chrome
USER chrome
ENV HOME /home/chrome

# Start the virtual frame buffer with google chrome running inside
CMD ["/usr/local/bin/xvfb-run-wrapper.sh"]
