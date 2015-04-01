<?php namespace ChromePrint;

use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Session\Session;

class XdoTool
{
	/**
	 * This refers to the X display number that be will be using.
	 */
	private $display;
	
	/**
	 * The location that we will instruct Google Chrome to print the PDF to.
	 *
	 * The filename will contain the display number so that we don't
	 * overwrite anyone elses files.
	 *
	 * It is stored on a shared volume, part of the storage container.
	 */
	private $pdfFile;
	
	/**
	 * A simple file that we use to keep count of how many X displays we have.
	 *
	 * Down the track this may get put into an Sqlite DB
	 * or other Key Value Store if we need to store other data as well.
	 *
	 * But for now it's a simple file with a number in it.
	 */
	private $displayCounter = '/tmp/chrome-print-displays';

	/**
	 * Xvfb Container Setup
	 *
	 * It goes without saying that 2 users would have an extremely hard time
	 * using the keyboard and mouse at the same time. The same is true when one
	 * sends xdotool commands simultanously to the same instance of Chrome.
	 * In short all hell breaks loss.
	 *
	 * Thus we use browser sessions to keep track of our xvfb containers.
	 * Each browser session get's it's own xvfb container and can only perform
	 * one document conversion at a time.
	 *
	 * So if you are using a basic "CURL" command or other tech that does not
	 * support cookies to make the requests to the REST service, please keep in
	 * mind that every request you make will start a new browser session and
	 * your document generation times will suffer as a result.
	 *
	 * If you maintain multiple browser sessions you can effetivly create as
	 * many xvfb workers as you like, just like say nginx or php-fpm.
	 * One day this worker management may become somewhat more transparent...
	 */
	public function __construct(Session $session)
	{
		// Has the session already been started?
		if ($session->has('display'))
		{
			// It has so just use the existing X display,
			// there is no need to start a new container.
			$this->display = $session->get('display');
		}
		else
		{
			// Keep track of how many X displays we have running.
			if (file_exists($this->displayCounter))
			{
				$this->display = file_get_contents($this->displayCounter) + 1;
			}
			else
			{
				// Start at 99 just like xvfb-run does by default
				$this->display = 99;
			}

			file_put_contents($this->displayCounter, $this->display);

			// Save the new display number to the session
			$session->set('display', $this->display);

			// Create a new instance of chrome.
			$this->runProcess
			(
				'docker run -d '.
				'--name chrome-print-xvfb-'.$this->display.' '.
				'--env DISPLAY='.$this->display.' '.
				'--volumes-from chrome-print-storage '.
				'--add-host docker:'.gethostbyname('docker').' '.
				'bradjones/chrome-print-xvfb'
			);

			// Wait for chrome to start up
			sleep(5);

			// Resize the chrome window
			$this->runProcess($this->grabChrome('windowmove --sync 0 0'));
			$this->runProcess($this->grabChrome('windowsize --sync 100% 100%'));

			// Wait for the window resize to take effect
			sleep(1);

			// Close the sandbox notice
			$this->setMousePos(1343, 79)->leftClick();

			// Open print preview
			$this->sendKeysToChrome('ctrl+p');

			// Wait for the preview to open
			// TODO: Instead of all this waiting bullshit lets use visgrep
			// To detect when certian things exist on the screen, etc.
			// http://manpages.ubuntu.com/manpages/dapper/man1/visgrep.1.html
			sleep(1);

			// Enable background graphics
			$this->setMousePos(140, 578)->leftClick();

			// Close the print preview box
			$this->setMousePos(226, 147)->leftClick();

			// Wait for print preview to close
			sleep(1);
		}
		
		// Set the file name of printed pdf
		$this->pdfFile = '/mnt/printed/document_'.$this->display.'.pdf';
		
		// This is important, if we don't do this we run
		// into the session locking issues.
		$session->save();
	}
	
