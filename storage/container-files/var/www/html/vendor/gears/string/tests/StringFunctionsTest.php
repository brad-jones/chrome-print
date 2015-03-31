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

class StringFunctionsTest extends PHPUnit_Framework_TestCase
{
	protected $string = 'This is a string.';

	public function testIsUTF8()
	{
		$this->assertTrue(Gears\String\isUTF8(''));
		$this->assertTrue(Gears\String\isUTF8('éééé'));
		$this->assertFalse(Gears\String\isUTF8(utf8_decode('éééé')));
	}

	public function testToUTF8()
	{
		$this->assertEquals('', Gears\String\toUTF8(''));
		$this->assertEquals('éééé', Gears\String\toUTF8(utf8_decode('éééé')));
	}

	public function testToLatin1()
	{
		$this->assertEquals('', Gears\String\toLatin1(''));
		$this->assertEquals('éééé', Gears\String\toLatin1(utf8_encode('éééé')));
	}

	public function testFixUTF8()
	{
		$this->assertEquals('', Gears\String\fixUTF8(''));
		$this->assertEquals('Fédération Camerounaise de Football', Gears\String\fixUTF8('FÃÂ©dération Camerounaise de Football'));
		$this->assertEquals('Fédération Camerounaise de Football', Gears\String\fixUTF8('FÃ©dÃ©ration Camerounaise de Football'));
		$this->assertEquals('Fédération Camerounaise de Football', Gears\String\fixUTF8('FÃÂ©dÃÂ©ration Camerounaise de Football'));
		$this->assertEquals('Fédération Camerounaise de Football', Gears\String\fixUTF8('FÃÂÂÂÂ©dÃÂÂÂÂ©ration Camerounaise de Football'));
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

		$this->assertEquals($matches, Gears\String\wildCardMatch($html, $pattern));
	}

	public function testSearch()
	{
		$this->assertEquals(8, Gears\String\search($this->string, 'a'));
		$this->assertEquals(-1, Gears\String\search($this->string, 'b'));
		$this->assertEquals(4, Gears\String\search($this->string, '/ is /', true));
		$this->assertEquals(-1, Gears\String\search($this->string, '/ foo /', true));
	}

	public function testReplace()
	{
		$this->assertEquals('This is a Gears\String.', Gears\String\replace($this->string, 'string', 'Gears\String'));
		$this->assertEquals($this->string, Gears\String\replace($this->string, 'foo', 'Gears\String'));
		$this->assertEquals('This is a Gears\String.', Gears\String\replace($this->string, '/string/', 'Gears\String', true));
		$this->assertEquals($this->string, Gears\String\replace($this->string, '/ string /', 'Gears\String', true));
		$this->assertEquals('This is a STRING.', Gears\String\replace($this->string, '/string/', function($matches){ return strtoupper($matches[0]); }, true));
	}

	public function testMatch()
	{
		$this->assertEquals(['is a', 'is a'], Gears\String\match($this->string.' '.$this->string, '/is a/'));
		$this->assertEquals([], Gears\String\match($this->string.' '.$this->string, '/is foo/'));
	}

	public function testBetween()
	{
		$this->assertEquals($this->string, Gears\String\between('<start>'.$this->string.'</end>', '<start>', '</end>'));
		$this->assertEquals('', Gears\String\between('<start>'.$this->string.'</end>', '<start1>', '</end>'));
		$this->assertEquals('', Gears\String\between('<start>'.$this->string.'</end>', '<start>', '</end1>'));
		$this->assertEquals('<start>'.$this->string.'</end>', Gears\String\between('<start>'.$this->string.'</end>', '<start>', '</end>', true));
	}

	public function testBetweenRegx()
	{
		$xml = file_get_contents(__DIR__.'/data/books.xml');
		$expected = require(__DIR__.'/data/books.php');
		$this->assertEquals($expected, Gears\String\betweenRegx($xml, '<author>', '</author>'));
	}

	public function testSubString()
	{
		$this->assertEquals('is a', Gears\String\substring($this->string, 5, 9));
		$this->assertEquals('is a string.', Gears\String\substring($this->string, 5));
	}

	public function testSlice()
	{
		$this->assertEquals('is a', Gears\String\slice($this->string, 5, 9));
		$this->assertEquals('is a string.', Gears\String\slice($this->string, 5));
	}

	public function testConCat()
	{
		$this->assertEquals($this->string.$this->string, Gears\String\conCat($this->string, $this->string));
	}

	public function testSplit()
	{
		$this->assertEquals(['T','h','i','s',' ','i','s',' ','a',' ','s','t','r','i','n','g','.'], Gears\String\split($this->string));
		$this->assertEquals(['This','is','a','string.'], Gears\String\split($this->string, ' '));
	}

