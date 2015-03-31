<?php namespace Gears\String;
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

/**
 * Function: wildCardMatch
 * =============================================================================
 * This is the lazy mans regular expression. Each wildcard "*" character will
 * be turned into a non-gready regular expression group.
 *
 * ```php
 * $html = '<a title="foo" href="/hello">Hello World</a>';
 *
 * $pattern = '<a*href="*"*></a>';
 * 
 * $matches = wildCardMatch($html, $pattern);
 * ```
 *
 * The result would look like:
 *
 * ```php
 * Array
 * (
 *     [0] => Array
 *         (
 *             [0] => <a title="foo" href="/hello">Hello World</a>
 *         )
 * 
 *     [1] => Array
 *         (
 *             [0] =>  title="foo" 
 *         )
 * 
 *     [2] => Array
 *         (
 *             [0] => /hello
 *         )
 * 
 *     [3] => Array
 *         (
 *             [0] => 
 *         )
 * 
 *     [4] => Array
 *         (
 *             [0] => Hello World
 *         )
 * 
 * )
 * ```
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack - The string haystack to search.
 * $needle - The pattern to look for.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * array
 */
function wildCardMatch($haystack, $needle)
{
	// Quote the entire needle, so that everything is treated literally
	$pattern = preg_quote($needle, '#');

	// Then replace literal wildcards with a regular expression group
	$pattern = str_replace('\*', '(.*?)', $pattern);

	// Run the regular expression
	preg_match_all('#'.$pattern.'#s', $haystack, $matches);

	// Return the matches
	return $matches;
}

/**
 * Function: search
 * =============================================================================
 * The search() method searches a string for a specified value,
 * or regular expression, and returns the position of the match.
 * This method returns -1 if no match is found.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack - The string haystack to perform the test to.
 * $needle - This is what we are looking for inside the haystack.
 * $regx - Set to true if needle is a regular expression.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * boolean
 */
function search($haystack, $needle, $regx = false)
{
	if ($regx)
	{
		if (preg_match($needle, $haystack, $match) == 1)
		{
			$pos = strpos($haystack, $match[0]);
		}
		else
		{
			$pos = -1;
		}
	}
	else
	{
		$pos = strpos($haystack, $needle);
		
		if ($pos === false)
		{
			$pos = -1;
		}
	}
	
	return $pos;
}

/**
 * Function: replace
 * =============================================================================
 * The replace() method searches a string for a specified value,
 * or a regular expression, and returns a new string where the specified
 * values are replaced.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack - The string haystack to perform the manipulation to.
 * 
 * $needle - This is what we are looking for inside the haystack.
 * This can also be an array of multiple needles.
 * 
 * $replace - This is what will replace the needle. This can be an array of
 * multiple replacements. Also if using regx, you can supply an annoymous
 * function if you wish instead.
 * 
 * $regex - Set to true if needle is a regular expression.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function replace($haystack, $needle, $replace, $regex = false)
{
	if($regex)
	{
		if (is_callable($replace))
		{
			$result = preg_replace_callback($needle, $replace, $haystack);
		}
		else
		{
			$result = preg_replace($needle, $replace, $haystack);
		}
	}
	else
	{
		$result = str_replace($needle, $replace, $haystack);
	}
	
	return $result;
}

/**
 * Function: match
 * =============================================================================
 * The match() method searches a string for a match against a regular
 * expression, and returns the matches, as an Array object.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack - The string haystack to perform the test to.
 * $regex - The regular expression.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * array
 */
function match($haystack, $regex)
{
	preg_match_all($regex, $haystack, $matches);
	return $matches[0];
}

/**
 * Function: matches
 * =============================================================================
 * This function is an alias for the laravel Str::is() method.
 * The reason we have this is because the laravel method has the haystack and
 * pattern parameters around the wrong way. And hence our fluent API fails.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack - The string haystack to perform the test to.
 * $pattern - A simple string which uses "*" the wildcard character.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * boolean
 */
function matches($haystack, $pattern)
{
	return is($pattern, $haystack);
}

