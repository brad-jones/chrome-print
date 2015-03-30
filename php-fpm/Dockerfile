# Chrome Print - php-fpm
# ==============================================================================
# This container runs php-fpm and communicates with nginx via a unix socket.
# Basically this is the container that houses the main php rest api.

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# Add our container files
ADD ["container-files","/"]

# Install packages
RUN yum -y update && yum -y install \
	xdotool \
	php-cli \
	php-fpm \
	&& yum clean all

# Start php-fpm
CMD ["/usr/sbin/php-fpm"]
