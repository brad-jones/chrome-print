<?php

class RoboFile extends Brads\Robo\Tasks
{
	public function convert($html)
	{

	}

	/**
	 * Pull down all our images from docker hub.
	 */
	public function pull()
	{
		$this->taskDockerPull('bradjones/chrome-print-storage')->run();
		$this->taskDockerPull('bradjones/chrome-print-xvfb')->run();
		$this->taskDockerPull('bradjones/chrome-print-php-fpm')->run();
		$this->taskDockerPull('bradjones/chrome-print-nginx')->run();
	}
	
	/**
	 * Now lets run our containers and link them together in the correct order.
	 */
	public function run()
	{
		// Create but do not run the storage container
		$this->taskExec('docker')
			->arg('create')
			->option('name', 'chrome-print-storage')
			->arg('bradjones/chrome-print-storage')
		->run();
		
		// Start the x virtual frame buffer and google chrome
		$this->taskDockerRun('bradjones/chrome-print-xvfb')
			->name('chrome-print-xvfb')
			->option('volumes-from', 'chrome-print-storage')
			->option('restart', 'on-failure:10')
			->detached()
		->run();
		
		// Start php-fpm
		$this->taskDockerRun('bradjones/chrome-print-php-fpm')
			->name('chrome-print-php-fpm')
			->option('volumes-from', 'chrome-print-storage')
			->option('restart', 'on-failure:10')
			->detached()
		->run();
		
		// Start nginx
		$this->taskDockerRun('bradjones/chrome-print-nginx')
			->name('chrome-print-nginx')
			->option('volumes-from', 'chrome-print-storage')
			->option('restart', 'on-failure:10')
			->publish(8081, 80)
			->detached()
		->run();
		
		$this->say('Go to http://localhost:8081/');
	}
	
	/**
	 * Starts our containers using the config defined in the run command.
	 */
	public function start()
	{
		$this->taskDockerStart('chrome-print-xvfb')->run();
		$this->taskDockerStart('chrome-print-php-fpm')->run();
		$this->taskDockerStart('chrome-print-nginx')->run();
	}
	
	/**
	 * Stops all our containers.
	 */
	public function stop()
	{
		$this->taskDockerStop('chrome-print-xvfb')->run();
		$this->taskDockerStop('chrome-print-php-fpm')->run();
		$this->taskDockerStop('chrome-print-nginx')->run();
	}
	
	/**
	 * Removes all our containers.
	 */
	public function remove($opts = ['destroy-data' => false])
	{
		// NOTE: There appears to be a bug with $this->taskDockerRemove()
		
		if ($opts['destroy-data'])
		{
			$this->taskExec('docker rm chrome-print-storage')->run();
		}
		
		$this->taskExec('docker rm chrome-print-xvfb')->run();
		$this->taskExec('docker rm chrome-print-php-fpm')->run();
		$this->taskExec('docker rm chrome-print-nginx')->run();
	}
}
