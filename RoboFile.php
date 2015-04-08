<?php

use Gears\String as Str;

class RoboFile extends Brads\Robo\Tasks
{
	/**
	 * Pull down all our images from docker hub. Use this to update your images.
	 */
	public function pull()
	{
		$this->taskExec('docker pull bradjones/chrome-print-storage')->run();
		$this->taskExec('docker pull bradjones/chrome-print-xvfb')->run();
		$this->taskExec('docker pull bradjones/chrome-print-php-fpm')->run();
		$this->taskExec('docker pull bradjones/chrome-print-nginx')->run();
		$this->taskExec('docker pull bradjones/chrome-print-xvfb-pool')->run();
	}
	
	/**
	 * Shortcut to create all containers.
	 */
	public function create()
	{
		$this->createStorage();
		$this->createPhpFpm();
		$this->createNginx();
		$this->createXvfbPool();
	}
	
	/**
	 * Creates the shared storage docker container.
	 */
	public function createStorage()
	{
		$this->taskExec('docker')
			->arg('create')
			->option('name', 'chrome-print-storage')
			->arg('bradjones/chrome-print-storage')
		->run();
	}
	
	/**
	 * Creates the php-fpm container.
	 */
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
	
	/**
	 * Creates the nginx container.
	 */
	public function createNginx()
	{
		$this->taskExec('docker')
			->arg('create')
			->arg('-P')
			->option('name', 'chrome-print-nginx')
			->option('volumes-from', 'chrome-print-storage')
			->option('restart', 'on-failure:10')
			->arg('bradjones/chrome-print-nginx')
		->run();
	}
	
	/**
	 * Creates our xvfb pool manager.
	 */
	public function createXvfbPool()
	{
		// Php needs access to the host docker process
		// to auto spawn the xvfb containers.
		$docker = getenv('CONDUCTOR_DOCKER_BIN');
		$socket = getenv('CONDUCTOR_DOCKER_SOCKET');
		
		$this->taskExec('docker')
			->arg('create')
			->option('name', 'chrome-print-xvfb-pool')
			->option('volumes-from', 'chrome-print-storage')
			->arg('-v '.$docker.':'.$docker)
			->arg('-v '.$socket.':'.$socket)
			->option('add-host', 'docker:'.getenv('CONDUCTOR_HOST'))
			->option('restart', 'on-failure:10')
			->arg('bradjones/chrome-print-xvfb-pool')
		->run();
	}
	
	/**
	 * Shortcut to start all our containers.
	 */
	public function start()
	{
		$this->startXvfbPool();
		$this->startPhpFpm();
		$this->startNginx();
	}
	
	/**
	 * Starts our xvfb pool manager, should be started first!
	 */
	public function startXvfbPool()
	{
		$this->taskDockerStart('chrome-print-xvfb-pool')->run();
	}
	
	/**
	 * Starts the created php-fpm container.
	 */
	public function startPhpFpm()
	{
		$this->taskDockerStart('chrome-print-php-fpm')->run();
	}
	
