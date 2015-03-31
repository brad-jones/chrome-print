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

class StringObjectTest extends PHPUnit_Framework_TestCase
{
	protected $string = 'This is a string.';

	public function testFactory()
	{
		$this->assertTrue(is_a(Str::s($this->string), 'Gears\String'));
	}

	public function testToString()
	{
		$this->assertEquals($this->string, new Str($this->string));
	}

	public function testArrayAccess()
	{
		$this->assertEquals('T', Str::s($this->string)[0]);
	}

	public function testMacroableTrait()
	{
		Str::macro('testmacro', function(){ return 'the macro worked'; });
		$this->assertEquals('the macro worked', Str::testmacro());
	}

	public function testMethodChaining()
	{
		$this->assertEquals('THIS IS A GEARS\STRING.', Str::s($this->string)->replace('string', 'Gears\String')->upper());
	}

	public function testMatches()
	{
		$this->assertTrue(Str::s('<p>Hello</p>')->matches('<p>*</p>'));
		$this->assertFalse(Str::s('<p>Hello</p>')->matches('<p>* World</p>'));
	}

	public function testTo()
	{
		$this->assertEquals('this is a string.', Str::s(strtoupper($this->string))->to('lower'));
		$this->assertEquals('this is a string.', Str::s(strtoupper($this->string))->to('lowercase'));
		$this->assertEquals('this is a string.', Str::s(strtoupper($this->string))->to('lowerCase'));
		$this->assertEquals('this is a string.', Str::s(strtoupper($this->string))->to('small'));

		$this->assertEquals('THIS IS A STRING.', Str::s($this->string)->to('upper'));
		$this->assertEquals('THIS IS A STRING.', Str::s($this->string)->to('uppercase'));
		$this->assertEquals('THIS IS A STRING.', Str::s($this->string)->to('upperCase'));
		$this->assertEquals('THIS IS A STRING.', Str::s($this->string)->to('big'));

		$this->assertEquals('book', Str::s('books')->to('singular'));
		$this->assertEquals('book', Str::s('books')->to('one'));
		$this->assertEquals('book', Str::s('books')->to('1'));
		$this->assertEquals('book', Str::s('books')->to(1));

		$this->assertEquals('books', Str::s('book')->to('plural'));
		$this->assertEquals('books', Str::s('book')->to('many'));
		$this->assertEquals('books', Str::s('book')->to('lots'));

		$this->assertEquals('fooBar', Str::s('foo_bar')->to('camel'));
		$this->assertEquals('fooBar', Str::s('foo_bar')->to('camelcase'));
		$this->assertEquals('fooBar', Str::s('foo_bar')->to('camelCase'));

		$this->assertEquals('foo-bar', Str::s('foo_bar')->to('slug'));
		$this->assertEquals('foo-bar', Str::s('foo_bar')->to('slugcase'));
		$this->assertEquals('foo-bar', Str::s('foo_bar')->to('slugCase'));

		$this->assertEquals('Jefferson Costella', Str::s('jefFErson coSTella')->to('title'));
		$this->assertEquals('Jefferson Costella', Str::s('jefFErson coSTella')->to('titlecase'));
		$this->assertEquals('Jefferson Costella', Str::s('jefFErson coSTella')->to('titleCase'));

		$this->assertEquals('foo_bar', Str::s('fooBar')->to('snake'));
		$this->assertEquals('foo_bar', Str::s('fooBar')->to('snakecase'));
		$this->assertEquals('foo_bar', Str::s('fooBar')->to('snakeCase'));

		$this->assertEquals('FooBar', Str::s('fooBar')->to('studly'));
		$this->assertEquals('FooBar', Str::s('fooBar')->to('studlycase'));
		$this->assertEquals('FooBar', Str::s('fooBar')->to('studlyCase'));

		$this->assertEquals('Foo Bar', Str::s('fooBar')->to('human'));
		$this->assertEquals('Foo Bar', Str::s('foo-bar')->to('human'));
		$this->assertEquals('Foo Bar', Str::s('foo_bar')->to('human'));
	}

