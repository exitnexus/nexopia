describe BBCode do
	def bbcode(input)
		return BBCode.parse(input);
	end
	
	it "should handle bold" do
		bbcode('[b]text[/b]').should eql('<b>text</b>');
		bbcode('[B]text[/B]').should eql('<b>text</b>');
		bbcode('[B]text[/b]').should eql('<b>text</b>');
		bbcode('[b]text[/B]').should eql('<b>text</b>');
	end
	
	it "should handle italics" do
		bbcode('[i]text[/i]').should eql('<i>text</i>');
		bbcode('[I]text[/I]').should eql('<i>text</i>');
		bbcode('[I]text[/i]').should eql('<i>text</i>');
		bbcode('[i]text[/I]').should eql('<i>text</i>');
	end

	it "should handle underline" do
		bbcode('[u]text[/u]').should eql('<u>text</u>');
		bbcode('[U]text[/U]').should eql('<u>text</u>');
		bbcode('[U]text[/u]').should eql('<u>text</u>');
		bbcode('[u]text[/U]').should eql('<u>text</u>');
	end

	it "should handle strikethrough" do
		bbcode('[strike]text[/strike]').should eql('<strike>text</strike>');
		bbcode('[STRIKE]text[/STRIKE]').should eql('<strike>text</strike>');
		bbcode('[STRIKE]text[/strike]').should eql('<strike>text</strike>');
		bbcode('[strike]text[/STRIKE]').should eql('<strike>text</strike>');
	end

	it "should handle subscript" do
		bbcode('[sub]text[/sub]').should eql('<sub>text</sub>');
		bbcode('[SUB]text[/SUB]').should eql('<sub>text</sub>');
		bbcode('[SUB]text[/sub]').should eql('<sub>text</sub>');
		bbcode('[sub]text[/SUB]').should eql('<sub>text</sub>');
	end

	it "should handle superscript" do
		bbcode('[sup]text[/sup]').should eql('<sup>text</sup>');
		bbcode('[SUP]text[/SUP]').should eql('<sup>text</sup>');
		bbcode('[SUP]text[/sup]').should eql('<sup>text</sup>');
		bbcode('[sup]text[/SUP]').should eql('<sup>text</sup>');
	end

	it "should handle changing text size" do
		bbcode('[size=1]text[/size]').should eql('<font size="1">text</font>');
		bbcode('[size=2]text[/size]').should eql('<font size="2">text</font>');
		bbcode('[size=3]text[/size]').should eql('<font size="3">text</font>');
		bbcode('[size=4]text[/size]').should eql('<font size="4">text</font>');
		bbcode('[size=5]text[/size]').should eql('<font size="5">text</font>');
		bbcode('[size=6]text[/size]').should eql('<font size="6">text</font>');
		bbcode('[size=7]text[/size]').should eql('<font size="7">text</font>');

		bbcode('[SIZE=1]text[/SIZE]').should eql('<font size="1">text</font>');
		bbcode('[SIZE=1]text[/size]').should eql('<font size="1">text</font>');
		bbcode('[size=1]text[/SIZE]').should eql('<font size="1">text</font>');
	end

	it "should handle left align" do
		bbcode('[left]text[/left]').should eql('<div style="text-align:left">text</div>');
		bbcode('[LEFT]text[/LEFT]').should eql('<div style="text-align:left">text</div>');
		bbcode('[LEFT]text[/left]').should eql('<div style="text-align:left">text</div>');
		bbcode('[left]text[/LEFT]').should eql('<div style="text-align:left">text</div>');
	end

	it "should handle centre align" do
		bbcode('[center]text[/center]').should eql('<center>text</center>');
		bbcode('[CENTER]text[/CENTER]').should eql('<center>text</center>');
		bbcode('[CENTER]text[/center]').should eql('<center>text</center>');
		bbcode('[center]text[/CENTER]').should eql('<center>text</center>');
	end

	it "should handle right align" do
		bbcode('[right]text[/right]').should eql('<div style="text-align:right">text</div>');
		bbcode('[RIGHT]text[/RIGHT]').should eql('<div style="text-align:right">text</div>');
		bbcode('[RIGHT]text[/right]').should eql('<div style="text-align:right">text</div>');
		bbcode('[right]text[/RIGHT]').should eql('<div style="text-align:right">text</div>');
	end
	
	it "should handle justify" do
		bbcode('[justify]text[/justify]').should eql('<div style="text-align:justify">text</div>')
		bbcode('[size=2][justify]text[/justify][/size]').should eql('<font size="2"><div style="text-align:justify">text</div></font>')
		bbcode('[justify][size=2]text[/size][/justify]').should eql('<div style="text-align:justify"><font size="2">text</font></div>')
	end

	it "should handle named colors" do
		bbcode('[color=red]text[/color]').should eql('<font color="red">text</font>');
		bbcode('[COLOR=red]text[/COLOR]').should eql('<font color="red">text</font>');
		bbcode('[COLOR=red]text[/color]').should eql('<font color="red">text</font>');
		bbcode('[color=red]text[/COLOR]').should eql('<font color="red">text</font>');
	end

	it "should handle hex colours" do
		bbcode('[color=#05A343]text[/color]').should eql('<font color="#05A343">text</font>');
		bbcode('[COLOR=#05A343]text[/COLOR]').should eql('<font color="#05A343">text</font>');
		bbcode('[COLOR=#05A343]text[/color]').should eql('<font color="#05A343">text</font>');
		bbcode('[color=#05A343]text[/COLOR]').should eql('<font color="#05A343">text</font>');
	end

	it "should handle the proper spelling of colour" do
		bbcode('[colour=red]text[/colour]').should eql('<font color="red">text</font>');
	end

	it "should handle linking to other users" do
		bbcode('[user]text[/user]').should eql('<a class="body user_content" href="/users/text" target="_new">text</a>');
		bbcode('[USER]text[/USER]').should eql('<a class="body user_content" href="/users/text" target="_new">text</a>');
		bbcode('[USER]text[/user]').should eql('<a class="body user_content" href="/users/text" target="_new">text</a>');
		bbcode('[user]text[/USER]').should eql('<a class="body user_content" href="/users/text" target="_new">text</a>');
	end

	it "should handle linking to other webpages" do
		bbcode('[url]http://www.google.com/[/url]').should eql('<a class="body user_content" href="http://www.google.com/" target="_new">http://www.google.com/</a>');
		bbcode('[URL]http://www.google.com/[/URL]').should eql('<a class="body user_content" href="http://www.google.com/" target="_new">http://www.google.com/</a>');
		bbcode('[URL]http://www.google.com/[/url]').should eql('<a class="body user_content" href="http://www.google.com/" target="_new">http://www.google.com/</a>');
		bbcode('[url]http://www.google.com/[/URL]').should eql('<a class="body user_content" href="http://www.google.com/" target="_new">http://www.google.com/</a>');
	end

	it "should handle text links to other webpages" do
		bbcode('[url=http://www.google.com/]text[/url]').should eql('<a class="body user_content" href="http://www.google.com/" target="_new">text</a>');
		bbcode('[URL=http://www.google.com/]text[/URL]').should eql('<a class="body user_content" href="http://www.google.com/" target="_new">text</a>');
		bbcode('[URL=http://www.google.com/]text[/url]').should eql('<a class="body user_content" href="http://www.google.com/" target="_new">text</a>');
		bbcode('[url=http://www.google.com/]text[/URL]').should eql('<a class="body user_content" href="http://www.google.com/" target="_new">text</a>');
	end

	it "should handle images" do
		bbcode('[img]http://text[/img]').should eql('<img minion_name="user_content_image" url="http://text" border="0">');
		bbcode('[IMG]http://text[/IMG]').should eql('<img minion_name="user_content_image" url="http://text" border="0">');
		bbcode('[IMG]http://text[/img]').should eql('<img minion_name="user_content_image" url="http://text" border="0">');
		bbcode('[img]http://text[/IMG]').should eql('<img minion_name="user_content_image" url="http://text" border="0">');
	end

	it "should handle alternate images" do
		bbcode('[img=http://text]').should eql('<img minion_name="user_content_image" url="http://text" border="0">');
		bbcode('[IMG=http://text]').should eql('<img minion_name="user_content_image" url="http://text" border="0">');
	end

	it "should handle linking images" do
		bbcode('[url=http://yourlink][img=http://imagelocation][/url]').should eql('<a class="body user_content" href="http://yourlink" target="_new"><img minion_name="user_content_image" url="http://imagelocation" border="0"></a>');
	end

	it "should handle resizing images by percent width" do
		bbcode('[img=75%x]http://users.nexopia.com/uploads/1808/1808085/image_resize_example.jpg[/img]').should eql('<img minion_name="user_content_image" url="http://users.nexopia.com/uploads/1808/1808085/image_resize_example.jpg" border="0" width="75%">');
	end

	it "should handle resizing images by percent height" do
		bbcode('[img=x75%]http://users.nexopia.com/uploads/1808/1808085/image_resize_example.jpg[/img]').should eql('<img minion_name="user_content_image" url="http://users.nexopia.com/uploads/1808/1808085/image_resize_example.jpg" border="0" height="75%">');
	end

	it "should handle resizing images by fixed width" do
		bbcode('[img=100x]http://users.nexopia.com/uploads/1808/1808085/image_resize_example.jpg[/img]').should eql('<img minion_name="user_content_image" url="http://users.nexopia.com/uploads/1808/1808085/image_resize_example.jpg" border="0" width="100">');
	end

	it "should handle resizing images by fixed height" do
		bbcode('[img=x100]http://users.nexopia.com/uploads/1808/1808085/image_resize_example.jpg[/img]').should eql('<img minion_name="user_content_image" url="http://users.nexopia.com/uploads/1808/1808085/image_resize_example.jpg" border="0" height="100">');
	end

	it "should handle url'ed img tags with resized images" do
		bbcode('[url=http://www.nexopia.com/users/ccthompson/gallery/2][img=230x]http://images.nexopia.com/gallery/28/28610/15.jpg[/img][/url]').should == '<a class="body user_content" href="http://www.nexopia.com/users/ccthompson/gallery/2" target="_new"><img minion_name="user_content_image" url="http://images.nexopia.com/gallery/28/28610/15.jpg" border="0" width="230"></a>';
	end

	it "should handle unordered lists" do
		bbcode('[list][*]text 1[*]text 2[/list]').should eql('<ul><li>text 1<li>text 2</ul>');
		bbcode('[LIST][*]text 1[*]text 2[/LIST]').should eql('<ul><li>text 1<li>text 2</ul>');
		bbcode('[LIST][*]text 1[*]text 2[/list]').should eql('<ul><li>text 1<li>text 2</ul>');
		bbcode('[list][*]text 1[*]text 2[/LIST]').should eql('<ul><li>text 1<li>text 2</ul>');
	end

	it "should handle ordered lists with letters" do
		bbcode('[list=a][*]text 1[*]text 2[/list]').should eql('<ol type="a"><li>text 1<li>text 2</ol>');
	end

	it "should handle ordered lists with numbers" do
		bbcode('[list=1][*]text 1[*]text 2[/list]').should eql('<ol type="1"><li>text 1<li>text 2</ol>');
	end

	it "should handle ordered lists with roman numerals" do
		bbcode('[list=i][*]text 1[*]text 2[/list]').should eql('<ol type="i"><li>text 1<li>text 2</ol>');
	end

	it "should handle quotes" do
		bbcode('[quote]text[/quote]').should eql('<div class="quote">text</div>');
		bbcode('[QUOTE]text[/QUOTE]').should eql('<div class="quote">text</div>');
		bbcode('[QUOTE]text[/quote]').should eql('<div class="quote">text</div>');
		bbcode('[quote]text[/QUOTE]').should eql('<div class="quote">text</div>');
	end
	
	it "should handle horizontal rules" do
		bbcode('[hr]').should eql('<hr />');
		bbcode('[HR]').should eql('<hr />');
		bbcode('[hr][hr]').should eql('<hr /><hr />');
		bbcode('[hr]text[hr]').should eql('<hr />text<hr />');
	end

	it "should handle correct tags/nesting" do
		bbcode('[b][i]text[/i][/b]').should eql('<b><i>text</i></b>');
		bbcode('[b][i]text').should eql('<b><i>text</i></b>');
		bbcode('[b][i]text[/b]text[/i]').should eql('<b><i>text</i></b>text[/i]');
	end

	# Much of the rest just documents current behaviour, but
	# other reasonable approaches would be fine, too.
	it "should handle bad tags/nesting" do
		bbcode('[b][blah]text[/blah][/b]').should eql('<b>[blah]text[/blah]</b>');
		bbcode('[b][i ]text[/i ][/b]').should eql('<b>[i]</b> ]text[/i ][/b]');
		bbcode('[ b]text[/b]').should eql('[ b]text[/b]');
		bbcode('[b][b]text[/b][/b]').should eql('<b><b>text</b></b>');
	end

	it "should handle simple lists" do
		bbcode('[list][*]1[*]2[/list]').should eql('<ul><li>1<li>2</ul>');
		bbcode('[list=1][*]1[*]2[/list]').should eql('<ol type="1"><li>1<li>2</ol>');
		bbcode('[list=a][*]1[*]2[/list]').should eql('<ol type="a"><li>1<li>2</ol>');
		bbcode('[list=i][*]1[*]2[/list]').should eql('<ol type="i"><li>1<li>2</ol>');
	end

	it "should handle nested lists" do
		bbcode('[list][*][list][*]1-1[*]1-2[/list][*]2[/list]').should eql('<ul><li><ul><li>1-1<li>1-2</ul><li>2</ul>');
	end

	it "should handle broken lists" do
		bbcode('[list]123[/list]').should eql('<ul>123</ul>');
		bbcode('[list][/list][*]1[*]2').should eql('<ul></ul><li>1<li>2');
		bbcode('[*]1[*]2').should eql('<li>1<li>2');
	end

	it "should handle url nesting - simple tags" do
		bbcode('[url=/link][b]test[/b][/url]').should eql('<a class="body user_content" href="/link" target="_new"><b>test</b></a>');
		bbcode('[url=/link][b]test[/url]').should eql('<a class="body user_content" href="/link" target="_new"><b>test</b></a>');
	end

	it "should handle url urlencoding" do
		bbcode('[url=http://www.asdf.com/asdf.php?asdf%5Basdf%5D=asdf]asdf[/url]').should eql('<a class="body user_content" href="http://www.asdf.com/asdf.php?asdf%5Basdf%5D=asdf" target="_new">asdf</a>');
	end

	it "should handle url encode img tags" do
		bbcode('[img=http://www.asdf.com/asdf.php?asdf=asdf]').should eql('<img minion_name="user_content_image" url="http://www.asdf.com/asdf.php?asdf=asdf" border="0">');
		bbcode('[img=http://www.asdf.com/asdf.php?asdf[asdf]=asdf]').should eql('<img minion_name="user_content_image" url="http://www.asdf.com/asdf.php?asdf[asdf]=asdf" border="0">');
		bbcode('[img=http://www.asdf.com/asdf.php?asdf%5Basdf%5D=asdf]').should eql('<img minion_name="user_content_image" url="http://www.asdf.com/asdf.php?asdf%5Basdf%5D=asdf" border="0">');
	end

	it "should handle non-bbcode" do
		bbcode("[ b ]").should eql("[ b ]");
	end
	
	it "should handle capitalized tags" do
		bbcode('[IMG]http://www.nexopia.com/asdf1.jpg[/IMG]').should == '<img minion_name="user_content_image" url="http://www.nexopia.com/asdf1.jpg" border="0">';
		bbcode('[SIZE=4][B][I]Testing 1,2,3[/I][/B][/SIZE]').should == '<font size="4"><b><i>Testing 1,2,3</i></b></font>'
	end

	it "should handle img tags nested in url tags" do
		bbcode('[url=http://www.google.com][img]http://www.nexopia.com/asdf.jpg[/img][/url]').should == '<a class="body user_content" href="http://www.google.com" target="_new"><img minion_name="user_content_image" url="http://www.nexopia.com/asdf.jpg" border="0"></a>';
	end

	it "should handle broken images gracefully" do
		bbcode('[img]sdlkjsdf').should == '<img minion_name="user_content_image" url="sdlkjsdf" border="0">';
		bbcode('[img][b]sdlkjsdf[/b]').should == '<img minion_name="user_content_image" url="[b]sdlkjsdf" border="0">';
		bbcode('[img]asd[b]sdlkjsdf[/b]').should == '<img minion_name="user_content_image" url="asd[b]sdlkjsdf" border="0">';
		bbcode('[b]asd[img]asdsdlkjsdf[/b]').should == '<b>asd<img minion_name="user_content_image" url="asdsdlkjsdf" border="0"></b>';
	end
	
	it "should handle tags nested around images" do
		bbcode('[b][i]foo[img]bar[/img][/i][/b]').should == '<b><i>foo<img minion_name="user_content_image" url="bar" border="0"></i></b>'
		bbcode('[img][b][i]bar[/i][/b][/img]').should == '<img minion_name="user_content_image" url="[b][i]bar[/i][/b]" border="0">'
		bbcode('[url=http://www.nexopia.com/users/%5BLunacy%5DFRINGE/][size=5][font=geneva][center]"And only the foolish pack emergency underpants."[/center][/font][/size][/url][center][img]http://i236.photobucket.com/albums/ff163/kalanigan/flower.png[/img][/center]').should == '<a class="body user_content" href="http://www.nexopia.com/users/%5BLunacy%5DFRINGE/" target="_new"><font size="5"><font face="geneva"><center>"And only the foolish pack emergency underpants."</center></font></font></a><center><img minion_name="user_content_image" url="http://i236.photobucket.com/albums/ff163/kalanigan/flower.png" border="0"></center>'
	end
	
	# Note: Just because this test fails at some point doesn't mean you've necessarily broken the bbcode parser.
	# I'm of the opinion that all bets are off if you're entering invalid bbcode, so if cases like this are spitting
	# out different results, so long as they're semi-reasonable, given the input, just modify the test to succeed
	# on the new bbcode. The case below is especially idiotic. I think any result that the bbcode parser spits out
	# for this, short of one that becomes a huge memory hog, is a valid one.
	it "should handle insane incorrect nesting of certain tags as gracefully as possible" do
		bbcode('[img]test.jpg[/img][url][img]test.jpg[/img][url][img]test.jpg[/img]'+
					'[img]test.jpg[/img][url][img]test.jpg[/img][url][img]test.jpg[/img]' +
					'[img]test.jpg[/img][url][img]test.jpg[/img][url][img]test.jpg[/img]' +
					'[img]test.jpg[/img][url][img]test.jpg[/img][url][img]test.jpg[/img]').should == '<img minion_name="user_content_image" url="test.jpg" border="0"><a class="body user_content" href="[img]test.jpg" target="_new">[img]test.jpg</a>';
		bbcode('[user][img][url]test.html[/img][/url][/user]').should == 			'<a class="body user_content" href="/users/%5Bimg%5D%5Burl%5Dtest.html%5B%2Fimg%5D%5B%2Furl%5D" target="_new">[img][url]test.html[/img][/url]</a>';
	end
	
	it "should handle long [code] statements" do
		bbcode('[code] [font=impact] [color=red] [sup] something[/sup][/color][/font][/code]').should == ' [font=impact] [color=red] [sup] something[/sup][/color][/font]'
	end
	
	it "should handle url tags with formatting inside" do
		bbcode('[size=5]I like [url=http://www.youtube.com][size=5]YouTube[/size][/url] videos[/size]').should == '<font size="5">I like <a class="body user_content" href="http://www.youtube.com" target="_new"><font size="5">YouTube</font></a> videos</font>'
		bbcode('[url=http://www.facebook.com/addfriend.php?id=641291196][size=4][font=Arial]Facebook[/font][/size][/url][size=4]![/size]').should == '<a class="body user_content" href="http://www.facebook.com/addfriend.php?id=641291196" target="_new"><font size="4"><font face="Arial">Facebook</font></font></a><font size="4">!</font>'
		bbcode('[url=http://www.new.facebook.com/profile.php?id=633290516&ref=name][color=#3B5998][sub]facebook[/sub][/color][/url]').should == '<a class="body user_content" href="http://www.new.facebook.com/profile.php?id=633290516&ref=name" target="_new"><font color="#3B5998"><sub>facebook</sub></font></a>'
		bbcode('[url=http://www.nexopia.com/weblog.php?uid=870201&id=3240781][b][size=3]Noise[/size][size=2]Noise[/size][size=1]Noise[/size][/b][/url]').should == '<a class="body user_content" href="http://www.nexopia.com/weblog.php?uid=870201&id=3240781" target="_new"><b><font size="3">Noise</font><font size="2">Noise</font><font size="1">Noise</font></b></a>'
	end
	
	it "should handle botched params" do
		bbcode('[size=]blah[/size]').should == '<font size="">blah</font>'
	end

	it "should handle excessively complex imgs and urls" do
			bbcode('[url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url][url]foo').should == '<a class="body user_content" href="" target="_new"></a>';
		# Real-world example
		bbcode('[img]http://i195.photobucket.com/albums/z283/psy-future/MagicMushroomsWorld.jpg[/img][img]http://profileangels.com/pics/love/flowers/0002.gif[/img][img]http://profileangels.com/pics/decorations/betty_boop/0003.gif[/img][img]http://profileangels.com/pics/decorations/wolf/0004.gif[/img][img]http://profileangels.com/pics/love/kissing_lips/0010.gif[/img][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1441_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1442_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1446_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1449_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1453_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1454_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1456_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1458_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1463_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1464_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1472_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1473_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1475_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1476_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1480_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1486_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1484_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1482_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1495_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1494_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1491_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1466_00.jpg[/img][url]').should == '<img minion_name="user_content_image" url="http://i195.photobucket.com/albums/z283/psy-future/MagicMushroomsWorld.jpg" border="0"><img minion_name="user_content_image" url="http://profileangels.com/pics/love/flowers/0002.gif" border="0"><img minion_name="user_content_image" url="http://profileangels.com/pics/decorations/betty_boop/0003.gif" border="0"><img minion_name="user_content_image" url="http://profileangels.com/pics/decorations/wolf/0004.gif" border="0"><img minion_name="user_content_image" url="http://profileangels.com/pics/love/kissing_lips/0010.gif" border="0"><img minion_name="user_content_image" url="http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1441_00.jpg" border="0"><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1442_00.jpg" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1442_00.jpg</a>';
		# Real-world example if the user had used more valid BB code
		bbcode('[img]http://i195.photobucket.com/albums/z283/psy-future/MagicMushroomsWorld.jpg[/img][img]http://profileangels.com/pics/love/flowers/0002.gif[/img][img]http://profileangels.com/pics/decorations/betty_boop/0003.gif[/img][img]http://profileangels.com/pics/decorations/wolf/0004.gif[/img][img]http://profileangels.com/pics/love/kissing_lips/0010.gif[/img][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1441_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1442_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1446_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1449_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1453_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1454_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1456_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1458_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1463_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1464_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1472_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1473_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1475_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1476_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1480_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1486_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1484_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1482_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1495_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1494_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1491_00.jpg[/img][/url][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1466_00.jpg[/img][/url]').should == '<img minion_name="user_content_image" url="http://i195.photobucket.com/albums/z283/psy-future/MagicMushroomsWorld.jpg" border="0"><img minion_name="user_content_image" url="http://profileangels.com/pics/love/flowers/0002.gif" border="0"><img minion_name="user_content_image" url="http://profileangels.com/pics/decorations/betty_boop/0003.gif" border="0"><img minion_name="user_content_image" url="http://profileangels.com/pics/decorations/wolf/0004.gif" border="0"><img minion_name="user_content_image" url="http://profileangels.com/pics/love/kissing_lips/0010.gif" border="0"><img minion_name="user_content_image" url="http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1441_00.jpg" border="0"><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1442_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1442_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1446_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1446_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1449_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1449_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1453_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1453_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1454_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1454_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1456_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1456_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1458_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1458_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1463_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1463_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1464_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1464_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1472_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1472_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1473_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1473_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1475_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1475_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1476_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1476_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1480_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1480_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1486_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1486_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1484_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1484_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1482_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1482_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1495_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1495_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1494_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1494_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1491_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1491_00.jpg[/img]</a><a class="body user_content" href="[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1466_00.jpg[/img]" target="_new">[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1466_00.jpg[/img]</a>'
	end
end
