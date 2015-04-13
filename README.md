Google Chrome Print
================================================================================
A set of docker containers that automate Google Chrome using XVFB, Xdotool,
Visgrep and other peices of tech to convert HTML documents into PDF Documents.

__THIS IS AN EXPERIMENT, DO NOT USE IN PRODUCTION__

Why?
--------------------------------------------------------------------------------
I have another project [Gears\Pdf](https://github.com/phpgearbox/pdf) that uses
[phantomjs](http://phantomjs.org/) to convert HTML to PDF. This is what I use
in production but the problem is that it is hard to debug layout issues because
_phantomjs_ is not the same rendering engine as _chrome_, it uses a much older
version of _webkit_.

I have also used [wkhtmltopdf](http://wkhtmltopdf.org/) with varying results.
While they do use a patched version of _webkit_ which supports a few extra
things, like custom fonts, it still suffers from the same problem.

I can't develop the HTML/CSS using a standard browser. I can get close using a
normal browser but then need to spend time generating many PDF's to fix and
tweak layout bugs.

One day I then read an article about controlling GUI apps with XVFB and XDOTOOL.
I was also just starting to play with [docker](https://www.docker.com/).
My thinking was that if we can just use the latest version of Chrome to generate
the PDF then I could use my workstation instance of Chrome in my document
development workflows like any other web page.

How?
--------------------------------------------------------------------------------
First there is the main ```RoboFile.php```, this is used in conjunction
with another _docker_ project of mine called
[conductor](https://github.com/brad-jones/conductor)

This provides the _"glue"_ between all the docker containers.

  - storage: The first container is the storage container.
    This provides several mount points that get shared to all other containers.
  
  - nginx: This runs an instance of nginx which serves files from /var/www/html
    Which is shared via the storage container.
  
  - php-fpm: We run the php fast cgi process manager in this container.
    It is configured to communicate to nginx via a unix socket, that is shared
    via the storage container: /var/run/php-fpm.sock
  
  - xvfb: The container that houses Google Chrome running inside a virtual
    frame buffer setup by xvfb-run. The xvfb-pool container will spawn new
    instances of this container as needed.
  
  - xvfb-pool: This is just a PHP script that enters into a never ending loop.
    It manages the xvfb pool files located in /var/run/xvfb-pool, again shared
    via the storage container. The php REST api will look in here for instances
    of the xvfb contaienr that are booted and ready for use. This pool manager
    script will automatically create new xvfb containers and remove expired
    containers. This makes the REST requests as fast as possible.

Most of the actual logic for controlling Google Chrome is contained int he php
REST api. There is a class ```XdoTool.php``` that is basically a wrapper for
[xdotool](http://www.semicomplete.com/projects/xdotool/) a command which sends
keyboard and mouse events to an X server.

The Reality
--------------------------------------------------------------------------------
The reality is that this is simply too slow and error prone.
If anything this project taught me more about docker containers than anything.

I had it running beautifully on my workstation and then tried to deploy it too
an Amazon EC2 instance and it just refused to work. Im sure if I upgraded the
EC2 instance with some more RAM, maybe an extra core or 2 it would have worked
but it wasn't my instance to upgrade.

The issues stem from the fact that because we are dealing with a GUI
application timing of sending key strokes and mouse clicks is absolutely
crtitical. If the button has not yet been drawn to the frame buffer we can't
click it, which then just snowballs.

I tried to mitigate this where possible by using
[visgrep](http://hoopajoo.net/projects/xautomation.html)

Then not mention the fact that took on the order of ~5 seconds to actually print
a PDF, compared to less than 1 second when using phantomjs.

> If [selenium](http://www.seleniumhq.org/) ever adds an option to Print pages
> then maybe this might be worth looking at again.

