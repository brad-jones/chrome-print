<?php namespace ChromePrint;

use RuntimeException;
use Symfony\Component\Process\Process;

class XdoTool
{
	/**
	 * The X display number we will be talking to.
	 */
	protected $display;
	
	/**
	 * XdoTool Constructor
	 *
	 * @param int $display All methods of this class work with a particular X
	 *                     display. You need to supply this number.
	 */
	public function __construct($display = null)
	{
		$this->display = $display;
	}
	
	/**
	 * Getter for the X Display Number
	 *
	 * @return int
	 */
	public function getXDisplayNo()
	{
		return $this->display;
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
	
	/**
	 * Set the position of the mouse on the screen.
	 *
	 * > NOTE: 0,0 in the top left corner.
	 *
	 * @param int $x The number of pixels in the x-plain.
	 * @param int $y The number of pixels in the y-plain.
	 * @return self For method chaining.
	 */
	public function setMousePos($x, $y)
	{
		$this->runProcess
		(
			$this->setDisplay('xdotool mousemove --sync '.$x.' '.$y)
		);
		
		return $this;
	}
	
	/**
	 * Sends a standard left mouse click at the mouses current position.
	 *
	 * @return self For method chaining.
	 */
	public function leftClick()
	{
		$this->runProcess
		(
			$this->setDisplay('xdotool click 1')
		);
		
		// This is important, we get some race conditions
		// if we don't slow things down a little.
		usleep(100000);
		
		return $this;
	}
	
	/**
	 * Sends key combinations, such as Ctrl+Alt+Del
	 *
	 * > NOTE: If you want to type normal characters
	 * > into a text field use the ```type()``` method.
	 *
	 * @param string $keys A string that represents the keys you want sent.
	 *
	 * @see http://cgit.freedesktop.org/xorg/proto/x11proto/plain/keysymdef.h
	 * To press the backspace key you would supply "BackSpace". Not XK_BackSpace
	 *
	 * @return self For method chaining.
	 */
	public function sendKeys($keys)
	{
		$this->runProcess
		(
			$this->setDisplay('xdotool key --clearmodifiers '.$keys)
		);
		
		return $this;
	}
	
	/**
	 * Sends key combinations directly to Google Chrome.
	 *
	 * This works exactly the same as the ```sendKeys()``` method
	 * but ensures the Google Chrome window is focuses first.
	 *
	 * @param string $keys A string that represents the keys you want sent.
	 * @return self For method chaining.
	 */
	public function sendKeysToChrome($keys)
	{
		$this->runProcess
		(
			$this->grabChrome('key --clearmodifiers '.$keys)
		);
		
		return $this;
	}
	
	/**
	 * Types text into any currently focuses input field.
	 *
	 * > NOTE: To focus a field use ```setMousePos()->leftClick()```
	 *
	 * @param string $text The text you wish to type.
	 * @return self For method chaining.
	 */
	public function type($text)
	{
		$this->runProcess
		(
			$this->setDisplay('xdotool type "'.$text.'"')
		);
		
		return $this;
	}
	
	/**
	 * Waits for a Graphical Element to Appear or Disappear on the screen.
	 *
	 * We are operating a GUI program with a computer program which doesn't have
	 * any eyes and so this is the next best thing. We can't click a button if
	 * it hasn't been drawn on the screen yet.
	 *
	 * @param string $pattern The name of the visgrep pattern file to use.
	 * @param bool $notExist Set to true to wait for the element to disappear.
	 * @param bool $sync Waits for the screen to be static before continuing.
	 * @param int $timeout The number of seconds we will wait for.
	 * @return self For method chaining.
	 */
	public function waitFor($pattern, $notExist = false, $sync = true, $timeout = 1)
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
		
		// Sometimes visgrep just doesn't work, instead of getting hung up here
		// for ages we will only loop for the timeout time Which defaults to 1
		// second, in the computer world this is a very long time.
		$start_time = time();
		
		do
		{
			// Create a new process
			$process = new Process($cmd);
			
			// Take a new screenshot and pass it in via stdin
			$process->setInput($this->takeScreenShot());
			
			// Run the command
			$process->run();
			
			// An exit code of 2 actually means something went wrong.
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
		while(!$ready && (time() - $start_time) < $timeout);
		
		// This works in a similar way to the xdotool --sync option.
		// Even though our pattern has been found if the screen is still
		// changing we probably want to wait until it's finished doing whatever
		// we told it to do last. Most of the time this is something we want to
		// do however if a cursor is blinking in a text field we will get hang
		// up here.
		if ($sync)
		{
			// Again we don't need to wait for 3 billion years.
			// If the screen is still changing after a second, it's probably
			// a cursor blinking, Chromes spinning icon spinning or some other
			// nonsense which we don't care for.
			$start_time = time();
			
			do
			{
				$img1 = $this->takeScreenShot();
				usleep(100000); // 100ms
				$img2 = $this->takeScreenShot();
			}
			while($img1 != $img2 && (time() - $start_time) < $timeout);
		}
		
		return $this;
	}
	
	/**
	 * Prefixes a cmd with the DISPLAY environment variable.
	 *
	 * @param string $cmd The command to run.
	 * @return string
	 */
	protected function setDisplay($cmd)
	{
		return 'DISPLAY=:'.$this->display.' '.$cmd;
	}
	
	/**
	 * Ensures the Google Chrome window is focused before running the next cmd.
	 *
	 * @param string $cmd The command to run on the Google Chrome Window.
	 * @return string.
	 */
	protected function grabChrome($cmd)
	{
		return $this->setDisplay('xdotool search --sync "Google Chrome" '.$cmd);
	}
	
	/**
	 * A helper method to run a command using Symfony's Process class.
	 *
	 * @param string $cmd The command to run.
	 * @throws RuntimeException When the command exits with errors.
	 * @return Symfony\Component\Process\Process
	 */
	protected function runProcess($cmd)
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
