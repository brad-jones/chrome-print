<?php

use Gears\String as Str;

class RoboFile extends Brads\Robo\Tasks
{
	private $http_port = '8081';
	private $https_port = '8082';
	
	/**
	 * Converts a HTML document into a PDF document using Google Chrome.
	 *
	 * @param string $html This takes a few diffrent options.
	 *
	 *                     If set to "stdin", we will read from stdin.
	 *
	 *                     Or it can be a valid filepath, keeping in mind this
	 *                     RoboFile is being run inside a container and only has
	 *                     access to files and folders below the current
	 *                     location.
	 *
	 *                     Or you can provide a fully qualified URL.
	 */
	public function convert($html, $pdf = 'stdout')
	{
		// Read in our html document
		if (strtolower($html) == 'stdin')
		{
			$html = ''; while(!feof(STDIN)) { $html .= fgets(STDIN, 4096); }
		}
		elseif (file_exists($html) || Str::contains($html, '://'))
		{
			$html = file_get_contents($html);
		}
		else
		{
			throw new RuntimeException
			(
				'You must provide a HTML document to convert to PDF!'
			);
		}
		
		// Grab a list of all the docker containers on the host
		$containers = Str::s
		(
			$this->taskExec('docker')
				->arg('ps')
				->arg('-a')
				->printed(false)
				->run()
				->getMessage()
		);

		// Does it have a storage container
		if (!$containers->contains('chrome-print-storage'))
		{
			// Nope so lets create it
			$this->createStorage();
		}

		// Does it have a xvfb container
		if (!$containers->contains('chrome-print-xvfb'))
		{
			// Nope so lets create it
			$this->createXvfb();
		}

		// Grab a list of all the "running" containers on the host
		$runningContainers = Str::s
		(
			$this->taskExec('docker')
				->arg('ps')
				->printed(false)
				->run()
				->getMessage()
		);

		// Is the xvfb container already running?
		if (!$runningContainers->contains('chrome-print-xvfb'))
		{
			// Nope so lets start it
			$this->startXvfb();

			// NOTE: We never shut it down because it can be reused,
			// it can take a few seconds for the virtual frame buffer and
			// Google Chrome to startup. Thus subsequent calls to this command
			// should be faster. If you do want to explicity shutdown the xvfb
			// container you can run ./conductor stop:xvfb
			// And if you then want to remove it also: ./conductor remove:xvfb
		}
		
		// Now lets run a temporary version of the php-fpm container.
		// This container has xdotool and all other needed libs.
		// It seemed a waste to create yet another container just for this.
		
		/*
		$result = $this->taskDockerRun('bradjones/chrome-print-php-fpm')
			->interactive()
			->option('rm')
			->volume('/path/to/data', '/mnt/src-document')
			->exec('/var/www/html/bin/chrome-print')
			->printed(false)
			->run()
		->getMessage();
		
		var_dump($result);
		*/
	}
	
	/**
	 * Pull down all our images from docker hub. Use this to update your images.
	 */
	public function pull()
	{
		$this->taskExec('docker pull bradjones/chrome-print-storage')->run();
		$this->taskExec('docker pull bradjones/chrome-print-xvfb')->run();
		$this->taskExec('docker pull bradjones/chrome-print-php-fpm')->run();
		$this->taskExec('docker pull bradjones/chrome-print-nginx')->run();
	}
	
	/**
	 * Shortcut to create all containers.
	 */
	public function create()
	{
		$this->createStorage();
		$this->createXvfb();
		$this->createPhpFpm();
		$this->createNginx();
	}

	public function createStorage()
	{
		$this->taskExec('docker')
			->arg('create')
			->option('name', 'chrome-print-storage')
			->arg('bradjones/chrome-print-storage')
		->run();
	}

	public function createXvfb()
	{
		$this->taskExec('docker')
			->arg('create')
			->option('name', 'chrome-print-xvfb')
			->option('volumes-from', 'chrome-print-storage')
			->option('restart', 'on-failure:10')
			->arg('bradjones/chrome-print-xvfb')
		->run();
	}

	public function createPhpFpm()
	{
		$this->taskExec('docker')
			->arg('create')
			->option('name', 'chrome-print-php-fpm')
			->option('volumes-from', 'chrome-print-storage')
			->option('restart', 'on-failure:10')
			->arg('bradjones/chrome-print-php-fpm')
		->run();
	}

	public function createNginx()
	{
		$this->taskExec('docker')
			->arg('create')
			->option('name', 'chrome-print-nginx')
			->option('volumes-from', 'chrome-print-storage')
			->option('restart', 'on-failure:10')
			->option('-p', $this->http_port.':80')
			->option('-p', $this->https_port.':443')
			->arg('bradjones/chrome-print-nginx')
		->run();
	}
	
