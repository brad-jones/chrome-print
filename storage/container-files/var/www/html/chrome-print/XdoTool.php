<?php namespace ChromePrint;

use RuntimeException;
use Symfony\Component\Process\Process;

class XdoTool
{
	/**
	 * This refers to the X display number, by default xvfb-run uses 99.
	 */
	private $display = '99';
	
	/**
	 * The location that we will instruct Google Chrome to print the PDF to.
	 * NOTE: It is a shared volume, part of the storage container.
	 */
	private $pdfFile = '/mnt/printed/document.pdf';
	
	/**
	 * Makes sure Chrome is in a state that is ready for use.
	 *
	 * > NOTE: This constructor will only run once per boot.
	 * > The file ```/tmp/google-chrome-setup``` will be deleted by
	 * > the CMD set in the Dockerfile.
	 *
	 * It is important that the chrome window is maximised
	 * so that mouse pointer locations are accurate.
	 */
	public function __construct()
	{
		// We only need to perform this the once for each boot
		if (file_exists('/tmp/google-chrome-setup')) return;
		
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
		sleep(1);
		
		// Enable background graphics
		$this->setMousePos(140, 578)->leftClick();
		
		// Close the print preview box
		$this->setMousePos(226, 147)->leftClick();
		
		// Wait for print preview to close
		sleep(1);
		
		// Write our temp file so we know that next time we can skip this
		touch('/tmp/google-chrome-setup');
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
		
		// Wait for the layout and paper size settings to take effect.
		// When we set layout and paper size it resets the margin dropdown box.
		sleep(1);
		
		// Click on margins drop down
		$this->setMousePos(304, 496)->leftClick();
		
		// Select the None option
		// We force the margins to none so that we can use
		// CSS to provide ultimate control of the layout.
		$this->setMousePos(304, 533)->leftClick();
		
		sleep(1);
		
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

		sleep(1);
		
		// Click the save button
		$this->setMousePos(740, 550)->leftClick();
		
		// Wait for PDF to be generated
		sleep(5);
		
		// Read pdf
		$pdf = file_get_contents($this->pdfFile);
		
		// Delete printed pdf file
		unlink($this->pdfFile);
		
		// Return pdf
		return $pdf;
	}
	
	/**
	 * Takes a PNG screenshot of the X Virtaul Frame Buffer.
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