	public function testRange()
	{
		$this->assertEquals(true, Gears\String\range($this->string, 0, 20));
		$this->assertEquals(true, Gears\String\range($this->string, 5, 20));
		$this->assertEquals(false, Gears\String\range($this->string, 0, 10));
		$this->assertEquals(false, Gears\String\range($this->string, 30, 40));
	}

	public function testCharAt()
	{
		$this->assertEquals('T', Gears\String\charAt($this->string, 0));
		$this->assertEquals('h', Gears\String\charAt($this->string, 1));
		$this->assertEquals('i', Gears\String\charAt($this->string, 2));
		$this->assertEquals('s', Gears\String\charAt($this->string, 3));
	}

	public function testCharCodeAt()
	{
		$this->assertEquals(84, Gears\String\charCodeAt($this->string, 0));
		$this->assertEquals(104, Gears\String\charCodeAt($this->string, 1));
		$this->assertEquals(105, Gears\String\charCodeAt($this->string, 2));
		$this->assertEquals(115, Gears\String\charCodeAt($this->string, 3));
	}

	public function testFromCharCode()
	{
		$this->assertEquals('T', Gears\String\fromCharCode(84));
		$this->assertEquals('h', Gears\String\fromCharCode(104));
		$this->assertEquals('i', Gears\String\fromCharCode(105));
		$this->assertEquals('s', Gears\String\fromCharCode(115));
	}

	public function testIndexOf()
	{
		$this->assertEquals(2, Gears\String\indexOf($this->string, 'is'));
		$this->assertEquals(false, Gears\String\indexOf($this->string, 'foo'));
	}

	public function testLastIndexOf()
	{
		$this->assertEquals(5, Gears\String\lastIndexOf($this->string, 'is'));
		$this->assertEquals(false, Gears\String\lastIndexOf($this->string, 'foo'));
	}

	public function testAscii()
	{
		$this->assertEquals('', Gears\String\ascii(''));
		$this->assertEquals('deja vu', Gears\String\ascii('déjà vu'));
		$this->assertEquals('i', Gears\String\ascii('ı'));
		$this->assertEquals('a', Gears\String\ascii('ä'));
	}

	public function testContains()
	{
		$this->assertTrue(Gears\String\contains('taylor', 'ylo'));
		$this->assertTrue(Gears\String\contains('taylor', array('ylo')));
		$this->assertFalse(Gears\String\contains('taylor', 'xxx'));
		$this->assertFalse(Gears\String\contains('taylor', array('xxx')));
		$this->assertFalse(Gears\String\contains('taylor', ''));
	}

	public function testStartsWith()
	{
		$this->assertTrue(Gears\String\startsWith('jason', 'jas'));
		$this->assertTrue(Gears\String\startsWith('jason', 'jason'));
		$this->assertTrue(Gears\String\startsWith('jason', array('jas')));
		$this->assertFalse(Gears\String\startsWith('jason', 'day'));
		$this->assertFalse(Gears\String\startsWith('jason', array('day')));
		$this->assertFalse(Gears\String\startsWith('jason', ''));
	}

	public function testEndsWith()
	{
		$this->assertTrue(Gears\String\endsWith('jason', 'on'));
		$this->assertTrue(Gears\String\endsWith('jason', 'jason'));
		$this->assertTrue(Gears\String\endsWith('jason', array('on')));
		$this->assertFalse(Gears\String\endsWith('jason', 'no'));
		$this->assertFalse(Gears\String\endsWith('jason', array('no')));
		$this->assertFalse(Gears\String\endsWith('jason', ''));
		$this->assertFalse(Gears\String\endsWith('7', ' 7'));
	}

	public function testFinish()
	{
		$this->assertEquals('abbc', Gears\String\finish('ab', 'bc'));
		$this->assertEquals('abbc', Gears\String\finish('abbcbc', 'bc'));
		$this->assertEquals('abcbbc', Gears\String\finish('abcbbcbc', 'bc'));
	}

	public function testIs()
	{
		$this->assertTrue(Gears\String\is('/', '/'));
		$this->assertFalse(Gears\String\is('/', ' /'));
		$this->assertFalse(Gears\String\is('/', '/a'));
		$this->assertTrue(Gears\String\is('foo/*', 'foo/bar/baz'));
		$this->assertTrue(Gears\String\is('*/foo', 'blah/baz/foo'));
	}

	public function testLength()
	{
		$this->assertEquals(17, Gears\String\length($this->string));
	}

	public function testLimit()
	{
		$this->assertEquals('This...', Gears\String\limit($this->string, 4));
	}

	public function testLower()
	{
		$this->assertEquals('this is a string.', Gears\String\lower($this->string));
	}

