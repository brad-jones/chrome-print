#!/usr/bin/php
<?php

// This script it basically going to log everything to STDOUT
echo "======================================================================\n";
echo "CHROME PRINT XVFB POOL MANAGER\n";
echo "======================================================================\n";
echo "\n";

echo "Loading script dependencies... ";

// Include composer
require('vendor/autoload.php');

// Import some classes
use ChromePrint\Worker;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

echo "DONE\n";

// Setup some basic signal handling early on
declare(ticks = 1);
pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT, "signal_handler");
function signal_handler($signal)
{
	switch($signal)
	{
		case SIGTERM:
		case SIGKILL:
		case SIGINT:
			
			echo "Exiting...";
			
			// Delete the display counter so that next time
			// we start, we start the intial containers.
			if (file_exists('/tmp/x-display-counter'))
			{
				unlink('/tmp/x-display-counter');
			}
			
			// Stop all other xvfb containers
			foreach (Finder::create()->files()->in('/var/run/xvfb-pool') as $file)
			{
				unlink($file->getRealPath());
				(new Process('docker stop '.$file->getFilename()))->run();
				(new Process('docker rm '.$file->getFilename()))->run();
			}
			
			echo "GoodBye\n";
			
			exit();
			
		break;
	}
}

// This handles the case where the script has died for some reason and docker
// has restarted us. In that instance we do need to create the intial workers.
if (file_exists('/tmp/x-display-counter'))
{
	$xDisplayCounter = file_get_contents('/tmp/x-display-counter');
	echo "Resuming operations, X display count = ".$xDisplayCounter."\n";
}
else
{
	// Keep count of the X displays
	// We start at 99 just like xvfb-run does by default
	$xDisplayCounter = 99;
	
	// Create our intial workers
	echo "Starting intial workers...\n";
	for ($i = 0; $i < getenv('START_WORKERS'); $i++)
	{
		$worker = new Worker($xDisplayCounter);
		
		echo "- worker: display=".$worker->getXDisplayNo();
		echo " container=".$worker->getDockerId()."\n";
		
		$xDisplayCounter++;
		
		file_put_contents('/tmp/x-display-counter', $xDisplayCounter);
	}
	
	echo "DONE\n";
}

// Now start a never ending loop.
// If we run into any PHP fatal errors, we are relying on
// dockers restart policies to get us going again.
while (true)
{
	// Read in our current pool
	$pool = [];
	foreach (Finder::create()->files()->in('/var/run/xvfb-pool') as $file)
	{
		$pool[$file->getFilename()] = json_decode($file->getContents(), true);
	}
	
	// Find all containers that have been marked expired, stop and remove them.
	$current_workers = 0;
	foreach ($pool as $id => $worker)
	{
		if ($worker['status'] == 'expired')
		{
			echo "Removing expired worker (display=".$worker['display']." container=".$id.")... ";
			
			// Delete the pool file
			unlink('/var/run/xvfb-pool/'.$id);
			
			// Stop the container
			$process = new Process('docker stop '.$id);
			$process->run();
			if (!$process->isSuccessful())
			{
				throw new RuntimeException($process->getErrorOutput());
			}
			
			// Remove the container
			$process = new Process('docker rm '.$id);
			$process->run();
			if (!$process->isSuccessful())
			{
				throw new RuntimeException($process->getErrorOutput());
			}
			
			echo "DONE\n";
		}
		else
		{
			$current_workers++;
		}
	}
	
	// If we have reached our limit of workers
	// we have nothing to do on this iteration.
	if ($current_workers > getenv('MAX_WORKERS'))
	{
		echo "Reached MAX_WORKERS limit!\n\n";
		continue;
	}
	
	// Count how many workers in the ready state
	$ready_workers = 0;
	foreach ($pool as $id => $worker)
	{
		if ($worker['status'] == 'ready')
		{
			$ready_workers++;
		}
	}
	
	// Check to see if we have enough spare workers
	if ($ready_workers < getenv('MIN_SPARE_WORKERS'))
	{
		echo "Starting new worker ";
		
		$worker = new Worker($xDisplayCounter);
		$xDisplayCounter++;
		file_put_contents('/tmp/x-display-counter', $xDisplayCounter);
		
		echo "(display=".$worker->getXDisplayNo();
		echo " container=".$worker->getDockerId().")... DONE\n";
	}
}
