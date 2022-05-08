lib_require :Devutils, 'quiz'
lib_require :Video, 'video_info'

# Tests to ensure that we're correctly parsing the content for the various sites we support.
# These tests may be very breakable due to the fact that we're looking for very specific things
# in our results. This may be a good thing, in that we will know whenever tests are run if the
# way the sites operate changes. However, it may be annoyingly brittle as well (for example,
# someone changes tags on break.com and because we're looking for specific tags, we fail, even
# though everything's operating in a perfectly valid manner), suggesting that we may want to make
# the nature of the tests different. We can see if it turns out to be a problem.
#
# As well, it might be nice to devise a way of automatically grabbing embed information when
# the tests are run and parsing it to ensure that new content is still meeting the previous
# specifications that the VideoInfo parsing was developed against.
class TestVideoInfo < Quiz
	def setup
	end
	
	def teardown
		return;
	end
	
	def test_youtube_info
		youtube_test_id = "olawhHjfNeI";
    youtube_test_embed = <<-EOS
	    <object width="425" height="350">
	      <param name="movie" value="http://www.youtube.com/v/olawhHjfNeI"></param>
	      <param name="wmode" value="transparent"></param>
	      <embed 
	        src="http://www.youtube.com/v/olawhHjfNeI" 
	        type="application/x-shockwave-flash" 
	        wmode="transparent" 
	        width="425" 
	        height="350"></embed>
	    </object>
	    EOS
    youtube_test_adjembed = <<-EOS
	    <object width="{width}" height="{height}">
	      <param name="movie" value="http://www.youtube.com/v/olawhHjfNeI"></param>
	      <param name="wmode" value="transparent"></param>
	      <embed 
	        src="http://www.youtube.com/v/olawhHjfNeI" 
	        type="application/x-shockwave-flash" 
	        wmode="transparent" 
	        width="{width}" 
	        height="{height}" 
					onmouseup='Views.update({videoid})'></embed>
	    </object>
	    EOS
	  youtube_test_title = "Alan Watts - Time";
	  youtube_test_description = "To idolize scriptures is like eating paper currency!\n" +
	    "Take it as a basic premise that if you cannot trust nature and other people, you " +
	    "cannot trust yourself. If you cannot trust yourself, you cannot even trust your " +
	    "mistrust of yourself -- so that without this underlying trust in the whole system " +
	    "of nature you are simply paralyzed.";
	  youtube_test_categories = "People & Blogs";
	  youtube_test_thumbnail_url = "http://img.youtube.com/vi/olawhHjfNeI/default.jpg";
	  youtube_test_width = 425;
	  youtube_test_height = 350;
	  
	  youtube_info = VideoInfo.create(youtube_test_embed);
	  
	  assert_equal(youtube_test_id, youtube_info.id);
		assert_equal(youtube_test_embed, youtube_info.embed);
	  assert_equal(youtube_test_title, youtube_info.title);
	  assert_equal(youtube_test_description, youtube_info.description);
	  assert_equal(youtube_test_categories, youtube_info.categories);
	  assert_equal(youtube_test_thumbnail_url, youtube_info.thumbnail_url);
	  
	  assert_equal(youtube_test_adjembed.gsub(/\n/,' ').gsub(/\s+/,' '), youtube_info.adjembed.gsub(/\n/,' ').gsub(/\s+/,' '));
	  assert_equal(youtube_test_width, youtube_info.width);
	  assert_equal(youtube_test_height, youtube_info.height);
  end
  
  def test_google_info
		google_test_id = -8734914658099018018;
    google_test_embed = <<-EOS
      <embed 
        style="width:400px; 
        height:326px;" 
        id="VideoPlayback" 
        type="application/x-shockwave-flash" 
        src="http://video.google.com/googleplayer.swf?docId=-8734914658099018018&hl=en-CA" flashvars=""></embed>
      EOS
    google_test_adjembed = <<-EOS
      <embed 
        style="width:{width}px; 
        height:{height}px;" 
        id="VideoPlayback" 
        type="application/x-shockwave-flash" 
        src="http://video.google.com/googleplayer.swf?docId=-8734914658099018018&hl=en-CA" flashvars="" 
				onmouseup='Views.update({videoid})'
				wmode="transparent"></embed>
      EOS
    google_test_title = "Alan Watts - Time and the More It Changes";
    google_test_description = "Charismatic philsopher and mystic Alan Watts muses on the meaning "+
      "of time and change, causality and the significance of being driven, and how in reality, the"+
      " past is a result of the present.";
    google_test_categories = "";
    google_test_thumbnail_url = "http://video.google.com/ThumbnailServer2?app=vss&contentid=3f313f8df9060b23&"+
      "offsetms=0&itag=w160&lang=en&sigh=Lh2V9rVMj0Kw5U2G3T86H-WDeq4";
    google_test_width = 400;
    google_test_height = 326;
    
    google_info = VideoInfo.create(google_test_embed);

		assert_equal(google_test_id, google_info.id);
	  assert_equal(google_test_embed, google_info.embed);
	  assert_equal(google_test_title, google_info.title);
	  assert_equal(google_test_description, google_info.description);
	  assert_equal(google_test_categories, google_info.categories);
	  assert_equal(google_test_thumbnail_url, google_info.thumbnail_url);
	  
	  assert_equal(google_test_adjembed.gsub(/\n/,' ').gsub(/\s+/,' '), google_info.adjembed.gsub(/\n/,' ').gsub(/\s+/,' '));
	  assert_equal(google_test_width, google_info.width);
	  assert_equal(google_test_height, google_info.height);
  end
	
	def test_break_info
		break_test_id = "MTY4Mjc2";
    break_test_embed = <<-EOS
      <object width="425" height="350">
        <param name="movie" value="http://embed.break.com/MTY4Mjc2"></param>
        <embed 
          src="http://embed.break.com/MTY4Mjc2" 
          type="application/x-shockwave-flash" 
          width="425" 
          height="350"></embed>
      </object>
      EOS

    break_test_adjembed = <<-EOS
      <object width="{width}" height="{height}">
        <param name="movie" value="http://embed.break.com/MTY4Mjc2"></param>
        <embed 
          src="http://embed.break.com/MTY4Mjc2" 
          type="application/x-shockwave-flash" 
          width="{width}" 
          height="{height}"	
					onmouseup='Views.update({videoid})'
					wmode="transparent"></embed>
				<param name="wmode" value="transparent"/></object>
      EOS
    
    break_test_title = "Beavis and Butthead - BREAK.com";
    break_test_description = "Classic!";
    break_test_categories = "BEAVIS";
    break_test_thumbnail_url = "http://media1.break.com/dnet/media/http://media1.break.com/dnet/media/2006/"+
			"10/168276_82b47baf-1fb2-4301-955c-0c9ff6f71437_prod_Break_Thumb_100_1.jpeg";

    break_test_width = 425;
    break_test_height = 350;

    break_info = VideoInfo.create(break_test_embed);

		assert_equal(break_test_id, break_info.id);
  	assert_equal(break_test_embed, break_info.embed);
  	assert_equal(break_test_title, break_info.title);
  	assert_equal(break_test_description, break_info.description);
  	assert_equal(break_test_categories, break_info.categories);
  	assert_equal(break_test_thumbnail_url, break_info.thumbnail_url);    
  	
	  assert_equal(break_test_adjembed.gsub(/\n/,' ').gsub(/\s+/,' '), break_info.adjembed.gsub(/\n/,' ').gsub(/\s+/,' '));
	  assert_equal(break_test_width, break_info.width);
	  assert_equal(break_test_height, break_info.height);
  end
	
	def test_photobucket_info
		photobucket_test_id = "t133/Dad2JaydenAndCaleb/Videos/Beavis%20and%20Butthead/EatingContest.flv";
    photobucket_test_embed = <<-EOS
        <embed 
          width="430" 
          height="389" 
          type="application/x-shockwave-flash" 
          wmode="transparent" 
          src="http://vid159.photobucket.com/player.swf?file=http://vid159.photobucket.com/albums/t133/Dad2JaydenAndCaleb/Videos/Beavis%20and%20Butthead/EatingContest.flv">
        </embed>
      EOS

    photobucket_test_adjembed = <<-EOS
        <embed 
          width="{width}" 
          height="{height}" 
          type="application/x-shockwave-flash" 
          wmode="transparent" 
          src="http://vid159.photobucket.com/player.swf?file=http://vid159.photobucket.com/albums/t133/Dad2JaydenAndCaleb/Videos/Beavis%20and%20Butthead/EatingContest.flv" 
					onmouseup='Views.update({videoid})'>
        </embed>
      EOS
    
    photobucket_test_title = "Eating Contest video, movie by Dad2JaydenAndCaleb - Photobucket";
    photobucket_test_description = "Photobucket Eating Contest video, this movie was uploaded "+
      "by Dad2JaydenAndCaleb. Browse other Eating Contest videos and movies or upload your own "+
      "with Photobucket's free image and video hosting service.";
    photobucket_test_categories = "Eating Contest video, Eating Contest videos, videos of the "+
      "Eating Contest, videos, movies, Eating Contestvideo, Eating Contestmovie";
    photobucket_test_thumbnail_url = "http://i159.photobucket.com/albums/t133/Dad2JaydenAndCaleb/"+
      "Videos/Beavis%20and%20Butthead/th_EatingContest.jpg";

    photobucket_test_width = 430;
    photobucket_test_height = 389;

    photobucket_info = VideoInfo.create(photobucket_test_embed);

		assert_equal(photobucket_test_id, photobucket_info.id);
    assert_equal(photobucket_test_embed, photobucket_info.embed);
	  assert_equal(photobucket_test_title, photobucket_info.title);
	  assert_equal(photobucket_test_description, photobucket_info.description);
	  assert_equal(photobucket_test_categories, photobucket_info.categories);
	  assert_equal(photobucket_test_thumbnail_url, photobucket_info.thumbnail_url);    
	  
	  assert_equal(photobucket_test_adjembed.gsub(/\n/,' ').gsub(/\s+/,' '), photobucket_info.adjembed.gsub(/\n/,' ').gsub(/\s+/,' '));
	  assert_equal(photobucket_test_width, photobucket_info.width);
	  assert_equal(photobucket_test_height, photobucket_info.height);
  end
	
	
	def test_vimeo_info
		vimeo_test_id = 195729;
    vimeo_test_embed = <<-EOS
        <object 
					type="application/x-shockwave-flash" 
					width="460" 
					height="345" 
					data="http://vimeo.com/moogaloop.swf?clip_id=195729&amp;server=vimeo.com&amp;fullscreen=1">
					<param name="quality" value="best" />
					<param name="allowfullscreen" value="true" />
					<param name="scale" value="showAll" />
					<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=195729&amp;server=vimeo.com&amp;fullscreen=1" />
				</object>
      EOS

    vimeo_test_adjembed = <<-EOS
        <object type="application/x-shockwave-flash" 
					width="{width}" 
					height="{height}" 
					data="http://vimeo.com/moogaloop.swf?clip_id=195729&amp;server=vimeo.com&amp;fullscreen=1" 
					onmouseup='Views.update({videoid})'>
					<param name="quality" value="best" />
					<param name="allowfullscreen" value="true" />
					<param name="scale" value="showAll" />
					<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=195729&amp;server=vimeo.com&amp;fullscreen=1" />
					<param name="wmode" value="transparent"/></object>
      EOS
    
    vimeo_test_title = "The Darwin Awards Trailer";
    vimeo_test_description = "The darwin awards finally have a movie! This is brilliant - stupid people dying in "+
			"stupid ways!<br />\n Excellent";
    vimeo_test_categories = "comedy,darwinawards,movie,new,funny,stupidstrangepeople,trailer";
    vimeo_test_thumbnail_url = "http://storage12.vimeo.com/5/14/34/thumbnail-1434832.jpg";

    vimeo_test_width = 460;
    vimeo_test_height = 345;

    vimeo_info = VideoInfo.create(vimeo_test_embed);

		assert_equal(vimeo_test_id, vimeo_info.id);
    assert_equal(vimeo_test_embed, vimeo_info.embed);
	  assert_equal(vimeo_test_title, vimeo_info.title);

	  assert_equal(vimeo_test_description.gsub(/\s+/,' '), vimeo_info.description.gsub(/\s+/,' '));
	  assert_equal(vimeo_test_categories, vimeo_info.categories);
	  assert_equal(vimeo_test_thumbnail_url, vimeo_info.thumbnail_url);    
	  
	  assert_equal(vimeo_test_adjembed.gsub(/\n/,' ').gsub(/\s+/,' '), vimeo_info.adjembed.gsub(/\n/,' ').gsub(/\s+/,' '));
	  assert_equal(vimeo_test_width, vimeo_info.width);
	  assert_equal(vimeo_test_height, vimeo_info.height);
  end

end