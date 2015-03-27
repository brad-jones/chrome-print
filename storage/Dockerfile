# Chrome Print - shared data container
# ==============================================================================
# This is a container that simply sets up a volume
# to be shared between all our other containers.
#
# Build the container: docker build -t chrome-print/storage:TAG .
#
# Create the container: docker create --name chrome-print-storage chrome-print/storage:TAG
#
# Then share the storage to all other Chrome Print containers:
# docker run -d --volumes-from chrome-print-storage --name chrome-print-xvfb chrome-print/xvfb:TAG
# docker run -d --volumes-from chrome-print-storage --name chrome-print-php-fpm chrome-print/php-fpm:TAG
# docker run -d --volumes-from chrome-print-storage --name chrome-print-nginx chrome-print/nginx:TAG

FROM fedora:latest
MAINTAINER Brad Jones <brad@bjc.id.au>

# Create the dir
RUN mkdir /chrome-print-data

# Add some intial data, this contains our main PHP code.
ADD ["intial", "/chrome-print-data"]

# Define the volume
VOLUME ["/chrome-print-data"]

# Run a command that indicates (trivial) success and exits
CMD ["/bin/true"]