	public function testIsUTF8()
	{
		$this->assertTrue(Str::s('')->isUTF8());
		$this->assertTrue(Str::s('éééé')->isUTF8());
		$this->assertFalse(Str::s(utf8_decode('éééé'))->isUTF8());
	}

	public function testToUTF8()
	{
		$this->assertEquals('', Str::s('')->toUTF8());
		$this->assertEquals('éééé', Str::s(utf8_decode('éééé'))->toUTF8());
	}

	public function testToLatin1()
	{
		$this->assertEquals('', Str::s('')->toLatin1());
		$this->assertEquals('éééé', Str::s(utf8_encode('éééé'))->toLatin1());
	}

	public function testFixUTF8()
	{
		$this->assertEquals('', Str::s('')->fixUTF8());
		$this->assertEquals('Fédération Camerounaise de Football', Str::s('FÃÂ©dération Camerounaise de Football')->fixUTF8());
		$this->assertEquals('Fédération Camerounaise de Football', Str::s('FÃ©dÃ©ration Camerounaise de Football')->fixUTF8());
		$this->assertEquals('Fédération Camerounaise de Football', Str::s('FÃÂ©dÃÂ©ration Camerounaise de Football')->fixUTF8());
		$this->assertEquals('Fédération Camerounaise de Football', Str::s('FÃÂÂÂÂ©dÃÂÂÂÂ©ration Camerounaise de Football')->fixUTF8());
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

		$this->assertEquals($matches, Str::s($html)->wildCardMatch($pattern));
	}

	public function testSearch()
	{
		$this->assertEquals(8, Str::s($this->string)->search('a'));
		$this->assertEquals(-1, Str::s($this->string)->search('b'));
		$this->assertEquals(4, Str::s($this->string)->search('/ is /', true));
		$this->assertEquals(-1, Str::s($this->string)->search('/ foo /', true));
	}

	public function testReplace()
	{
		$this->assertEquals('This is a Str.', Str::s($this->string)->replace('string', 'Str'));
		$this->assertEquals($this->string, Str::s($this->string)->replace('foo', 'Str'));
		$this->assertEquals('This is a Str.', Str::s($this->string)->replace('/string/', 'Str', true));
		$this->assertEquals($this->string, Str::s($this->string)->replace('/ string /', 'Str', true));
		$this->assertEquals('This is a STRING.', Str::s($this->string)->replace('/string/', function($matches){ return strtoupper($matches[0]); }, true));
	}

	public function testMatch()
	{
		$this->assertEquals(['is a', 'is a'], Str::s($this->string.' '.$this->string)->match('/is a/'));
		$this->assertEquals([], Str::s($this->string.' '.$this->string)->match('/is foo/'));
	}

	public function testBetween()
	{
		$this->assertEquals($this->string, Str::s('<start>'.$this->string.'</end>')->between('<start>', '</end>'));
		$this->assertEquals('', Str::s('<start>'.$this->string.'</end>')->between('<start1>', '</end>'));
		$this->assertEquals('', Str::s('<start>'.$this->string.'</end>')->between('<start>', '</end1>'));
		$this->assertEquals('<start>'.$this->string.'</end>', Str::s('<start>'.$this->string.'</end>')->between('<start>', '</end>', true));
	}

	public function testBetweenRegx()
	{
		$xml = file_get_contents(__DIR__.'/data/books.xml');
		$expected = require(__DIR__.'/data/books.php');
		$this->assertEquals($expected, Str::s($xml)->betweenRegx('<author>', '</author>'));
	}

	public function testSubString()
	{
		$this->assertEquals('is a', Str::s($this->string)->substring(5, 9));
		$this->assertEquals('is a string.', Str::s($this->string)->substring(5));
	}

	public function testSlice()
	{
		$this->assertEquals('is a', Str::s($this->string)->slice(5, 9));
		$this->assertEquals('is a string.', Str::s($this->string)->slice(5));
	}

	public function testConCat()
	{
		$this->assertEquals($this->string.$this->string, Str::s($this->string)->conCat($this->string));
	}

