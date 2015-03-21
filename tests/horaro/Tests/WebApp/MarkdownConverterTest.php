<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Tests\WebApp;

use horaro\WebApp\MarkdownConverter;
use Michelf\Markdown;

class MarkdownConverterTest extends \PHPUnit_Framework_TestCase {
	protected $md;

	protected function setUp() {
		$this->md = new MarkdownConverter(new Markdown());
	}

	/**
	 * @dataProvider  convertProvider
	 */
	public function testConvert($markdown, $expected) {
		$converted = $this->md->convert($markdown);
		$this->assertEquals($expected, $converted);
	}

	public function convertProvider() {
		return [
			// basic formatting
			['test',     '<p>test</p>'],
			['*italic*', '<p><em>italic</em></p>'],
			['**bold**', '<p><strong>bold</strong></p>'],

			// links
			['a [link](https://example.com/)', '<p>a <a href="https://example.com/">link</a></p>'],
			['a [link](https://x.com/test?foo=bar&xy=foo%20bar)', '<p>a <a href="https://x.com/test?foo=bar&amp;xy=foo%20bar">link</a></p>'],

			// images
			['an ![image](https://example.com/)', '<p>an <img class="img-responsive" src="https://example.com/" alt="image"></p>'],
			['an ![image](https://x.com/test?foo=bar&xy=foo%20bar)', '<p>an <img class="img-responsive" src="https://x.com/test?foo=bar&amp;xy=foo%20bar" alt="image"></p>'],

			// invalid URIs
			['a [link](javascript:alert("xss"))', '<p>a <a href="#">link</a></p>'],
			['a [link](%6a%61%76%61%73%63%72%69%70%74:alert("xss"))', '<p>a <a href="#">link</a></p>'],
			['a [link](http://foo.com/"onclick)', '<p>a <a href="http://foo.com/&quot;onclick">link</a></p>'],

			// regular HTML encoding
			['a "test" string&stuff', '<p>a &quot;test&quot; string&amp;stuff</p>'],
			['some <<tags with="attributes">>', '<p>some &lt;&lt;tags with=&quot;attributes&quot;&gt;&gt;</p>'],
			['\'\';!--"<XSS>=&{()}', '<p>&#039;&#039;;!--&quot;&lt;XSS&gt;=&amp;{()}</p>'],

			// the ">" is not encoded because the Markdown library handles that round and it does not use ENT_QUOTES
			['[test](http://foo.com/\'\';!--"<XSS>=&{})', '<p><a href="http://foo.com/\'\';!--&quot;&lt;XSS>=&amp;{}">test</a></p>'],
		];
	}
}
