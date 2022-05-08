class TestBBCode < Quiz
	def setup
		#@e_str = BBCode::ErrorStream.new();
	end

	def bbcode(input)
		#bb_scan = BBCode::Scanner.new();
		#bb_scan.InitFromStr(input, @e_str);
		#bb_parser = BBCode::Parser.new(bb_scan);
		return BBCode.parse(input);
	end

	def test_standard()
	#correct tags/nesting
		assert_equal(bbcode('[b][i]text[/i][/b]'), '<b><i>text</i></b>');
		assert_equal(bbcode('[b][i]text'), '<b><i>text</i></b>');
		assert_equal(bbcode('[b][i]text[/b]text[/i]'), '<b><i>text</i></b><i>text</i>');

	#bad tags/nesting
		assert_equal(bbcode('[b][blah]text[/blah][/b]'), '<b>[blah]text[/blah]</b>');
		assert_equal(bbcode('[b][i ]text[/i ][/b]'), '<b>[i ]text[/i ]</b>');
		assert_equal(bbcode('[ b]text[/b]'), '[ b]text[/b]');
		assert_equal(bbcode('[b][b]text[/b][/b]'), '<b><b>text</b></b>');

	#simple lists
		assert_equal(bbcode('[list][*]1[*]2[/list]'), '<ul><li>1</li><li>2</li></ul>');
		assert_equal(bbcode('[list=1][*]1[*]2[/list]'), '<ol type="1"><li>1</li><li>2</li></ol>');
		assert_equal(bbcode('[list=a][*]1[*]2[/list]'), '<ol type="a"><li>1</li><li>2</li></ol>');
		assert_equal(bbcode('[list=i][*]1[*]2[/list]'), '<ol type="i"><li>1</li><li>2</li></ol>');

	#nested lists
		assert_equal(bbcode('[list][*][list][*]1-1[*]1-2[/list][*]2[/list]'), '<ul><li><ul><li>1-1</li><li>1-2</li></ul></li><li>2</li></ul>');

	#broken lists
		assert_equal(bbcode('[list]123[/list]'), '<ul><li>123</li></ul>');
		assert_equal(bbcode('[list][/list][*]1[*]2'), '<ul><li></li></ul>[*]1[*]2');
		assert_equal(bbcode('[*]1[*]2'), '[*]1[*]2');


	#simple urls
		assert_equal(bbcode('[url]www.google.com[/url]'), 'www.google.com');
		assert_equal(bbcode('[url]http://www.google.com[/url]'), '<a class="body" href="http://www.google.com" target="_new">http://www.google.com</a>');
		assert_equal(bbcode('[url]https://www.google.com[/url]'), '<a class="body" href="https://www.google.com" target="_new">https://www.google.com</a>');
		assert_equal(bbcode('[url]/link[/url]'), '<a class="body" href="/link" target="_new">/link</a>');
		assert_equal(bbcode('[url=/link]asdf[/url]'), '<a class="body" href="/link" target="_new">asdf</a>');
		assert_equal(bbcode('[url=http://www.google.com]asdf[/url]'), '<a class="body" href="http://www.google.com" target="_new">asdf</a>');
		assert_equal(bbcode('[url=www.google.com]google[/url]'), '[url=www.google.com]google[/url]');

	#url nesting - simple tags
		assert_equal(bbcode('[url=/link][b]test[/b][/url]'), '<a class="body" href="/link" target="_new"><b>test</b></a>');
		assert_equal(bbcode('[url=/link][b]test[/url]'), '<a class="body" href="/link" target="_new"><b>test</b></a><b></b>');

	#url urlencoding
		#assert_equal(bbcode('[url=http://www.asdf.com/asdf.php?asdf[asdf]=asdf]asdf[/url]'), '<a class="body" href="http://www.asdf.com/asdf.php?asdf%5Basdf" target="_new">=asdf]asdf</a>');
		assert_equal(bbcode('[url=http://www.asdf.com/asdf.php?asdf%5Basdf%5D=asdf]asdf[/url]'), '<a class="body" href="http://www.asdf.com/asdf.php?asdf%5Basdf%5D=asdf" target="_new">asdf</a>');
		#assert_equal(bbcode('[url=/asdf" onLoad="alert()]asdf[/url]'), '<a class="body" href="/asdf%22+onLoad=%22alert%28%29" target="_new">asdf</a>');
		assert_equal(bbcode('[url=/asdf" onLoad="alert()]asdf[/url]'), '<a class="body" href="/asdf%22 onLoad=%22alert()" target="_new">asdf</a>');

	#simple img tests
		assert_equal(bbcode('[img]http://img.nexopia.com/asdf1.jpg[/img]'), '<img src="http://img.nexopia.com/asdf1.jpg" border="0"/>');
		assert_equal(bbcode('[IMG]http://img.nexopia.com/asdf1.jpg[/IMG]'), '<img src="http://img.nexopia.com/asdf1.jpg" border="0"/>');
		assert_equal(bbcode('[img=http://img.nexopia.com/asdf2.jpg]'), '<img src="http://img.nexopia.com/asdf2.jpg" border="0"/>');
		assert_equal(bbcode('[url=http://www.google.com][img]http://img.nexopia.com/asdf.jpg[/img][/url]'), '<a class="body" href="http://www.google.com" target="_new"><img src="http://img.nexopia.com/asdf.jpg" border="0"/></a>');
		assert_equal(bbcode('[img=http://www.asdf.com/asdf.php]'), '<img src="http://www.asdf.com/asdf.php" border="0"/>');

	#broken img tests
		assert_equal(bbcode('[img]asdf3.jpg[/img]'), '[img]asdf3.jpg[/img]');
		assert_equal(bbcode('[img=asdf4.jpg]'), '[img=asdf4.jpg]');
		assert_equal(bbcode('[img]sdlkjsdf'), '[img]sdlkjsdf');
		assert_equal(bbcode('[img][b]sdlkjsdf[/b]'), '[img]<b>sdlkjsdf</b>');
		assert_equal(bbcode('[img]asd[b]sdlkjsdf[/b]'), '[img]asd<b>sdlkjsdf</b>');
		assert_equal(bbcode('[b]asd[img]asdsdlkjsdf[/b]'), '<b>asd[img]asdsdlkjsdf</b>');

	#url encode img tests
		assert_equal(bbcode('[img=http://www.asdf.com/asdf.php?asdf=asdf]'), '<img src="http://www.asdf.com/asdf.php?asdf=asdf" border="0"/>');
		#assert_equal(bbcode('[img=http://www.asdf.com/asdf.php?asdf[asdf]=asdf]'), '<img src="http://www.asdf.com/asdf.php?asdf%5Basdf" border="0"/>=asdf]');
		assert_equal(bbcode('[img=http://www.asdf.com/asdf.php?asdf[asdf]=asdf]'), '<img src="http://www.asdf.com/asdf.php?asdf[asdf" border="0"/>=asdf]');
		assert_equal(bbcode('[img=http://www.asdf.com/asdf.php?asdf%5Basdf%5D=asdf]'), '<img src="http://www.asdf.com/asdf.php?asdf%5Basdf%5D=asdf" border="0"/>');


		assert_equal(bbcode("[ b ]"), "[ b ]");

	end
end
