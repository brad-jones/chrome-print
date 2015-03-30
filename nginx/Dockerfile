# Chrome Print - nginx
# ==============================================================================
# This container runs nginx and is the frontend for our rest api.

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# Add our container files
ADD ["container-files","/"]

# Install packages
RUN yum -y update && yum -y install \
	nginx \
	&& yum clean all

# Publish standard http and httpd ports
EXPOSE 80 443

# Start nginx
CMD ["/usr/sbin/nginx"]
