require 'socket'

class PotentialCheckProgress < Storable
	init_storable(:bannerdb, "potentialviews");

end

class AdBlasterPotentialChecker
	def submit_request()
		if (site_module_loaded?(:Worker))
			Worker::PostProcessQueue.queue(AdBlasterPotentialChecker, "check", 
				[self.userid, self.moves], :request_notification => true);
		end
	end
	
	def AdBlasterPotentialChecker.check()
		`cd ../adblaster; java com/nexopia/potentialcheck.sh`
	end
end

class AdBlasterWeb < PageHandler
	declare_handlers("adblaster") {
		page :GetRequest, :Full, :header, remain;
	}
	declare_handlers("adblaster_pages") {
		page :GetRequest, :Full, :run, "run";
		page :GetRequest, :Full, :ad_blast, "ad_blast";
		page :GetRequest, :Full, :check, "check";
		page :GetRequest, :Full, :remove_potential_check, "potential_check", "remove", input(Integer), remain;
		handle :GetRequest, :progress, "progress";
		handle :PostRequest, :send_check, "send_check";
	}

	def header(real_path)
		out = StringIO.new();
		req = subrequest(out, request.method, "/adblaster_pages/#{real_path.join('/')}:Body", request.params.to_hash);
		t = Template.instance("adblaster_web", "header");
		puts t.display();
		puts out.string;
	end
	
	def run()
		t = Template.instance("adblaster_web", "run");
		checks = PotentialCheckProgress.find(:all);
		t.checks = checks;
		str = `ssh root@192.168.0.51 -p 3022 find /data/adblaster/ -type d -maxdepth 1 -name DB_*`
		days = Array.new();
		str.split(/\s/).each{|dir|
			d = dir.gsub(/[^\d]+/, "").to_i;
			now = Time.now;
			if (d <= now.yday)
				y = now.year;
			else
				y = now.year - 1;
			end
			
			days << ["#{Time.utc(y) + d*86400}", dir];
			
		}
		t.days = days;
		puts t.display();
		
	end
	
	def ad_blast()
		s = TCPSocket.new($site.config.adblaster_server, $site.config.adblaster_web_interface);
		s.send("start_adblaster 192.168.0.150:8000\n", 0);
		s.close();
		puts "Success.";
	end
	
	def send_check()
		dirname = params['dir', String];
		id = params['bid', String];
		puts "#{dirname} ::: #{id}";
		s = TCPSocket.new($site.config.adblaster_server, $site.config.adblaster_web_interface);
		s.send("check_potentials #{id} #{dirname}\n", 0);
		s.close();
		site_redirect("/adblaster/check");
	end

	def check()
		t = Template.instance("adblaster_web", "check");
		checks = PotentialCheckProgress.find(:all);
		t.checks = checks;
		puts t.display();
	end
	
	def remove_potential_check(id, remain)
		check = PotentialCheckProgress.find(:first, :conditions => "bannerid = #{id}");
		check.delete();
		site_redirect("/adblaster/index");
	end

	def progress()
		id = params['bid', String];
		checks = PotentialCheckProgress.find(:all, :conditions => "bannerid = #{id}", :order => "bannerid");
		reply.headers['Content-Type'] = PageRequest::MimeType::XML;
		puts "<?xml version = \"1.0\"?>";
		puts "<progress-list>"
		checks.each {|check|
			puts "\t<check>";
			puts "\t\t<bannerid>#{check.bannerid}</bannerid>";
			puts "\t\t<percent>#{check.percentcomplete}</percent>";
			puts "\t\t<estimate>#{check.potentialviews}</estimate>";
			puts "\t</check>"
		}
		puts "</progress-list>";
		
	end
		

end
