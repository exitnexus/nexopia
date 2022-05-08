require "socket"
lib_require :Devutils, 'quiz'

class Array
	def implode(str)
		output = "";
		each{|s|
			output+=s.to_s+str;
		}
		return output[0...-(str.length)];
	end
end

class Banner < Storable
	init_storable(:testbanner, "banners");

	def Banner.get(conditions)
		list = Banner.find_all({});
		if (conditions["sex"])
			list.each(){|b|
				puts "Sex: " + b.sex.to_s;
				if (b.sex == "1")
					return b;
				end
			}
		end
		return nil;
	end

end

class TestBannerserve < Quiz

	class_attr :time, true;

	# create a bannerview.  Bannerview will be created so that any specified
	# attribute will be keyed to exactly one banner.  Thus there must be at least
	# one banner for which each attribute is unique.
	def bannerview2(conditions)
		b = Banner.get({"sex" => true});
		puts b.sex;
	end


	# create a bannerview.  Bannerview will be created so that any specified
	# attribute will be keyed to exactly one banner.  Thus there must be at least
	# one banner for which each attribute is unique.
	def bannerview(age, sex, loc, interests, pageid=-1)
		TestBannerserve.time += 1;
		return "get " + TestBannerserve.time.to_s + " 1 1 " + age.to_s + " " + sex.to_s + " " + loc.to_s + " " + interests.implode(",") + " bannerview 0 1 #{pageid}\n";
	end

	def setup

		$site.dbs[:testbanner].query("TRUNCATE TABLE banners;");
		$site.dbs[:testbanner].query("TRUNCATE TABLE bannercampaigns;");
		$site.dbs[:testbanner].query("TRUNCATE TABLE bannerstats");
		$site.dbs[:testbanner].query("TRUNCATE TABLE bannertypestats");

		TestBannerserve.time = 0;
		begin
			@s = TCPSocket.new("192.168.10.236",8435)
			@connected = true
		rescue Exception
			@connected = false
		end
		reset();
	end

	def teardown
		sleep(0.02);
		begin
			@s.close();
		rescue Exception
		end
	end

	def reset
		sleep(0.02);
		begin
			@s.send("reset\n ", 0);
			@s.close();
			@s = TCPSocket.new("192.168.10.236",8435)
			@connected = true
		rescue Exception
			@connected = false
		end
	end

