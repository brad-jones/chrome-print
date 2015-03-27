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
	nginx \
	&& yum clean all

# Add Nginx config
ADD ["nginx-config", "/etc/nginx"]

# Expose HTTP
EXPOSE 80

# Start nginx
CMD ["/usr/sbin/nginx"]
