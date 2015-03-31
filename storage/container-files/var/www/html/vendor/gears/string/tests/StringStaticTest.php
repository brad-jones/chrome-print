<?php
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

use Gears\String as Str;

class StringStaticTest extends PHPUnit_Framework_TestCase
{
	protected $string = 'This is a string.';

	public function testIsUTF8()
	{
		$this->assertTrue(Str::isUTF8(''));
		$this->assertTrue(Str::isUTF8('éééé'));
		$this->assertFalse(Str::isUTF8(utf8_decode('éééé')));
	}

	public function testToUTF8()
	{
		$this->assertEquals('', Str::toUTF8(''));
		$this->assertEquals('éééé', Str::toUTF8(utf8_decode('éééé')));
	}

	public function testToLatin1()
	{
		$this->assertEquals('', Str::toLatin1(''));
		$this->assertEquals('éééé', Str::toLatin1(utf8_encode('éééé')));
	}

	public function testFixUTF8()
	{
		$this->assertEquals('', Str::fixUTF8(''));
		$this->assertEquals('Fédération Camerounaise de Football', Str::fixUTF8('FÃÂ©dération Camerounaise de Football'));
		$this->assertEquals('Fédération Camerounaise de Football', Str::fixUTF8('FÃ©dÃ©ration Camerounaise de Football'));
		$this->assertEquals('Fédération Camerounaise de Football', Str::fixUTF8('FÃÂ©dÃÂ©ration Camerounaise de Football'));
		$this->assertEquals('Fédération Camerounaise de Football', Str::fixUTF8('FÃÂÂÂÂ©dÃÂÂÂÂ©ration Camerounaise de Football'));
	}

	public function testWildCardMatch()
	{
		$html = '<a title="foo" href="/hello">Hello World</a>';

		$pattern = '<a*href="*"*>*</a>';

		$matches = array
		(
			0 => array
			(
				0 => '<a title="foo" href="/hello">Hello World</a>',
			),
			1 => array
			(
				0 => ' title="foo" ',
			),
			2 => array
			(
				0 => '/hello',
			),
			3 => array
			(
				0 => '',
			),
			4 => array
			(
				0 => 'Hello World',
			)
		);

		$this->assertEquals($matches, Str::wildCardMatch($html, $pattern));
	}
	
	public function testSearch()
	{
		$this->assertEquals(8, Str::search($this->string, 'a'));
		$this->assertEquals(-1, Str::search($this->string, 'b'));
		$this->assertEquals(4, Str::search($this->string, '/ is /', true));
		$this->assertEquals(-1, Str::search($this->string, '/ foo /', true));
	}

	public function testReplace()
	{
		$this->assertEquals('This is a Gears\String.', Str::replace($this->string, 'string', 'Gears\String'));
		$this->assertEquals($this->string, Str::replace($this->string, 'foo', 'Gears\String'));
		$this->assertEquals('This is a Gears\String.', Str::replace($this->string, '/string/', 'Gears\String', true));
		$this->assertEquals($this->string, Str::replace($this->string, '/ string /', 'Gears\String', true));
		$this->assertEquals('This is a STRING.', Str::replace($this->string, '/string/', function($matches){ return strtoupper($matches[0]); }, true));
	}

	public function testMatch()
	{
		$this->assertEquals(['is a', 'is a'], Str::match($this->string.' '.$this->string, '/is a/'));
		$this->assertEquals([], Str::match($this->string.' '.$this->string, '/is foo/'));
	}

	public function testBetween()
	{
		$this->assertEquals($this->string, Str::between('<start>'.$this->string.'</end>', '<start>', '</end>'));
		$this->assertEquals('', Str::between('<start>'.$this->string.'</end>', '<start1>', '</end>'));
		$this->assertEquals('', Str::between('<start>'.$this->string.'</end>', '<start>', '</end1>'));
		$this->assertEquals('<start>'.$this->string.'</end>', Str::between('<start>'.$this->string.'</end>', '<start>', '</end>', true));
	}

	public function testBetweenRegx()
	{
		$xml = file_get_contents(__DIR__.'/data/books.xml');
		$expected = require(__DIR__.'/data/books.php');
		$this->assertEquals($expected, Str::betweenRegx($xml, '<author>', '</author>'));
	}

	public function testSubString()
	{
		$this->assertEquals('is a', Str::substring($this->string, 5, 9));
		$this->assertEquals('is a string.', Str::substring($this->string, 5));
	}