	public function testUpper()
	{
		$this->assertEquals('THIS IS A STRING.', Gears\String\upper($this->string));
	}

	public function testWords()
	{
		$this->assertEquals('Taylor...', Gears\String\words('Taylor Otwell', 1));
		$this->assertEquals('Taylor___', Gears\String\words('Taylor Otwell', 1, '___'));
		$this->assertEquals('Taylor Otwell', Gears\String\words('Taylor Otwell', 3));
		$this->assertEquals(' Taylor Otwell ', Gears\String\words(' Taylor Otwell ', 3));
		$this->assertEquals(' Taylor...', Gears\String\words(' Taylor Otwell ', 1));
		$nbsp = chr(0xC2).chr(0xA0);
		$this->assertEquals(' ', Gears\String\words(' '));
		$this->assertEquals($nbsp, Gears\String\words($nbsp));
	}

	public function testPlural()
	{
		$this->assertEquals('children', Gears\String\plural('child'));
		$this->assertEquals('tests', Gears\String\plural('test'));
		$this->assertEquals('deer', Gears\String\plural('deer'));
		$this->assertEquals('Children', Gears\String\plural('Child'));
		$this->assertEquals('CHILDREN', Gears\String\plural('CHILD'));
		$this->assertEquals('Tests', Gears\String\plural('Test'));
		$this->assertEquals('TESTS', Gears\String\plural('TEST'));
		$this->assertEquals('tests', Gears\String\plural('test'));
		$this->assertEquals('Deer', Gears\String\plural('Deer'));
		$this->assertEquals('DEER', Gears\String\plural('DEER'));
	}

	public function testSingular()
	{
		$this->assertEquals('Child', Gears\String\singular('Children'));
		$this->assertEquals('CHILD', Gears\String\singular('CHILDREN'));
		$this->assertEquals('Test', Gears\String\singular('Tests'));
		$this->assertEquals('TEST', Gears\String\singular('TESTS'));
		$this->assertEquals('Deer', Gears\String\singular('Deer'));
		$this->assertEquals('DEER', Gears\String\singular('DEER'));
		$this->assertEquals('Criterium', Gears\String\singular('Criteria'));
		$this->assertEquals('CRITERIUM', Gears\String\singular('CRITERIA'));
		$this->assertEquals('child', Gears\String\singular('children'));
		$this->assertEquals('test', Gears\String\singular('tests'));
		$this->assertEquals('deer', Gears\String\singular('deer'));
		$this->assertEquals('criterium', Gears\String\singular('criteria'));
	}

	public function testQuickRandom()
	{
		$randomInteger = mt_rand(1, 100);
		$this->assertEquals($randomInteger, strlen(Gears\String\quickRandom($randomInteger)));
		$this->assertInternalType('string', Gears\String\quickRandom());
		$this->assertEquals(16, strlen(Gears\String\quickRandom()));
	}

	public function testRandom()
	{
		$this->assertEquals(16, strlen(Gears\String\random()));
		$randomInteger = mt_rand(1, 100);
		$this->assertEquals($randomInteger, strlen(Gears\String\random($randomInteger)));
		$this->assertInternalType('string', Gears\String\random());
	}

	public function testTitle()
	{
		$this->assertEquals('Jefferson Costella', Gears\String\title('jefferson costella'));
		$this->assertEquals('Jefferson Costella', Gears\String\title('jefFErson coSTella'));
	}

	public function testSlug()
	{
		$this->assertEquals('hello-world', Gears\String\slug('hello world'));
		$this->assertEquals('hello-world', Gears\String\slug('hello-world'));
		$this->assertEquals('hello-world', Gears\String\slug('hello_world'));
		$this->assertEquals('hello_world', Gears\String\slug('hello_world', '_'));
	}

	public function testSnake()
	{
		$this->assertEquals('foo_bar', Gears\String\snake('fooBar'));
	}

	public function testCamelCase()
	{
		$this->assertEquals('fooBar', Gears\String\camel('FooBar'));
		$this->assertEquals('fooBar', Gears\String\camel('foo_bar'));
		$this->assertEquals('fooBarBaz', Gears\String\camel('Foo-barBaz'));
		$this->assertEquals('fooBarBaz', Gears\String\camel('foo-bar_baz'));
	}

	public function testStudly()
	{
		$this->assertEquals('FooBar',  Gears\String\studly('fooBar'));
		$this->assertEquals('FooBar',  Gears\String\studly('foo_bar'));
		$this->assertEquals('FooBarBaz',  Gears\String\studly('foo-barBaz'));
		$this->assertEquals('FooBarBaz',  Gears\String\studly('foo-bar_baz'));
	}
}