=begin
=end
	def test_get
		return unless @connected
		banners_sql = %q{
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (2, 1, 1, 4, 3, 107389875, 0, 0, 0, 0, 0, 0, 1, 20, 'y', 0, 0, 30, 0, '99', '1', '1', '', '1', '', 'unchecked', 'y', '247 468', '', '', '<SCRIPT></script>', 1143670141, 1158177307, 30, 96586959, 1, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (4, 6, 1, 4, 12, 91474296, 0, 0, 0, 0, 0, 0, 4, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '2', '', 'unchecked', 'y', 'Google 468', '', '', '<script type="text/javascript"></script>', 1135785018, 1158177307, 30, 0, 2, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 2, 1, 4, 6, 72545611, 0, 0, 0, 0, 0, 0, 1, 20, 'y', 0, 0, 10, 0, '', '', '', '', '3', '', 'approved', 'y', 'Fastclick 468', '', '', '<CODE></CODE>', 1155057182, 1158177307, 25, 114434072, 1, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (7, 4, 1, 4, 6, 0, 0, 0, 0, 2, 0, 0, 0, 3600, 'y', 0, 0, 0, 0, '', '', '', '', '4', '', 'unchecked', 'y', 'eType 468', '', '', '<SCRIPT></SCRIPT>', 1114134140, 1158177307, 30, 0, 2, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (10, 8, 1, 2, 2, 0, 2, 0, 0, 0, 0, 0, 0, 86400, 'n', 0, 0, 0, 0, '', '', '', '', '5', '', 'unchecked', 'y', 'Rayacom', '', '', 'http://nexopiaads.laslo.ca/rayacom.swf', 1116477818, 1158110821, 30, 0, 3, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (11, 8, 1, 2, 4, 0, 2, 0, 0, 0, 0, 0, 0, 86400, 'n', 0, 0, 0, 0, '', '', '', '', '5', '', 'unchecked', 'y', 'Rayacom', '', '', 'http://nexopiaads.laslo.ca/rayacom.swf', 1116477818, 1158110821, 30, 0, 4, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (12, 8, 1, 2, 1, 0, 2, 0, 0, 0, 0, 0, 0, 86400, 'n', 0, 0, 0, 0, '', '', '', '', '5', '', 'unchecked', 'y', 'Rayacom', '', '', 'http://nexopiaads.laslo.ca/rayacom.swf', 1116477818, 1158110821, 30, 0, 5, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (13, 8, 1, 2, 2, 0, 2, 0, 0, 0, 0, 0, 0, 86400, 'n', 0, 0, 0, 0, '', '', '', '', '5', '', 'unchecked', 'y', 'Rayacom', '', '', 'http://nexopiaads.laslo.ca/rayacom.swf', 1116477818, 1158110821, 30, 0, 6, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (15, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 86400, 'y', 0, 0, -1, 0, '', '', '', '', '10', '', 'unchecked', 'y', '', '', '', '', 0, 1158110821, -1, 0, 8, 0);
INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (14, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86400, 'y', 0, 0, -1, 0, '', '', '', '', '11', '', 'unchecked', 'y', '', '', '', '', 0, 1158110821, -1, 0, 7, 0);
		};

		campaigns_sql = %q{
INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (1, 1, 0, 0, 0, 0, 3600, 0, 0, 'y', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);
INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 6, 0, 0, 0, 0, 86400, 0, 0, 'y', 0, 0, 5, 0, 'Google 468', 1135785018, '', '', '', '', '', '', 'y', 30, 0);
INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (3, 1, 0, 0, 0, 0, 20, 0, 1, 'y', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '6', '', 'y', 30, 0);
INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (4, 6, 0, 0, 0, 0, 86400, 0, 4, 'y', 0, 0, 5, 0, 'Google 468', 1135785018, '', '', '', '', '7', '', 'y', 30, 0);
INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (5, 1, 0, 0, 0, 0, 3600, 0, 0, 'y', 0, 0, 30, 0, '247 468', 1143670141, '99', '1', '1', '', '8', '', 'y', 30, 0);
INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (6, 6, 0, 0, 2, 0, 86400, 0, 0, 'y', 0, 0, 5, 0, 'Google 468', 1135785018, '', '', '', '', '9', '', 'y', 30, 0);
INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (7, 0, 1, 0, 0, 0, 86400, 0, 0, 'y', 0, 0, 0, 0, '', 0, '', '', '', '', '', '', 'y', 30, 0);
INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (8, 0, 0, 0, 0, 0, 86400, 0, 0, 'y', 0, 0, 0, 0, '', 0, '', '', '', '', '', '', 'y', 30, 0);
		};


		campaigns = campaigns_sql.split(";\n");
		campaigns.pop();
		banners = banners_sql.split(";\n");
		banners.pop();
		campaigns.each{|sql|
			$site.dbs[:testbanner].query(sql);
		}
		banners.each{|sql|
			$site.dbs[:testbanner].query(sql);
		}

		reset();

		@tests = [
		############BANNERS###############
		#invalid interests
		[bannerview(15, 2, 381, [1]), 0],
		#limit by period
		[bannerview(17, 1, 1, [1,3]), 8],
		[bannerview(17, 1, 1, [1,3]), 0],
		[begin TestBannerserve.time += 20000; end, -1],
		[bannerview(17, 1, 1, [1,3]), 8],
		[bannerview(17, 1, 1, [1,3]), 0],
		#number of views per day
		[bannerview(15, 2, 381, [2]), 4],
		[bannerview(15, 2, 381, [2]), 4],
		[bannerview(15, 2, 381, [2]), 4],
		[bannerview(15, 2, 381, [2]), 4],
		[bannerview(15, 2, 381, [2]), 0],
		[bannerview(14, 2, 381, [2]), 0],
		[bannerview(14, 2, 381, [2]), 0],
		#age, sex, location
		[bannerview(99, 1, 2, [1]), 0],
		[bannerview(99, 2, 1, [1]), 0],
		[bannerview(98, 1, 1, [1]), 0],
		[bannerview(99, 1, 1, [1]), 2],
		#max daily views
		[bannerview(16, 1, 1, [4]), 7],
		[bannerview(16, 1, 1, [4]), 7],
		[bannerview(16, 1, 1, [4]), 0],
		[bannerview(16, 1, 1, [4]), 0],
		#max total views
		[bannerview(16, 1, 1, [10]), 15],
		[bannerview(16, 1, 1, [10]), 0],
		############Campaigns###############
		#invalid interests
		[bannerview(15, 2, 381, [5]), 0],
		#limit by period
		[bannerview(17, 1, 1, [5,6]), 10],
		[bannerview(17, 1, 1, [5,6]), 0],
		[begin TestBannerserve.time += 20000; end, -1],
		[bannerview(17, 1, 1, [5,6]), 10],
		[bannerview(17, 1, 1, [5,6]), 0],
		#number of views per day
		[bannerview(15, 2, 381, [5,7]), 11],
		[bannerview(15, 2, 381, [5,7]), 11],
		[bannerview(15, 2, 381, [5,7]), 11],
		[bannerview(15, 2, 381, [5,7]), 11],
		[bannerview(15, 2, 381, [5,7]), 0],
		[bannerview(14, 2, 381, [5,7]), 0],
		[bannerview(14, 2, 381, [5,7]), 0],
		#age, sex, location
		[bannerview(99, 1, 2, [5,8]), 0],
		[bannerview(99, 2, 1, [5,8]), 0],
		[bannerview(98, 1, 1, [5,8]), 0],
		[bannerview(99, 1, 1, [5,8]), 12],
		#maxviews
		[bannerview(16, 1, 1, [5,9]), 13],
		[bannerview(16, 1, 1, [5,9]), 13],
		[bannerview(16, 1, 1, [5,9]), 0],
		[bannerview(16, 1, 1, [5,9]), 0],
		#max total views
		[bannerview(16, 1, 1, [11]), 14],
		[bannerview(16, 1, 1, [11]), 0]
		];

		@tests.each{|test|
			set, result = test;
			if (result != -1)
				set.each_line(){|str|
					t = @s.send(str,0);
					sleep(0.02);
					data = @s.recvfrom( 1024 );
					#puts str;
					#print data.to_s + "<br>";
					assert_equal(result.to_s.strip, data.to_s.split("-")[0].strip, "Expected #{result.to_s.strip}, got #{data.to_s.split("-")[0].strip}");
				}
			else
				#set.call;
			end
		}
	end
	def test_stats
		return unless @connected
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 1, 0, 0, 0, 0, 86400, 0, 0, 'n', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");

		reset
		@s.send(bannerview(20, 1, 1, [2]),0);
		@s.send(bannerview(20, 1, 1, [2]),0);
		@s.send(bannerview(20, 1, 1, [2]),0);
		@s.send(bannerview(20, 1, 1, [2]),0);

		@s.send("minutely\n",0);
		sleep(0.05); #we need to sleep here because minutely is asynchronous and hourly depends on it being done
		@s.send("hourly\n",0);
		@s.send("daily\n",0);

		sleep(0.5); #give the query thread a chance to finish before we check the database

		result = $site.dbs[:testbanner].query("SELECT * FROM banners");
		assert(result.num_rows == 1);
		hash = result.fetch;
		assert(hash['views'].to_i == 4);
		assert(hash['id'].to_i == 8);
		assert(hash['clicks'].to_i == 0);

		result = $site.dbs[:testbanner].query("SELECT * FROM bannerstats");
		assert(result.num_rows == 1);
		hash = result.fetch;
		assert(hash['views'].to_i == 4);
		assert(hash['bannerid'].to_i == 8);
		assert(hash['clicks'].to_i == 0);

		result = $site.dbs[:testbanner].query("SELECT * FROM bannertypestats");
		assert(result.num_rows == 8);
		result = $site.dbs[:testbanner].query("SELECT * FROM bannertypestats WHERE size = 1");
		assert(result.num_rows == 1);
		hash = result.fetch;
		assert(hash['clicks'].to_i == 0);
		assert(hash['views'].to_i == 4);

		#gzip_reader = Zlib::GzipReader.new(StringIO.new(hash['viewsdump']));
		#assert_equal("<typestat>", (gzip_reader.read)[0,10]);
		#gzip_reader = Zlib::GzipReader.new(StringIO.new(hash['clicksdump']));
		#assert_equal("<typestat>", (gzip_reader.read)[0,10]);
	end

	def test_start_end
		return unless @connected
		current_time = Time.now.to_i;
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 400, 86400, 'n', #{current_time-100000}, #{current_time+100000}, 5, 0, '', '', '', '', '', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 1, 0, 0, 0, 0, 86400, 0, 0, 'n', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");
		reset
		TestBannerserve.time = current_time;
		@s.send(bannerview(20, 1, 1, [2]),0);
		data = @s.recvfrom( 1024 );
		assert_equal("8", data.to_s.split("-")[0].strip);

		$site.dbs[:testbanner].query("TRUNCATE TABLE banners;");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 400, 86400, 'n', 1, 1000, 5, 0, '', '', '', '', '', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		reset
		@s.send(bannerview(20, 1, 1, [2]),0);
		data = @s.recvfrom( 1024 );
		assert_equal("0", data.to_s.split("-")[0].strip);


		$site.dbs[:testbanner].query("TRUNCATE TABLE banners;");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 400, 86400, 'n', #{current_time-100000}, #{current_time-50000}, 5, 0, '', '', '', '', '', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		reset
		@s.send(bannerview(20, 1, 1, [2]),0);
		data = @s.recvfrom( 1024 );
		assert_equal("0", data.to_s.split("-")[0].strip);

		#campaigns
		$site.dbs[:testbanner].query("TRUNCATE TABLE banners;");
		$site.dbs[:testbanner].query("TRUNCATE TABLE bannercampaigns;");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 1, 0, 0, 0, 0, 86400, 0, 0, 'n', #{current_time-90000}, #{current_time+90000}, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");
		reset
		TestBannerserve.time = current_time;
		@s.send(bannerview(20, 1, 1, [2]),0);
		data = @s.recvfrom( 1024 );
		assert_equal("8", data.to_s.split("-")[0].strip);

		$site.dbs[:testbanner].query("TRUNCATE TABLE bannercampaigns;");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 1, 0, 0, 0, 0, 86400, 0, 0, 'n', 1, 100, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");
		reset
		@s.send(bannerview(20, 1, 1, [2]),0);
		data = @s.recvfrom( 1024 );
		assert_equal("0", data.to_s.split("-")[0].strip);


		$site.dbs[:testbanner].query("TRUNCATE TABLE bannercampaigns;");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 1, 0, 0, 0, 0, 86400, 0, 0, 'n', #{current_time+900000}, #{current_time+950000}, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");
		reset
		@s.send(bannerview(20, 1, 1, [2]),0);
		data = @s.recvfrom( 1024 );
		assert_equal("0", data.to_s.split("-")[0].strip);

	end

	#There are no asserts for this test, to ensure it passes check the bannerDebug output and make sure it complains once
	#and only once about an invalid passback.
	def test_passback
		return unless @connected
		print "<table width=\"100%\" style=\"background-color:FFFFCC\"><tr><td><strong>Warning:</strong> If you are attempting to test passbacks you will need to examine the bannerDebug output.  It should have one and only one error regarding Invalid Passback: 9.</td></tr></table>";
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 1, 0, 0, 0, 0, 86400, 0, 0, 'n', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");
		reset
		@s.send(bannerview(20, 1, 1, [2]),0);
		@s.send("get " + TestBannerserve.time.to_s + " 1 1 20 1 1 2 bannerview 8 0 -1\n",0);
		@s.send("get " + TestBannerserve.time.to_s + " 1 1 20 1 1 2 bannerview 9 0 -1\n",0);
	end

	def test_clicks
		return unless @connected
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (7, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 1, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '1,2,3,4,5,6,7,8', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 1, 0, 3, 0, 0, 86400, 0, 0, 'n', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (1, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '1,2,3,4,5,6', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 1, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (1, 1, 0, 0, 0, 0, 86400, 1, 0, 'n', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");
		sleep(1);
		reset

		@s.send(bannerview(20,1,1,[8]),0);
		data = @s.recvfrom(1024);
		assert_equal("7", data.to_s.split("-")[0].strip);
		@s.send("click 7\n", 0);
		data = @s.recvfrom(1024);
		@s.send(bannerview(20,1,1,[8]),0);
		data = @s.recvfrom(1024);
		assert_equal("0", data.to_s.split("-")[0].strip);

		@s.send(bannerview(20,1,1,[6]),0);
		data = @s.recvfrom(1024);
		assert_equal("1", data.to_s.split("-")[0].strip);
		@s.send("click 1\n", 0);
		data = @s.recvfrom(1024);
		@s.send(bannerview(20,1,1,[6]),0);
		data = @s.recvfrom(1024);
		assert_equal("0", data.to_s.split("-")[0].strip);
	end

	def test_timetable
		return unless @connected
		days = ['S','M','T','W','R','F','Y'];
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (7, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 1, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '4', 'M-S', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 1, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '3', '#{days[Time.now.wday]}', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (9, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 1, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '2', '#{days[(Time.now.wday+1)%7]}', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (10, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 1, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '1', '#{days[(Time.now.wday)]}0-23', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (11, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 1, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '5', '#{Time.now.hour}', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (12, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 1, 400, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '6', '#{days[(Time.now.wday)]}#{Time.now.hour+1}', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`) VALUES (2, 1, 0, 3, 0, 0, 86400, 0, 0, 'n', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0);");
		reset

		TestBannerserve.time = Time.now.to_i;
		@s.send(bannerview(20,1,1,[4]),0);
		data = @s.recvfrom(1024);
		assert_equal("7", data.to_s.split("-")[0].strip);

		@s.send(bannerview(20,1,1,[3]),0);
		data = @s.recvfrom(1024);
		assert_equal("8", data.to_s.split("-")[0].strip);

		@s.send(bannerview(20,1,1,[2]),0);
		data = @s.recvfrom(1024);
		assert_equal("0", data.to_s.split("-")[0].strip);

		@s.send(bannerview(20,1,1,[1]),0);
		data = @s.recvfrom(1024);
		assert_equal("10", data.to_s.split("-")[0].strip);

		@s.send(bannerview(20,1,1,[5]),0);
		data = @s.recvfrom(1024);
		assert_equal("11", data.to_s.split("-")[0].strip);

		@s.send(bannerview(20,1,1,[6]),0);
		data = @s.recvfrom(1024);
		assert_equal("0", data.to_s.split("-")[0].strip);

	end

	def test_pagedominance
		return unless @connected
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (7, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 2, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `banners` (`id`, `clientid`, `bannersize`, `bannertype`, `views`, `potentialviews`, `clicks`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `clicksperday`, `viewsperuser`, `limitbyperiod`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `moded`, `enabled`, `title`, `image`, `link`, `alt`, `dateadded`, `lastupdatetime`, `refresh`, `passbacks`, `campaignid`, `credits`) VALUES (8, 1, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86400, 'n', 0, 0, 5, 0, '', '', '', '', '', '', 'unchecked', 'y', 'Google 468', '', '', 'content stuff here', 1135785018, 1158177307, 30, 0, 1, 0)");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`, `pagedominance`) VALUES (1, 1, 0, 3, 0, 0, 86400, 0, 0, 'n', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0, 'y');");
		$site.dbs[:testbanner].query("INSERT INTO `bannercampaigns` (`id`, `clientid`, `maxviews`, `maxclicks`, `viewsperday`, `minviewsperday`, `limitbyperiod`, `clicksperday`, `viewsperuser`, `limitbyhour`, `startdate`, `enddate`, `payrate`, `paytype`, `title`, `dateadded`, `age`, `sex`, `loc`, `page`, `interests`, `allowedtimes`, `enabled`, `refresh`, `credits`, `pagedominance`) VALUES (2, 1, 0, 3, 0, 0, 86400, 0, 0, 'n', 0, 0, 30, 0, '247 468', 1143670141, '', '', '', '', '', '', 'y', 30, 0, 'n');");
		reset;
		(0..9).each {|i|
			@s.send(bannerview(20,1,1,[6], i),0);
			data = @s.recvfrom(1024);
			correct_banner = data.to_s.split("-")[0].strip;
			(0..9).each {|j|
				@s.send(bannerview(20,1,1,[6], i),0);
				data = @s.recvfrom(1024);
				assert_equal(correct_banner, data.to_s.split("-")[0].strip);
			}
		}
	end
end