	public function testSplit()
	{
		$this->assertEquals(['T','h','i','s',' ','i','s',' ','a',' ','s','t','r','i','n','g','.'], Str::s($this->string)->split());
		$this->assertEquals(['This','is','a','string.'], Str::s($this->string)->split(' '));
	}

	public function testRange()
	{
		$this->assertEquals(true, Str::s($this->string)->range(0, 20));
		$this->assertEquals(true, Str::s($this->string)->range(5, 20));
		$this->assertEquals(false, Str::s($this->string)->range(0, 10));
		$this->assertEquals(false, Str::s($this->string)->range(30, 40));
	}

	public function testCharAt()
	{
		$this->assertEquals('T', Str::s($this->string)->charAt(0));
		$this->assertEquals('h', Str::s($this->string)->charAt(1));
		$this->assertEquals('i', Str::s($this->string)->charAt(2));
		$this->assertEquals('s', Str::s($this->string)->charAt(3));
	}

	public function testCharCodeAt()
	{
		$this->assertEquals(84, Str::s($this->string)->charCodeAt(0));
		$this->assertEquals(104, Str::s($this->string)->charCodeAt(1));
		$this->assertEquals(105, Str::s($this->string)->charCodeAt(2));
		$this->assertEquals(115, Str::s($this->string)->charCodeAt(3));
	}

	public function testIndexOf()
	{
		$this->assertEquals(2, Str::s($this->string)->indexOf('is'));
		$this->assertEquals(false, Str::s($this->string)->indexOf('foo'));
	}

	public function testLastIndexOf()
	{
		$this->assertEquals(5, Str::s($this->string)->lastIndexOf('is'));
		$this->assertEquals(false, Str::s($this->string)->lastIndexOf('foo'));
	}

	public function testAscii()
	{
		$this->assertEquals('', Str::s('')->ascii());
		$this->assertEquals('deja vu', Str::s('déjà vu')->ascii());
		$this->assertEquals('i', Str::s('ı')->ascii());
		$this->assertEquals('a', Str::s('ä')->ascii()->toString());
	}

	public function testContains()
	{
		$this->assertTrue(Str::s('taylor')->contains('ylo'));
		$this->assertTrue(Str::s('taylor')->contains(array('ylo')));
		$this->assertFalse(Str::s('taylor')->contains('xxx'));
		$this->assertFalse(Str::s('taylor')->contains(array('xxx')));
		$this->assertFalse(Str::s('taylor')->contains(''));
	}

	public function testStartsWith()
	{
		$this->assertTrue(Str::s('jason')->startsWith('jas'));
		$this->assertTrue(Str::s('jason')->startsWith('jason'));
		$this->assertTrue(Str::s('jason')->startsWith(array('jas')));
		$this->assertFalse(Str::s('jason')->startsWith('day'));
		$this->assertFalse(Str::s('jason')->startsWith(array('day')));
		$this->assertFalse(Str::s('jason')->startsWith(''));
	}

	public function testEndsWith()
	{
		$this->assertTrue(Str::s('jason')->endsWith('on'));
		$this->assertTrue(Str::s('jason')->endsWith('jason'));
		$this->assertTrue(Str::s('jason')->endsWith(array('on')));
		$this->assertFalse(Str::s('jason')->endsWith('no'));
		$this->assertFalse(Str::s('jason')->endsWith(array('no')));
		$this->assertFalse(Str::s('jason')->endsWith(''));
		$this->assertFalse(Str::s('7')->endsWith(' 7'));
	}

	public function testFinish()
	{
		$this->assertEquals('abbc', Str::s('ab')->finish('bc'));
		$this->assertEquals('abbc', Str::s('abbcbc')->finish('bc'));
		$this->assertEquals('abcbbc', Str::s('abcbbcbc')->finish('bc'));
	}

	public function testLength()
	{
		$this->assertEquals(17, Str::s($this->string)->length());
	}

	public function testLimit()
	{
		$this->assertEquals('This...', Str::s($this->string)->limit(4));
	}

