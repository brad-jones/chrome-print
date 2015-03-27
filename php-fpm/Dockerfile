# Chrome Print - php-fpm
# ==============================================================================
# This container runs php-fpm and xdotool to communicate to the xvfb container
# runnig Google Chrome. The main PHP code that provides the REST API to easily
# convert HTML document into PDF documents is part of this container.

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# Update base image
RUN yum update -y && yum clean all

# Install packages
RUN yum install -y \
	php \
	php-fpm \
	xdotool \
	&& yum clean all

# Add PHP-FPM config
ADD ["php-fpm-config", "/etc"]

# Start php fpm
CMD ["/usr/sbin/php-fpm"]