	/**
	 * Starts the created nginx container.
	 */
	public function startNginx()
	{
		$this->taskDockerStart('chrome-print-nginx')->run();
		
		// Grab the dynamic ports that have been exposed to the host.
		$containers = Str::s
		(
			$this->taskExec('docker')
				->arg('ps')
				->printed(false)
				->run()
				->getMessage()
		);
		
		// Find the line that ends with "chrome-print-nginx"
		foreach ($containers->split("\n") as $container)
		{
			$container = Str::s(trim($container->toString()));
			
			if ($container->endsWith('chrome-print-nginx'))
			{
				// Extract the ports
				$http = $container->wildCardMatch(', 0.0.0.0:*->80/tcp')[1][0];
				$https = $container->wildCardMatch('0.0.0.0:*->443/tcp')[1][0];
				
				// Display a nice little message
				$this->yell('Google Chrome Print has Started!');
				$this->say('You may access it by going to:');
				$this->say('http://'.getenv('CONDUCTOR_HOST').':'.$http);
				$this->say('OR');
				$this->say('https://'.getenv('CONDUCTOR_HOST').':'.$https);
			}
		}
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
	
	/**
	 * Stops the pool manager and all remaining instances of chrome-print-xvfb
	 */
	public function stopXvfb()
	{
		$this->taskDockerStop('chrome-print-xvfb-pool')->run();
		
		// Grab a list of running containers
		$containers = Str::s
		(
			$this->taskExec('docker')
				->arg('ps')
				->printed(false)
				->run()
				->getMessage()
		);
		
		// Find the lines that have "bradjones/chrome-print-xvfb:latest"
		foreach ($containers->split("\n") as $container)
		{
			if ($container->contains('bradjones/chrome-print-xvfb:latest'))
			{
				$matches = $container->match('/chrome-print-xvfb-\d+/');
				
				if (count($matches) > 0)
				{
					// Extract the container name
					$name = trim($matches[0]->toString());
					
					// Stop the container
					$this->taskDockerStop($name)->run();
				}
			}
		}
	}
	
	/**
	 * Stops the running php-fpm container.
	 */
	public function stopPhpFpm()
	{
		$this->taskDockerStop('chrome-print-php-fpm')->run();
	}
	
	/**
	 * Stops the running nginx container.
	 */
	public function stopNginx()
	{
		$this->taskDockerStop('chrome-print-nginx')->run();
	}
	
	/**
	 * Run start and then stop.
	 */
	public function restart()
	{
		$this->stop();
		$this->start();
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
	
	/**
	 * Removes the shared storage container, use with caution!
	 */
	public function removeStorage()
	{
		$this->taskExec('docker rm chrome-print-storage')->run();
	}
	
	/**
	 * Removes the pool manager and any remaining instances of chrome-print-xvfb
	 */
	public function removeXvfb()
	{
		$this->taskExec('docker rm chrome-print-xvfb-pool')->run();
		
		// Grab all containers
		$containers = Str::s
		(
			$this->taskExec('docker')
				->arg('ps')
				->arg('-a')
				->printed(false)
				->run()
				->getMessage()
		);
		
		// Find the lines that have "bradjones/chrome-print-xvfb:latest"
		foreach ($containers->split("\n") as $container)
		{
			if ($container->contains('bradjones/chrome-print-xvfb:latest'))
			{
				$matches = $container->match('/chrome-print-xvfb-\d+/');
				
				if (count($matches) > 0)
				{
					// Extract the container name
					$name = trim($matches[0]->toString());
					
					// Remove the container
					$this->taskExec('docker rm '.$name)->run();
				}
			}
		}
	}
	
	/**
	 * Removes a stoped php-fpm container.
	 */
	public function removePhpFpm()
	{
		$this->taskExec('docker rm chrome-print-php-fpm')->run();
	}
	
	/**
	 * Removes a stopped nginx container.
	 */
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
		$this->removeImagesXvfbPool();
		$this->removeImagesPhpFpm();
		$this->removeImagesNginx();
	}
	
	/**
	 * Forceably removes the storage image.
	 */
	public function removeImagesStorage()
	{
		$this->taskExec('docker rmi -f bradjones/chrome-print-storage')->run();
	}
	
	/**
	 * Forceably removes the xvfb image.
	 */
	public function removeImagesXvfb()
	{
		$this->taskExec('docker rmi -f bradjones/chrome-print-xvfb')->run();
	}
	
	/**
	 * Forceably removes the xvfb-pool image.
	 */
	public function removeImagesXvfbPool()
	{
		$this->taskExec('docker rmi -f bradjones/chrome-print-xvfb-pool')->run();
	}
	
	/**
	 * Forceably removes the php-fpm image.
	 */
	public function removeImagesPhpFpm()
	{
		$this->taskExec('docker rmi -f bradjones/chrome-print-php-fpm')->run();
	}
	
	/**
	 * Forceably removes the nginx image.
	 */
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
		$this->taskDockerBuild('xvfb-pool')->tag('bradjones/chrome-print-xvfb-pool')->run();
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
			->arg('create')
			->arg('-v '.getenv('CONDUCTOR_ROOT').'/storage/container-files/var/www/html:/var/www/html')
			->option('name', 'chrome-print-storage')
			->arg('bradjones/chrome-print-storage')
		->run();
		
		// Create the remaining containers
		$this->createXvfbPool();
		$this->createPhpFpm();
		$this->createNginx();
		
		// And start the containers
		$this->start();
	}
	
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
}