	public function testSlice()
	{
		$this->assertEquals('is a', Str::slice($this->string, 5, 9));
		$this->assertEquals('is a string.', Str::slice($this->string, 5));
	}

	public function testConCat()
	{
		$this->assertEquals($this->string.$this->string, Str::conCat($this->string, $this->string));
	}

	public function testSplit()
	{
		$this->assertEquals(['T','h','i','s',' ','i','s',' ','a',' ','s','t','r','i','n','g','.'], Str::split($this->string));
		$this->assertEquals(['This','is','a','string.'], Str::split($this->string, ' '));
	}

	public function testRange()
	{
		$this->assertEquals(true, Str::range($this->string, 0, 20));
		$this->assertEquals(true, Str::range($this->string, 5, 20));
		$this->assertEquals(false, Str::range($this->string, 0, 10));
		$this->assertEquals(false, Str::range($this->string, 30, 40));
	}

	public function testCharAt()
	{
		$this->assertEquals('T', Str::charAt($this->string, 0));
		$this->assertEquals('h', Str::charAt($this->string, 1));
		$this->assertEquals('i', Str::charAt($this->string, 2));
		$this->assertEquals('s', Str::charAt($this->string, 3));
	}

	public function testCharCodeAt()
	{
		$this->assertEquals(84, Str::charCodeAt($this->string, 0));
		$this->assertEquals(104, Str::charCodeAt($this->string, 1));
		$this->assertEquals(105, Str::charCodeAt($this->string, 2));
		$this->assertEquals(115, Str::charCodeAt($this->string, 3));
	}

	public function testFromCharCode()
	{
		$this->assertEquals('T', Str::fromCharCode(84));
		$this->assertEquals('h', Str::fromCharCode(104));
		$this->assertEquals('i', Str::fromCharCode(105));
		$this->assertEquals('s', Str::fromCharCode(115));
	}

	public function testIndexOf()
	{
		$this->assertEquals(2, Str::indexOf($this->string, 'is'));
		$this->assertEquals(false, Str::indexOf($this->string, 'foo'));
	}

	public function testLastIndexOf()
	{
		$this->assertEquals(5, Str::lastIndexOf($this->string, 'is'));
		$this->assertEquals(false, Str::lastIndexOf($this->string, 'foo'));
	}

	public function testAscii()
	{
		$this->assertEquals('', Str::ascii(''));
		$this->assertEquals('deja vu', Str::ascii('déjà vu'));
		$this->assertEquals('i', Str::ascii('ı'));
		$this->assertEquals('a', Str::ascii('ä'));
	}

	public function testContains()
	{
		$this->assertTrue(Str::contains('taylor', 'ylo'));
		$this->assertTrue(Str::contains('taylor', array('ylo')));
		$this->assertFalse(Str::contains('taylor', 'xxx'));
		$this->assertFalse(Str::contains('taylor', array('xxx')));
		$this->assertFalse(Str::contains('taylor', ''));
	}

	public function testStartsWith()
	{
		$this->assertTrue(Str::startsWith('jason', 'jas'));
		$this->assertTrue(Str::startsWith('jason', 'jason'));
		$this->assertTrue(Str::startsWith('jason', array('jas')));
		$this->assertFalse(Str::startsWith('jason', 'day'));
		$this->assertFalse(Str::startsWith('jason', array('day')));
		$this->assertFalse(Str::startsWith('jason', ''));
	}

	public function testEndsWith()
	{
		$this->assertTrue(Str::endsWith('jason', 'on'));
		$this->assertTrue(Str::endsWith('jason', 'jason'));
		$this->assertTrue(Str::endsWith('jason', array('on')));
		$this->assertFalse(Str::endsWith('jason', 'no'));
		$this->assertFalse(Str::endsWith('jason', array('no')));
		$this->assertFalse(Str::endsWith('jason', ''));
		$this->assertFalse(Str::endsWith('7', ' 7'));
	}

	public function testFinish()
	{
		$this->assertEquals('abbc', Str::finish('ab', 'bc'));
		$this->assertEquals('abbc', Str::finish('abbcbc', 'bc'));
		$this->assertEquals('abcbbc', Str::finish('abcbbcbc', 'bc'));
	}

	public function testIs()
	{
		$this->assertTrue(Str::is('/', '/'));
		$this->assertFalse(Str::is('/', ' /'));
		$this->assertFalse(Str::is('/', '/a'));
		$this->assertTrue(Str::is('foo/*', 'foo/bar/baz'));
		$this->assertTrue(Str::is('*/foo', 'blah/baz/foo'));
	}