	/**
	 * Shortcut to start all our containers.
	 */
	public function start()
	{
		$this->startXvfb();
		$this->startPhpFpm();
		$this->startNginx();
	}

	public function startXvfb()
	{
		$this->taskDockerStart('chrome-print-xvfb')->run();
	}

	public function startPhpFpm()
	{
		$this->taskDockerStart('chrome-print-php-fpm')->run();
	}

	public function startNginx()
	{
		$this->taskDockerStart('chrome-print-nginx')->run();
	}
	
	/**
	 * Shortcut to stop all our containers.
	 */
	public function stop()
	{
		$this->stopXvfb();
		$this->stopPhpFpm();
		$this->stopNginx();
	}

	public function stopXvfb()
	{
		$this->taskDockerStop('chrome-print-xvfb')->run();
	}

	public function stopPhpFpm()
	{
		$this->taskDockerStop('chrome-print-php-fpm')->run();
	}

	public function stopNginx()
	{
		$this->taskDockerStop('chrome-print-nginx')->run();
	}
	
	/**
	 * Shortcut to removes all our containers.
	 */
	public function remove($opts = ['destroy-data' => false])
	{
		// NOTE: There appears to be a bug with $this->taskDockerRemove()
		
		if ($opts['destroy-data'])
		{
			$this->removeStorage();
		}
		
		$this->removeXvfb();
		$this->removePhpFpm();
		$this->removeNginx();
	}

	public function removeStorage()
	{
		$this->taskExec('docker rm chrome-print-storage')->run();
	}

	public function removeXvfb()
	{
		$this->taskExec('docker rm chrome-print-xvfb')->run();
	}

	public function removePhpFpm()
	{
		$this->taskExec('docker rm chrome-print-php-fpm')->run();
	}

	public function removeNginx()
	{
		$this->taskExec('docker rm chrome-print-nginx')->run();
	}
	
	/**
	 * Shortcut to removes all our images. Dev use only!
	 *
	 * This is pretty extreme, we would only use this if we needed
	 * to bust some docker cache.
	 */
	public function removeImages()
	{
		$this->removeImagesStorage();
		$this->removeImagesXvfb();
		$this->removeImagesPhpFpm();
		$this->removeImagesNginx();
	}

	public function removeImagesStorage()
	{
		$this->taskExec('docker rmi -f bradjones/chrome-print-storage')->run();
	}

	public function removeImagesXvfb()
	{
		$this->taskExec('docker rmi -f bradjones/chrome-print-xvfb')->run();
	}

	public function removeImagesPhpFpm()
	{
		$this->taskExec('docker rmi -f bradjones/chrome-print-php-fpm')->run();
	}

	public function removeImagesNginx()
	{
		$this->taskExec('docker rmi -f bradjones/chrome-print-nginx')->run();
	}
	
	/**
	 * Build all the images. Dev use only!
	 *
	 * This should only be used if developing the images.
	 * For production use the pulled images from docker hub.
	 */
	public function build()
	{
		$this->taskDockerBuild('storage')->tag('bradjones/chrome-print-storage')->run();
		$this->taskDockerBuild('xvfb')->tag('bradjones/chrome-print-xvfb')->run();
		$this->taskDockerBuild('php-fpm')->tag('bradjones/chrome-print-php-fpm')->run();
		$this->taskDockerBuild('nginx')->tag('bradjones/chrome-print-nginx')->run();
	}
	
	/**
	 * Stops, removes, builds, creates & starts. Dev use only!
	 */
	public function reload($opts = ['destroy-images' => false])
	{
		// First make sure any previous containers are stopped
		$this->stop();
		
		// Next remove those containers
		$this->remove(['destroy-data' => true]);
		
		// Do a full rebuild, remove the actual images as well
		if ($opts['destroy-images']) $this->removeImages();
		
		// Now build some new images
		$this->build();
		
		// While developing we want the main www root bind mounted to the host.
		// So that as we make changes to the php it is reflected striaght away
		// like you would be used to in a normal non docker environment.
		$this->taskExec('docker')
			->arg('run')
			->arg('-d')
			->arg('-v '.getenv('CONDUCTOR_ROOT').'/storage/container-files/var/www/html:/var/www/html')
			->option('name', 'chrome-print-storage')
			->arg('bradjones/chrome-print-storage')
			->arg('/bin/sh')
		->run();
		
		// Create the remaining containers
		$this->createXvfb();
		$this->createPhpFpm();
		$this->createNginx();
		
		// And start the containers
		$this->start();
	}
}
