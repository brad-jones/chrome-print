<?php namespace ChromePrint;

use RuntimeException;
use Symfony\Component\Process\Process;

class XdoTool
{
	/**
	 * Xvfb Container Setup
	 *
	 * It goes without saying that 2 users would have an extremely hard time
	 * using the keyboard and mouse at the same time. The same is true when one
	 * sends xdotool commands simultanously to the same instance of Chrome.
	 * In short all hell breaks loss.
	 *
	 * Thus we create a pool of xvfb workers much like nginx or php-fpm would.
	 * This constructor only ensures the right number of ready and waiting
	 * instances of Google Chrome exist.
	 */
	public function __construct()
	{
		// TODO: This logic needs to go into some sort of worker manager

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

		// Wait for X to start up
		$ready = false;
		do
		{
			try
			{
				// This will throw an exception if X is not ready yet
				$this->takeScreenShot();
				$ready = true;
			}
			catch (RuntimeException $e){}
		}
		while(!$ready);

		// Resize the chrome window
		$this->runProcess($this->grabChrome('windowmove --sync 0 0'));
		$this->runProcess($this->grabChrome('windowsize --sync 100% 100%'));
		
		// Wait for the no sandbox banner to appear
		$this->waitFor('no-sandbox');

		// Close the sandbox notice
		$this->setMousePos(1343, 79)->leftClick();

		// Wait for it to disappear
		$this->waitFor('no-sandbox', true);

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
	 * Prints a HTML document from the provided URL.
	 *
	 * This is the main heart of the whole operation. Basically we just
	 * manipulate the keyboard and mouse to operate Google Chrome just as though
	 * we were a normal user. I can see great potential for this,
	 * ie: phantomjs replacement... :)
	 *
	 * @param string $url A fully qualified url.
	 *
	 * @return PDF Stream.
	 */
	public function printWithUrl($url, $size = 'a4', $layout = 'portrait')
	{
		// TODO: Select a new display number from a list of ready displays

		// TODO: Mark that display as used

		// Make sure google chrome is focused
		$this->runProcess($this->grabChrome('windowfocus'));
		
		// Then focus the address bar
		$this->setMousePos(130, 40)->leftClick();
		
		// Delete any existing url
		$this->sendKeys('ctrl+a')->sendKeys('Delete');
		
		// Type in the url and go to it
		$this->type($url)->sendKeys('Return');
		
		// Wait for the page to load
		// The page must alert a message saying "PRINT ME"
		// when the page is ready to be printed.
		$this->waitFor('print-me', null, false);

		// Dismiss the print me alert
		$this->setMousePos(800, 175)->leftClick();

		// Wait for the alert to disappear
		$this->waitFor('print-me', true);
		
		// Open print preview
		$this->sendKeysToChrome('ctrl+p');
		
		// Wait for print preview screen to open
		$this->waitFor('save-as-pdf');
		
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
		$this->type($this->pdfFile)->sendKeys('Return');
		
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

		// Return pdf
		return $pdf1;
	}
	
	/**
	 * Takes a PNG screenshot of the X Virtual Frame Buffer.
	 *
	 * This is very useful in debugging, as normally you have no way of seeing
	 * what is actually happening. It's most useful feature is being able to
	 * work out where to place the mouse.
	 *
	 * The idea being you take a screenshot, find the button, text box or other
	 * element the mouse needs to be placed on top of and then just measure the
	 * number of pixels from the top left corner to get your X and Y position.
	 *
	 * @param int $display If supplied this will take a screenshot from a
	 *                     specfic X display. Instead of the display currently
	 *                     stored in the Session.
	 *
	 * @return PNG Stream
	 */
	public function takeScreenShot($display = 'session')
	{
		$process = $this->runProcess
		(
			$this->setDisplay
			(
				'import -window root -quality 100 png:-',
				$display
			)
		);
		
		return $process->getOutput();
	}
	
	private function setDisplay($cmd, $display = 'session')
	{
		if ($display == 'session')
		{
			return 'DISPLAY=:'.$this->display.' '.$cmd;
		}
		else
		{
			return 'DISPLAY=:'.$display.' '.$cmd;
		}
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

	private function waitFor($pattern, $notExist = false, $sync = true)
	{
		// Find the pattern file
		$pattern = __DIR__.'/visgrep/'.$pattern.'.pat';

		if (!file_exists($pattern))
		{
			throw new RuntimeException
			(
				'Pattern file does not exist: '.$pattern
			);
		}

		// Build the command to run
		$cmd = $this->setDisplay('visgrep /dev/stdin '.$pattern);

		// We are not ready until this loop passes true
		$ready = false;

		do
		{
			// Create a new process
			$process = new Process($cmd);

			// Take a new screenshot and pass it in via stdin
			$process->setInput($this->takeScreenShot());

			// Run the command
			$process->run();

			// An exist code of 2 actually means something went wrong.
			// If have also noticed visgrep doesn't always send errors
			// to stderr, hence outputting both in the exception.
			if ($process->getExitCode() == 2)
			{
				throw new RuntimeException
				(
					$process->getOutput().
					$process->getErrorOutput()
				);
			}

			// Did we find the pattern?
			// If this is empty, we couldn't find the pattern.
			// If this contains content we did find the pattern.
			$exists = !empty($process->getOutput());

			// Most of the time we need to check if an element exists on the
			// screen. Sometimes though we want to check if an element has
			// disappeared.
			if ($notExist)
			{
				$ready = !$exists;
			}
			else
			{
				$ready = $exists;
			}
		}
		while(!$ready);

		// This works in a similar way to the xdotool --sync option.
		// Even though our pattern has been found if the screen is still
		// changing we probably want to wait until it's finished doing whatever
		// we told it to do last. Most of the time this is something we want to
		// do however if a cursor is blinking in a text field we will get hang
		// up here.
		if ($sync)
		{
			do
			{
				$img1 = $this->takeScreenShot();
				usleep(100000); // 100ms
				$img2 = $this->takeScreenShot();
			}
			while($img1 != $img2);
		}

		return $this;
	}
}
