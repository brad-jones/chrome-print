# Chrome Print - storage
# ==============================================================================
# This container provides some shared storage space for our other containers.
# Out of the box all communication is done via unix sockets not tcp.
# For smaller single host setups this should work fine. For larger deployments
# you may like to reconfigure the services to talk via TCP.

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# The main php rest api lives in /var/www/html
# All logs from all containers should end up in /var/log,
# you may like to setup something like: : http://logstash.net/
# Our shared sockets will reside in /var/run
VOLUME ["/var/www/html", "/var/log", "/var/run", "/tmp/.X11-unix"]

# Add an intial data to the shared volumnes above.
ADD ["container-files","/"]

# Even though we never actually run this container,
# docker build/create still likes to see a valid executable.
CMD ["/bin/true"]
