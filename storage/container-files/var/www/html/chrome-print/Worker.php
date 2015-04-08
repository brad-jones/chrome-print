<?php namespace ChromePrint;

use RuntimeException;

class Worker extends XdoTool
{
	/**
	 * This will be the docker container id from the host docker process.
	 */
	protected $dockerId;
	
	/**
	 * Orchestrates the startup of a new XVFB Container.
	 *
	 * We use a pool file for each new container located in  /var/run/xvfb-pool
	 * This file is named with the docker id of the container. Inside the file
	 * is some JSON data. For now this data only has the X display number and a
	 * status flag.
	 *
	 * If the status flag is ```ready``` it means the PHP REST API can use the
	 * container to print a new document. The PHP REST API will then set the
	 * flag to ```expired``` when it has finished with that container.
	 */
	public function __construct($display = null)
	{
		parent::__construct($display);
		
		$this->dockerId = $this->bootContainer();
		
		$this->updatePoolFile('booting');
		
		$this->waitUntilBooted();
		
		$this->updatePoolFile('booted');
		
		$this->maximiseChrome();
		$this->dismissNoSandboxNotice();
		$this->enableBackgroundGraphics();
		
		$this->updatePoolFile('ready');
	}
	
	/**
	 * Getter for the docker id.
	 *
	 * @return string
	 */
	public function getDockerId()
	{
		return $this->dockerId;
	}
	
	/**
	 * Starts a new xvfb container on the docker host.
	 *
	 * @return string The docker id of the container.
	 */
	protected function bootContainer()
	{
		// Create a new instance of chrome.
		$process = $this->runProcess
		(
			'docker run -d '.
			'--name chrome-print-xvfb-'.$this->display.' '.
			'--env DISPLAY='.$this->display.' '.
			'--volumes-from chrome-print-storage '.
			'--add-host docker:'.gethostbyname('docker').' '.
			'bradjones/chrome-print-xvfb'
		);
		
		// Return the docker id
		return trim($process->getOutput());
	}
	
	/**
	 * Waits until the X Display is ready.
	 *
	 * > NOTE: This doesn't mean Google Chrome has started.
	 */
	protected function waitUntilBooted()
	{
		do
		{
			try
			{
				// This will throw an exception if X is not ready yet
				$this->takeScreenShot();
				$ready = true;
			}
			catch (RuntimeException $e)
			{
				$ready = false;
			}
		}
		while(!$ready);
	}
	
	/**
	 * Ensures Google Chrome is maximised.
	 *
	 * This is critically important for mouse positions.
	 */
	protected function maximiseChrome()
	{
		$this->runProcess($this->grabChrome('windowmove --sync 0 0'));
		$this->runProcess($this->grabChrome('windowsize --sync 100% 100%'));
		usleep(500000);
	}
	
	/**
	 * Chrome shows a warning about using the --no-sandbox argument.
	 * This closes the banner message. It's not super important but does
	 * declutter the window and reduces the risk of mucking up X,Y positions.
	 */
	protected function dismissNoSandboxNotice()
	{
		// Wait for the no sandbox banner to appear
		$this->waitFor('no-sandbox');
		
		// Close the sandbox notice
		$this->setMousePos(1343, 79)->leftClick();
		
		// Wait for it to disappear
		$this->waitFor('no-sandbox', true);
	}
	
	/**
	 * Enables the Background Graphics Print Option
	 *
	 * Google Chrome will remember this setting for future prints.
	 * So we make sure the option is enabled at boot to speed up the printing
	 * process.
	 */
	protected function enableBackgroundGraphics()
	{
		// Open print preview
		$this->sendKeysToChrome('ctrl+p');
		
		// Wait for the preview to open
		$this->waitFor('save-as-pdf');
		
		// Enable background graphics
		$this->setMousePos(140, 578)->leftClick();
		
		// Close the print preview box
		$this->setMousePos(226, 147)->leftClick();
		
		// Wait for print preview to close.
		$this->waitFor('save-as-pdf', true);
	}
	
	/**
	 * Updates the pool file so the REST API knows whats happening.
	 *
	 * @param string $status
	 */
	protected function updatePoolFile($status)
	{
		file_put_contents('/var/run/xvfb-pool/'.$this->dockerId, json_encode
		([
			'display' => $this->display,
			'status' => $status
		]));
		
		chmod('/var/run/xvfb-pool/'.$this->dockerId, 0777);
	}
}
