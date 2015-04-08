<?php namespace ChromePrint;

use RuntimeException;
use Symfony\Component\Finder\Finder;

class Printer extends XdoTool
{
	/**
	 * This will point to the location of the Pool File that represents
	 * the xvfb container we will be using for the printing.
	 */
	protected $poolFile;
	
	/**
	 * This will point to a filename that we will tell Google Chrome
	 * to save the PDF to. It needs to be unique because we might print
	 * several documents at the same time.
	 */
	protected $pdfFile;
	
	/**
	 * Printer Setup
	 *
	 * Unless a custom display number has been provided we will find a display
	 * to use from our pool for xvfb containers. Once we have found a container
	 * from the pool we mark it as ```in-use```.
	 *
	 * @param int $display Optional
	 */
	public function __construct($display = null)
	{
		// If no display has been set then lets find one from our pool
		// NOTE: That if a custom display is set then this class will
		// do nothing in terms of pool management.
		if (empty($display))
		{
			foreach (Finder::create()->files()->in('/var/run/xvfb-pool') as $file)
			{
				$worker = json_decode($file->getContents(), true);
				
				if ($worker['status'] == 'ready')
				{
					$this->poolFile = $file->getRealpath();
					
					$display = $worker['display'];
					
					file_put_contents($this->poolFile, json_encode
					([
						'display' => $display,
						'status' => 'in-use'
					]));
					
					break;
				}
			}
			
			if (empty($display))
			{
				throw new RuntimeException('No workers avaliable!');
			}
		}
		
		parent::__construct($display);
		
		$this->pdfFile = '/mnt/printed/document-'.$this->display.'.pdf';
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
		// Make sure google chrome is focused
		$this->runProcess($this->grabChrome('windowfocus'));
		
		// Then focus the address bar
		$this->setMousePos(130, 40)->leftClick();
		
		// Delete any existing url
		$this->sendKeys('ctrl+a')->sendKeys('Delete');
		
		// Type in the url and go to it
		$this->type($url)->sendKeys('Return');
		
		// Wait for the page to load
		// The page must alert a message saying "PRINT ME" when the page is
		// ready to be printed. We will wait no longer the 10 seconds.
		$this->waitFor('print-me', null, false, 10);
		
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
		
		// Expire the container
		if (!empty($this->poolFile))
		{
			file_put_contents($this->poolFile, json_encode
			([
				'display' => $this->display,
				'status' => 'expired'
			]));
		}
		
		// Return pdf
		return $pdf1;
	}
}