	public function testLength()
	{
		$this->assertEquals(17, Str::length($this->string));
	}

	public function testLimit()
	{
		$this->assertEquals('This...', Str::limit($this->string, 4));
	}

	public function testLower()
	{
		$this->assertEquals('this is a string.', Str::lower($this->string));
	}

	public function testUpper()
	{
		$this->assertEquals('THIS IS A STRING.', Str::upper($this->string));
	}

	public function testWords()
	{
		$this->assertEquals('Taylor...', Str::words('Taylor Otwell', 1));
		$this->assertEquals('Taylor___', Str::words('Taylor Otwell', 1, '___'));
		$this->assertEquals('Taylor Otwell', Str::words('Taylor Otwell', 3));
		$this->assertEquals(' Taylor Otwell ', Str::words(' Taylor Otwell ', 3));
		$this->assertEquals(' Taylor...', Str::words(' Taylor Otwell ', 1));
		$nbsp = chr(0xC2).chr(0xA0);
		$this->assertEquals(' ', Str::words(' '));
		$this->assertEquals($nbsp, Str::words($nbsp));
	}

	public function testPlural()
	{
		$this->assertEquals('children', Str::plural('child'));
		$this->assertEquals('tests', Str::plural('test'));
		$this->assertEquals('deer', Str::plural('deer'));
		$this->assertEquals('Children', Str::plural('Child'));
		$this->assertEquals('CHILDREN', Str::plural('CHILD'));
		$this->assertEquals('Tests', Str::plural('Test'));
		$this->assertEquals('TESTS', Str::plural('TEST'));
		$this->assertEquals('tests', Str::plural('test'));
		$this->assertEquals('Deer', Str::plural('Deer'));
		$this->assertEquals('DEER', Str::plural('DEER'));
	}

	public function testSingular()
	{
		$this->assertEquals('Child', Str::singular('Children'));
		$this->assertEquals('CHILD', Str::singular('CHILDREN'));
		$this->assertEquals('Test', Str::singular('Tests'));
		$this->assertEquals('TEST', Str::singular('TESTS'));
		$this->assertEquals('Deer', Str::singular('Deer'));
		$this->assertEquals('DEER', Str::singular('DEER'));
		$this->assertEquals('Criterium', Str::singular('Criteria'));
		$this->assertEquals('CRITERIUM', Str::singular('CRITERIA'));
		$this->assertEquals('child', Str::singular('children'));
		$this->assertEquals('test', Str::singular('tests'));
		$this->assertEquals('deer', Str::singular('deer'));
		$this->assertEquals('criterium', Str::singular('criteria'));
	}

	public function testQuickRandom()
	{
		$randomInteger = mt_rand(1, 100);
		$this->assertEquals($randomInteger, strlen(Str::quickRandom($randomInteger)));
		$this->assertInternalType('string', Str::quickRandom());
		$this->assertEquals(16, strlen(Str::quickRandom()));
	}

	public function testRandom()
	{
		$this->assertEquals(16, strlen(Str::random()));
		$randomInteger = mt_rand(1, 100);
		$this->assertEquals($randomInteger, strlen(Str::random($randomInteger)));
		$this->assertInternalType('string', Str::random());
	}

	public function testTitle()
	{
		$this->assertEquals('Jefferson Costella', Str::title('jefferson costella'));
		$this->assertEquals('Jefferson Costella', Str::title('jefFErson coSTella'));
	}

	public function testSlug()
	{
		$this->assertEquals('hello-world', Str::slug('hello world'));
		$this->assertEquals('hello-world', Str::slug('hello-world'));
		$this->assertEquals('hello-world', Str::slug('hello_world'));
		$this->assertEquals('hello_world', Str::slug('hello_world', '_'));
	}

	public function testSnake()
	{
		$this->assertEquals('foo_bar', Str::snake('fooBar'));
	}

	public function testCamelCase()
	{
		$this->assertEquals('fooBar', Str::camel('FooBar'));
		$this->assertEquals('fooBar', Str::camel('foo_bar'));
		$this->assertEquals('fooBarBaz', Str::camel('Foo-barBaz'));
		$this->assertEquals('fooBarBaz', Str::camel('foo-bar_baz'));
	}

	public function testStudly()
	{
		$this->assertEquals('FooBar',  Str::studly('fooBar'));
		$this->assertEquals('FooBar',  Str::studly('foo_bar'));
		$this->assertEquals('FooBarBaz',  Str::studly('foo-barBaz'));
		$this->assertEquals('FooBarBaz',  Str::studly('foo-bar_baz'));
	}
}