/**
 * Function: between
 * =============================================================================
 * A very simple function that will extract the information between a start and
 * an end string. This function only acts once on the given string. I find it
 * helpful for parsing fetched HTML pages by getting to the part of the HTML
 * I am intrested in. ie: A Table, I would then run BetweenPreg over the table.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack - The string haystack to perform the manipulation to.
 * $start - A string that defines the start point
 * $end - A string that defines the end point
 * $include - If set to true we will include the start and end in the output.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * The string between a start and end string.
 */
function between($haystack, $start, $end, $include = false)
{
	// This is what we will return
	$result = '';
	
	// Add a little buffer
	$haystack = ' '.$haystack;
	
	// Can we find the start?
	$ini = strpos($haystack, $start);
	if ($ini === false)
	{
		// Nope so return nothing
		return '';
	}

	// Can we find the end?
	if (strpos($haystack, $end) === false)
	{
		// Nope so return nothing
		return '';
	}

	// Move our position past the start point
	$ini += strlen($start);
	
	// Get the length of the middle section
	$len = strpos($haystack, $end, $ini) - $ini;
	
	// Grab the middle
	$result = substr($haystack, $ini, $len);
	
	// Do we want the start and end?
	if ($include)
	{
		$result = $start.$result.$end;
	}
	
	// Return the final result
	return $result;
}

/**
 * Function: betweenRegx
 * =============================================================================
 * This does more or less the same thing as above except that it does it using
 * a regular expression. It also matches multiple start and end strings.
 * This function is great for parsing HTML/XML type data.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack - The string haystack to perform the manipulation to.
 * $start - A string that defines the start point
 * $end - A string that defines the end point
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * Returns an array of matches or false if it can't find anything. The first
 * key in the array contains an array of results including the start and end
 * strings. The second key contains an array of results that don't have the
 * start and end strings.
 */
function betweenRegx($haystack, $start, $end)
{
	// Create the regular expression
	$find = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';
	
	// Run the regular expression
	if (preg_match_all($find, $haystack, $matches))
	{
		return $matches;
	}
	else
	{
		return false;
	}
}

/**
 * Function: subString
 * =============================================================================
 * The substring() method extracts the characters from a string, between two
 * specified indices, and returns the new sub string. This method extracts the
 * characters in a string between "from" and "to", not including "to" itself.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - The input string. Must be one character or longer.
 * $start - The starting point of the extraction.
 * $end - The ending point of the extraction.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function subString($string, $start, $end = null)
{
	if(empty($end))
	{
		return substr($string, $start);
	}
	else
	{
		return substr($string, $start, ($end - $start));
	}
}

/**
 * Function: slice
 * =============================================================================
 * This is really just an alias for substring for completeness sake
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - The input string. Must be one character or longer.
 * $start - The starting point of the extraction.
 * $end - The ending point of the extraction.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function slice($string, $start, $end = null)
{
	return subString($string, $start, $end);
}

/**
 * Function: conCat
 * =============================================================================
 * The concat() method is used to join two or more strings. This method does
 * not change the existing strings, but returns a new string containing the
 * text of the joined strings.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string1
 * $string2
 * $string3
 * $etc
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function conCat()
{
	$result = '';
	foreach(func_get_args() as $arg) { $result .= (string)$arg; }
	return $result;
}

/**
 * Function: split
 * =============================================================================
 * Splits a string into an array of substrings
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - The string to split
 * 
 * $at - What character to use as the delimeter for the split,
 * if none supplied you will get an array of characters.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * array
 */
function split ($string, $at = '')
{
	if(empty($at))
	{
		return str_split($string);
	}
	else
	{
		return explode($at, $string);
	}
}

/**
 * Function: range
 * =============================================================================
 * This checks to see if the length of the haystack is between x and y
 * Calulates inclusive of x and y, I felt this was the natural assumption.
 * It was for me when I first used this function before it become part of
 * the library anyway.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - A string to test
 * $x - The lower value
 * $y - The higher value
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * boolean
 */
