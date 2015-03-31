The String Gear
================================================================================
[![Build Status](https://travis-ci.org/phpgearbox/string.svg?branch=master)](https://travis-ci.org/phpgearbox/string)
[![Latest Stable Version](https://poser.pugx.org/gears/string/v/stable.svg)](https://packagist.org/packages/gears/string)
[![Total Downloads](https://poser.pugx.org/gears/string/downloads.svg)](https://packagist.org/packages/gears/string)
[![License](https://poser.pugx.org/gears/string/license.svg)](https://packagist.org/packages/gears/string)

A collection of basic string manipulation functions.
There are 2 APIs:

  - One procedural based using name spaced functions / static method calls.
  - And a more fluent object based API.

I am not going to bother documenting every single last function here but please
see below for some general usage examples. The rest you can work out for
yourself by reading the source, it's fairly straight forward and well commented.

How to Install
--------------------------------------------------------------------------------
Installation via composer is easy:

	composer require gears/string:*

How to Use
--------------------------------------------------------------------------------
Here are a few procedural examples:

```php
// returns true
Gears\String\contains('this is a test', 'test');

// returns 'this is a test'
Gears\String\between('<p>this is a test</p>', '<p>', '</p>');

// returns 'cde'
Gears\String\subString('abcdeg', 2, 5);
```

In PHP 5.6 you can import functions so you could change the above to:

```php
// Import the functions
use function Gears\String\contains;
use function Gears\String\between;
use function Gears\String\subString;

// returns true
contains('this is a test', 'test');

// returns 'this is a test'
between('<p>this is a test</p>', '<p>', '</p>');

// returns 'cde'
subString('abcdeg', 2, 5);
```

> NOTE: All function names are camelCased.

Prior to PHP 5.6 this is not possible. So you can do this instead:

```php
// Import the string class
use Gears\String as Str;

// returns true
Str::contains('this is a test', 'test');

// returns 'this is a test'
Str::between('<p>this is a test</p>', '<p>', '</p>');

// returns 'cde'
Str::subString('abcdeg', 2, 5);
```

**The factory method:** You may wish to use the factory method to initiate a
new Gears\String object. When you do, please note how the subsequent method
call signature changes. You no longer need to provide the string to be performed
on as the first argument. This is automatically done for you.
Here is an example:

```php
// using the factory method - returns true
Str::s('This is a test')->contains('test');

// Method chaining is possible - returns 'this is a test'
Str::s('<div><p>this is a test</p></div>')
	->between('<div>', '</div>')
	->between('<p>', '</p>');
```

If an array is returned for example when using the match method, the array
will be an array of Gears\String instances not simple PHP strings.

```php
foreach (Str::s('I am going to perform a test')->match('/ \w /') as $match)
{
	if ($match->contains('a'))
	{
		echo 'we found the letter a';
	}
}
```

> NOTE: Using the procedural API will only ever
> return standard PHP strings unlike the above.

Laravel Integration
--------------------------------------------------------------------------------
*Gears\String* has been designed as functionally compatible to the *Laravel Str*
class. Thus if you want you can completely swap out
```Illuminate\Support\Str``` for ```Gears\String```.

To do so in the file ```/app/config/app.php``` replace the following line:

```php
'Str' => 'Illuminate\Support\Str',
```

with:

```php
'Str' => 'Gears\String',
```

Credits
--------------------------------------------------------------------------------
Thanks to *alecgorge* for the inspiration, I have taken his methods re-factored
them slightly and added a few of my own methods, into the mix.
https://github.com/alecgorge/PHP-String-Class/

Additionally all methods in the class ```Illuminate\Support\Str```
provided by Laravel. Have been integrated into ```Gears\String```.
https://github.com/laravel/framework/blob/4.2/src/Illuminate/Support/Str.php

--------------------------------------------------------------------------------
Developed by Brad Jones - brad@bjc.id.au