	/**
	 * Prints a HTML document from the provided URL.
	 *
	 * This is the main heart of the whole operation. Basically we just
	 * manipulate the keyboard and mouse to operate Google Chrome just as though
	 * we were a normal user. I can see great potential for this,
	 * ie: phantomjs replacement... :)
	 *
	 * @param string $url A fully qualified url.
	 * @param int $wait The number of seconds to wait for the page to load.
	 *
	 * @return PDF Stream.
	 */
	public function printWithUrl($url, $size = 'a4', $layout = 'portrait', $wait = 1)
	{
		// Make sure google chrome is focused
		$this->runProcess($this->grabChrome('windowfocus'));
		
		// Then focus the address bar
		$this->setMousePos(130, 40)->leftClick();
		
		// Delete any existing url
		$this->sendKeys('ctrl+a')->sendKeys('Delete');
		
		// Type in the url and go to it
		$this->type($url)->sendKeys('Return');
		
		// Wait for the page to load
		sleep($wait);
		
		// Open print preview
		$this->sendKeysToChrome('ctrl+p');
		
		// Wait again for the print preview to load
		// It would seem the amount of time it takes for the print preview to
		// finish rendering is relative to the time it takes to load the page.
		sleep($wait);
		
		// Click the Layout drop down
		$this->setMousePos(304, 380)->leftClick();
		
		// Select either Portrait or Landscape
		switch (strtolower($layout))
		{
			case 'portrait': $this->setMousePos(304, 400)->leftClick(); break;
			case 'landscape': $this->setMousePos(304, 415)->leftClick(); break;
		}
		
		// Click the paper size drop down
		$this->setMousePos(304, 438)->leftClick();
		
		// Select the paper size
		switch (strtolower($size))
		{
			case 'a4': $this->setMousePos(304, 460)->leftClick(); break;
			case 'a3': $this->setMousePos(304, 475)->leftClick(); break;
			case 'letter': $this->setMousePos(304, 490)->leftClick(); break;
			case 'legal': $this->setMousePos(304, 500)->leftClick(); break;
			case 'tabloid': $this->setMousePos(304, 515)->leftClick(); break;
		}

		// Click on margins drop down
		$this->setMousePos(304, 496)->leftClick();
		
		// Select the None option
		// We force the margins to none so that we can use
		// CSS to provide ultimate control of the layout.
		$this->setMousePos(304, 533)->leftClick();

		// Now click the save button
		$this->setMousePos(290, 150)->leftClick();
		
		// Wait for the save file dialog box
		$this->runProcess($this->setDisplay('xdotool search --sync "Save File"'));
		
		// Then focus the name box
		$this->setMousePos(125, 25)->leftClick();
		
		// Delete any existing filename
		$this->sendKeys('ctrl+a')->sendKeys('Delete');
		
		// Type in the temp filename
		$this->type($this->pdfFile);

		// Wait for the filename to be typed in.
		sleep(1);
		
		// Click the save button
		$this->setMousePos(740, 550)->leftClick();
		
		// Wait for PDF file to exist
		while (!file_exists($this->pdfFile)){ usleep(100); }
		
		// Wait for pdf file to be finished writing
		// Because chrome is running in another container we can't use
		// lsof or other similar type solutions. I think the ideal solution
		// would be to use incond inside the xvfb container to write another
		// dummy file the second the pdf is closed by chrome. But this seems
		// to work for now.
		do
		{
			$pdf1 = file_get_contents($this->pdfFile);
			usleep(100);
			$pdf2 = file_get_contents($this->pdfFile);
		}
		while($pdf1 != $pdf2);
		
		// Delete printed pdf file
		unlink($this->pdfFile);
		
		// Shutdown and destroy the chrome container
		/*$this->runProcess
		(
			'docker stop chrome-print-xvfb-'.$this->display.' && '.
			'docker rm chrome-print-xvfb-'.$this->display
		);*/

		// Return pdf
		return $pdf1;
	}
	
	/**
	 * Takes a PNG screenshot of the X Virtual Frame Buffer.
	 *
	 * This is very useful in debugging, as normally you have no way of seeing
	 * what is actually happening. It's most useful feature is being able to
	 * work out wher to place the mouse.
	 *
	 * The idea being you take a screenshot, find the button, text box or other
	 * element the mouse needs to be placed on top of and then just measure the
	 * number of pixels from the top left corner to get your X and Y position.
	 *
	 * @return PNG Stream
	 */
	public function takeScreenShot()
	{
		$process = $this->runProcess
		(
			$this->setDisplay('import -window root -quality 100 png:-')
		);
		
		return $process->getOutput();
	}
	
	private function setDisplay($cmd)
	{
		return 'DISPLAY=:'.$this->display.' '.$cmd;
	}
	
	private function grabChrome($cmd)
	{
		return $this->setDisplay('xdotool search --sync "Google Chrome" '.$cmd);
	}
	
	private function setMousePos($x, $y)
	{
		$this->runProcess
		(
			$this->setDisplay('xdotool mousemove --sync '.$x.' '.$y)
		);
		
		return $this;
	}
	
	private function leftClick()
	{
		$this->runProcess
		(
			$this->setDisplay('xdotool click 1')
		);
		
		return $this;
	}
	
	private function sendKeys($keys)
	{
		$this->runProcess
		(
			$this->setDisplay('xdotool key --clearmodifiers '.$keys)
		);
		
		return $this;
	}
	
	private function sendKeysToChrome($keys)
	{
		$this->runProcess
		(
			$this->grabChrome('key --clearmodifiers '.$keys)
		);
		
		return $this;
	}
	
	private function type($text)
	{
		$this->runProcess
		(
			$this->setDisplay('xdotool type "'.$text.'"')
		);
		
		return $this;
	}
	
	private function runProcess($cmd)
	{
		$process = new Process($cmd);
		
		$process->run();
		
		if (!$process->isSuccessful())
		{
			throw new RuntimeException($process->getErrorOutput());
		}
		
		return $process;
	}
}