function range($string, $x, $y)
{
	// Get the length of the string
	$length = length($string);
	
	// Is it between x and y
	if (($length >= $x) && ($length <= $y))
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Function: charAt
 * =============================================================================
 * The charAt() method returns the character at the specified index in a string.
 * The index of the first character is 0, the second character is 1, and so on.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - The string to extract the character from.
 * $point - The numerical index of the character, starting at 0.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function charAt($string, $point)
{
	return substr($string, $point, 1);
}

/**
 * Function: charCodeAt
 * =============================================================================
 * The charCodeAt() method returns the Unicode of the character at the
 * specified index in a string. The index of the first character is 0,
 * the second character 1, and so on.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - The string to extract the character from.
 * $point - The numerical index of the character, starting at 0.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * int
 */
function charCodeAt($string, $point)
{
	return ord(substr($string, $point, 1));
}

/**
 * Function: fromCharCode
 * =============================================================================
 * The fromCharCode() method converts Unicode values into characters.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $code - The ASCII Code
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function fromCharCode($code)
{
	return chr($code);
}

/**
 * Function: indexOf
 * =============================================================================
 * Just an alias for strpos
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack
 * $needle
 * $offset
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * int
 */
function indexOf($haystack, $needle, $offset = 0)
{
	return strpos($haystack, $needle, $offset);
}

/**
 * Function: lastIndexOf
 * =============================================================================
 * Just an alias for strrpos
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $haystack
 * $needle
 * $offset
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * int
 */
function lastIndexOf($haystack, $needle, $offset = 0)
{
	return strrpos($haystack, $needle, $offset);
}

/**
 * Function: humanise
 * =============================================================================
 * This does the reverse of camel case, snake case, etc.
 * 
 * For example:
 * 
 *     fooBar  => Foo Bar
 *     foo-bar => Foo Bar
 *     foo_bar => Foo Bar
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - The string to humanise.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function humanise($string)
{
	return ucwords(str_replace(['_', '-'], ' ', snake($string)));
}

/**
 * Function: to
 * =============================================================================
 * This function has been built with the fluent API in mind.
 * Although can still be used procedurally.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - The string to change form, ie: lower case to upper case.
 * 
 * $what - What do we want to change the string into.
 * Current valid values for this:
 * 
 *     - upper | uppercase | big
 *     - lower | lowercase | small
 *     - singular | one | 1
 *     - plural | many | lots
 *     - camel | camelcase
 *     - slug | slugcase
 *     - title | titlecase
 *     - snake | snakecase
 *     - studly | studlycase
 *     - human | humanise | humanize
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function to($string, $what)
{
	$what = lower($what);

	switch ($what)
	{
		case 'upper':
		case 'uppercase':
		case 'big':
		{
			return upper($string);
			break;
		}

		case 'lower':
		case 'lowercase':
		case 'small':
		{
			return lower($string);
			break;
		}

		case 'singular':
		case 'one':
		case '1':
		case 1:
		{
			return singular($string);
			break;
		}

		case 'plural':
		case 'many':
		case 'lots':
		{
			return plural($string);
			break;
		}

		case 'camel':
		case 'camelcase':
		{
			return camel($string);
			break;
		}

		case 'slug':
		case 'slugcase':
		{
			return slug($string);
			break;
		}

		case 'title':
		case 'titlecase':
		{
			return title($string);
			break;
		}

		case 'snake':
		case 'snakecase':
		{
			return snake($string);
			break;
		}

		case 'studly':
		case 'studlycase':
		{
			return studly($string);
			break;
		}

		case 'humanise':
		case 'humanize':
		case 'human':
		{
			return humanise($string);
			break;
		}

		case 'ascii':
		{
			return ascii($string);
			break;
		}

		case 'utf8':
		case 'UTF8':
		case 'utf-8':
		case 'UTF-8':
		{
			return toUTF8($string);
			break;
		}

		case 'latin1':
		case 'ISO8859':
		case 'iso8859':
		case 'ISO88591':
		case 'iso88591':
		case 'ISO-8859':
		case 'iso-8859':
		case 'ISO-8859-1':
		case 'iso-8859-1':
		{
			return toLatin1($string);
			break;
		}

		default:
		{
			return $string;
		}
	}
}

/**
 * Function: make
 * =============================================================================
 * This is simply an alias for the "to" function above.
 * 
 * Parameters:
 * -----------------------------------------------------------------------------
 * $string - See "to" function docs.
 * $what - See "to" function docs.
 * 
 * Returns:
 * -----------------------------------------------------------------------------
 * string
 */
function make($string, $what)
{
	return to($string, $what);
}

/**
 * Section: Laravel Stubs
 * =============================================================================
 * The following functions are just stubs for methods of the class:
 * 
 *     \Illuminate\Support\Str
 * 
 * I could integrate these calls directly and dynamically into the
 * Gears\String class. However then this procedural API would not match
 * that of the Gears\String class.
 * 
 * Each function does not define any arguments, we dynamically pick these up
 * so that any changes to the methods definition in the original Laravel class
 * are automatically picked up here.
 * 
 * Thus if you are looking for documenation on how to use these functions.
 * Please see: http://laravel.com/api/4.2/Illuminate/Support/Str.html
 */

function ascii() { return call_user_func_array('\Illuminate\Support\Str::ascii', func_get_args()); }
function camel() { return call_user_func_array('\Illuminate\Support\Str::camel', func_get_args()); }
function contains() { return call_user_func_array('\Illuminate\Support\Str::contains', func_get_args()); }
function startsWith() { return call_user_func_array('\Illuminate\Support\Str::startsWith', func_get_args()); }
function endsWith() { return call_user_func_array('\Illuminate\Support\Str::endsWith', func_get_args()); }
function finish() { return call_user_func_array('\Illuminate\Support\Str::finish', func_get_args()); }
function is() { return call_user_func_array('\Illuminate\Support\Str::is', func_get_args()); }
function length() { return call_user_func_array('\Illuminate\Support\Str::length', func_get_args()); }
function limit() { return call_user_func_array('\Illuminate\Support\Str::limit', func_get_args()); }
function lower() { return call_user_func_array('\Illuminate\Support\Str::lower', func_get_args()); }
function upper() { return call_user_func_array('\Illuminate\Support\Str::upper', func_get_args()); }
function words() { return call_user_func_array('\Illuminate\Support\Str::words', func_get_args()); }
function plural() { return call_user_func_array('\Illuminate\Support\Str::plural', func_get_args()); }
function random() { return call_user_func_array('\Illuminate\Support\Str::random', func_get_args()); }
function quickRandom() { return call_user_func_array('\Illuminate\Support\Str::quickRandom', func_get_args()); }
function title() { return call_user_func_array('\Illuminate\Support\Str::title', func_get_args()); }
function singular() { return call_user_func_array('\Illuminate\Support\Str::singular', func_get_args()); }
function slug() { return call_user_func_array('\Illuminate\Support\Str::slug', func_get_args()); }
function snake() { return call_user_func_array('\Illuminate\Support\Str::snake', func_get_args()); }
function studly() { return call_user_func_array('\Illuminate\Support\Str::studly', func_get_args()); }
function parseCallback() { return call_user_func_array('\Illuminate\Support\Str::parseCallback', func_get_args()); }

/**
 * Section: UTF8 Stubs
 * =============================================================================
 * As above the the following functions are just some stubs for UTF-8 methods.
 * 
 * > Credits:
 * >   - https://github.com/neitanod/forceutf8/
 * >   - https://github.com/nicolas-grekas/Patchwork-UTF8/
 */

function isUTF8() { return call_user_func_array('\Patchwork\Utf8::isUtf8', func_get_args()); }
function fixUTF8() { return call_user_func_array('\ForceUTF8\Encoding::fixUTF8', func_get_args()); }
function toUTF8() { return call_user_func_array('\ForceUTF8\Encoding::toUTF8', func_get_args()); }
function toLatin1() { return call_user_func_array('\ForceUTF8\Encoding::toLatin1', func_get_args()); }