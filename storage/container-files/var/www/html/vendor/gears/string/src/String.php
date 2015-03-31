<?php namespace Gears;
////////////////////////////////////////////////////////////////////////////////
// __________ __             ________                   __________              
// \______   \  |__ ______  /  _____/  ____ _____ ______\______   \ _______  ___
//  |     ___/  |  \\____ \/   \  ____/ __ \\__  \\_  __ \    |  _//  _ \  \/  /
//  |    |   |   Y  \  |_> >    \_\  \  ___/ / __ \|  | \/    |   (  <_> >    < 
//  |____|   |___|  /   __/ \______  /\___  >____  /__|  |______  /\____/__/\_ \
//                \/|__|           \/     \/     \/             \/            \/
// -----------------------------------------------------------------------------
//          Designed and Developed by Brad Jones <brad @="bjc.id.au" />         
// -----------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

use \Illuminate\Support\Traits\Macroable;

class String implements \ArrayAccess
{
	/*
	 * Make this compatiable with the Laravel Str class.
	 * That way we can easily swap in our version into a Laravel App.
	 */
	use Macroable
	{
		__callStatic as __macroCallStatic;
		__call as __macroCall;
	}

	/**
	 * Property: $value
	 * =========================================================================
	 * This stores the actual string that this object represents.
	 */
	private $value;
	
	/**
	 * Method: __construct
	 * =========================================================================
	 * Creates a new Gears\String object.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $string - A PHP string to turn into a Gears\String object.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public function __construct($string)
	{
		$this->value = (string)$string;
	}

	/**
	 * Method: s
	 * =========================================================================
	 * This provides a static constructor or factory method.
	 * So you can do things like this:
	 * 
	 *     Gears\String::s('hello world')->contains('world');
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $string - A PHP string to turn into a Gears\String object.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public static function s($string)
	{
		return new self($string);
	}
	
	/**
	 * Method: __toString
	 * =========================================================================
	 * Magic method to turn Gears\String back into a normal string.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * n/a
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * string
	 */
	public function __toString()
	{
		return $this->value;
	}
	
	// Alias for above
	public function toString()
	{
		return $this->__toString();
	}
	
	/**
	 * Method: offsetExists
	 * =========================================================================
	 * ArrayAccess method, checks to see if the key actually exists.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $index - The integer of the index to check.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * boolean
	 */
	public function offsetExists($index)
	{
		return !empty($this->value[$index]);
	}
	
	/**
	 * Method: offsetGet
	 * =========================================================================
	 * ArrayAccess method, retrieves an array value.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $index - The integer of the index to get.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * string
	 */
	public function offsetGet($index)
	{
		return \Gears\String\charAt($this->value, $index);
	}
	
	/**
	 * Method: offsetSet
	 * =========================================================================
	 * ArrayAccess method, sets an array value.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $index - The integer of the index to set.
	 * $val - The new value for the index.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public function offsetSet($index, $val)
	{
		// Work out the start of the string
		if ($index == 0)
		{
			$start = '';
		}
		else
		{
			$start = \Gears\String\slice($this->value, 0, $index);
		}
		
		// Work out the end of the string
		$end = \Gears\String\slice
		(
			$this->value,
			$index+1,
			\Gears\String\length($this->value)
		);
		
		// Recombine them with a new middle
		$this->value = $start.$val.$end;
	}
	
	/**
	 * Method: offsetUnset
	 * =========================================================================
	 * ArrayAccess method, removes an array value.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $index - The integer of the index to delete.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public function offsetUnset($index)
	{
		$this->value =
			\Gears\String\slice($this->value, 0, $index).
			\Gears\String\slice($this->value, $index+1)
		;
	}
	
	/**
	 * Method: returnSelf
	 * =========================================================================
	 * The idea behind this is to provide a fluent interface.
	 * So that multiple calls can be chained together.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $input - The input data. This will either be a string,
	 * an array of strings or possibly some other value like a boolean,
	 * in the case something failed.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * mixed
	 */
	private function returnSelf($input)
	{
		if (is_string($input))
		{
			// It's just a single string so create a new instance.
			$output = new self($input);
		}
		elseif (is_array($input))
		{
			// Create our output array
			$output = [];
			
			// Loop over the input array
			foreach ($input as $string)
			{
				if (is_string($string))
				{
					// Add a new string
					$output[] = new self($string);
				}
				elseif (is_array($string))
				{
					// Recurse into the array
					$output[] = $this->returnSelf($string);
				}
				else
				{
					// We don't know what it is do do nothing to it
					$output[] = $string;
				}
			}
		}
		else
		{
			// We don't know what it is do do nothing to it
			$output = $input;
		}

		return $output;
	}
	
	/**
	 * Method: __call
	 * =========================================================================
	 * This is what creates the fluent api. This class is just a fancy
	 * container and has no real functionality at all.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $name - The name of the \Gears\String\"FUNCTION" to call.
	 * $arguments - The arguments to pass to the function.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * mixed
	 */
	public function __call($name, $arguments)
	{
		// Create the function name
		$func_name = '\Gears\String\\'.$name;

		// Prepend the current string value to the arguments
		array_unshift($arguments, $this->value);

		// Does the function exist
		if (!function_exists($func_name))
		{
			// Try a macro
			if (self::hasMacro($name))
			{
				return self::__macroCall($name, $arguments);
			}

			// Bail out, we don't have a function to run
			throw new \Exception('Gears String function does not exist!');
		}

		// Call the function
		$result = call_user_func_array($func_name, $arguments);

		// Return our selves.
		return $this->returnSelf($result);
	}

	/**
	 * Method: __callStatic
	 * =========================================================================
	 * This provides a static API. As of PHP 5.5 we can't import functions from
	 * different name spaces. In PHP 5.6 we can. So this is the next best thing.
	 * 
	 * For example compare this:
	 * 
	 *     \Gears\String\contains('hello world', 'world');
	 * 
	 * To this:
	 * 
	 *     use Gears\String as Str;
	 *     Str::contains('hello world', 'world');
	 * 
	 * NOTE: Static calls like this will return the exact output from the
	 * underlying function. So you can't do method chaining, etc.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $name - The name of the \Gears\String\"FUNCTION" to call.
	 * $arguments - The arguments to pass to the function.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * mixed
	 */
	public static function __callStatic($name, $arguments)
	{
		// Create the function name
		$func_name = '\Gears\String\\'.$name;

		// Does the function exist
		if (!function_exists($func_name))
		{
			// Try a macro
			if (self::hasMacro($name))
			{
				return self::__macroCallStatic($name, $arguments);
			}

			// Bail out, we don't have a function to run
			throw new \Exception('Gears String function does not exist!');
		}

		// Call the function
		return call_user_func_array($func_name, $arguments);
	}
}