	public function testLower()
	{
		$this->assertEquals('this is a string.', Str::s($this->string)->lower());
	}

	public function testUpper()
	{
		$this->assertEquals('THIS IS A STRING.', Str::s($this->string)->upper());
	}

	public function testWords()
	{
		$this->assertEquals('Taylor...', Str::s('Taylor Otwell')->words(1));
		$this->assertEquals('Taylor___', Str::s('Taylor Otwell')->words(1, '___'));
		$this->assertEquals('Taylor Otwell', Str::s('Taylor Otwell')->words(3));
		$this->assertEquals(' Taylor Otwell ', Str::s(' Taylor Otwell ')->words(3));
		$this->assertEquals(' Taylor...', Str::s(' Taylor Otwell ')->words(1));
		$nbsp = chr(0xC2).chr(0xA0);
		$this->assertEquals(' ', Str::s(' ')->words());
		$this->assertEquals($nbsp, Str::s($nbsp)->words());
	}

	public function testPlural()
	{
		$this->assertEquals('children', Str::s('child')->plural());
		$this->assertEquals('tests', Str::s('test')->plural());
		$this->assertEquals('deer', Str::s('deer')->plural());
		$this->assertEquals('Children', Str::s('Child')->plural());
		$this->assertEquals('CHILDREN', Str::s('CHILD')->plural());
		$this->assertEquals('Tests', Str::s('Test')->plural());
		$this->assertEquals('TESTS', Str::s('TEST')->plural());
		$this->assertEquals('tests', Str::s('test')->plural());
		$this->assertEquals('Deer', Str::s('Deer')->plural());
		$this->assertEquals('DEER', Str::s('DEER')->plural());
	}

	public function testSingular()
	{
		$this->assertEquals('Child', Str::s('Children')->singular());
		$this->assertEquals('CHILD', Str::s('CHILDREN')->singular());
		$this->assertEquals('Test', Str::s('Tests')->singular());
		$this->assertEquals('TEST', Str::s('TESTS')->singular());
		$this->assertEquals('Deer', Str::s('Deer')->singular());
		$this->assertEquals('DEER', Str::s('DEER')->singular());
		$this->assertEquals('Criterium', Str::s('Criteria')->singular());
		$this->assertEquals('CRITERIUM', Str::s('CRITERIA')->singular());
		$this->assertEquals('child', Str::s('children')->singular());
		$this->assertEquals('test', Str::s('tests')->singular());
		$this->assertEquals('deer', Str::s('deer')->singular());
		$this->assertEquals('criterium', Str::s('criteria')->singular());
	}

	public function testTitle()
	{
		$this->assertEquals('Jefferson Costella', Str::s('jefferson costella')->title());
		$this->assertEquals('Jefferson Costella', Str::s('jefFErson coSTella')->title());
	}

	public function testSlug()
	{
		$this->assertEquals('hello-world', Str::s('hello world')->slug());
		$this->assertEquals('hello-world', Str::s('hello-world')->slug());
		$this->assertEquals('hello-world', Str::s('hello_world')->slug());
		$this->assertEquals('hello_world', Str::s('hello_world')->slug('_'));
	}

	public function testSnake()
	{
		$this->assertEquals('foo_bar', Str::s('fooBar')->snake());
	}

	public function testCamelCase()
	{
		$this->assertEquals('fooBar', Str::s('FooBar')->camel());
		$this->assertEquals('fooBar', Str::s('foo_bar')->camel());
		$this->assertEquals('fooBarBaz', Str::s('Foo-barBaz')->camel());
		$this->assertEquals('fooBarBaz', Str::s('foo-bar_baz')->camel());
	}

	public function testStudly()
	{
		$this->assertEquals('FooBar',  Str::s('fooBar')->studly());
		$this->assertEquals('FooBar',  Str::s('foo_bar')->studly());
		$this->assertEquals('FooBarBaz',  Str::s('foo-barBaz')->studly());
		$this->assertEquals('FooBarBaz',  Str::s('foo-bar_baz')->studly());
	}
}