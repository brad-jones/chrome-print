# Chrome Print - php-fpm
# ==============================================================================
# This container runs php-fpm and communicates with nginx via a unix socket.
# Basically this is the container that houses the main php rest api.

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# Update base image
RUN yum -y update && yum clean all

# Install packages
RUN yum -y install \
	xdotool \
	ImageMagick \
	php-cli \
	php-fpm \
	php-gd \
	&& yum clean all

# Add our container files
ADD ["container-files","/"]

# Make our on-boot hook executable
RUN chmod +x /usr/local/bin/on-boot

RUN useradd -ms /bin/bash chrome
RUN chown -R chrome:chrome /home/chrome

# Start php-fpm
CMD /usr/local/bin/on-boot && /usr/sbin/php-fpm
