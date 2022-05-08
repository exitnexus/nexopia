lib_require :Core, 'template/template';
lib_require :Video, 'video';

module Vid

	class Report < PageHandler

		def initialize(*args)
			super(*args);
			@dump = StringIO.new;
		end


		declare_handlers("videos") {
			# Public Level Handlers
			area :Public
		
			# Reporting naughty videos (input is the id of the video being reported)
			page :GetRequest, :Full, :enter_report, "report", "enter", input(Integer)
			page :PostRequest, :Full, :send_report, "report", "send"
		}


		def enter_report(videoid)
			request.reply.headers['X-width'] = 0;
			
			t = Template::instance('video', 'report_enter');
			
			video = Video.find(:first, videoid);
			t.video = video;
			
			if (!session.anonymous?())
				userid = session.user.userid;
			else
				userid = "";
			end
			
			t.repuserid = userid;
			t.handler_root = "/videos";
			
			parameters = VideoParameters.new(params,videoid);
			t.back_link = "/videos" + parameters.to_url;

			puts t.display();
		end
		

		def send_report()
			request.reply.headers['X-width'] = 0;
			
			reason = params['reason', String];
			repuserid = params['repuserid', Integer];
			videoid = params['videoid', Integer];

			report = VideoReport.new;
			report.videoid = videoid;
			report.repuserid = repuserid;
			report.reason = reason;
			report.time = Time.now.to_i();
			report.store();
			
			video = Video.find(:first, videoid);
			video.reported = true;
			video.store();

			t = Template::instance('video', 'report_confirm');
			t.video = video;
			t.report = report;
			t.handler_root = "/videos";

			t.back_link = params['back_link', String];

			puts t.display();
		end

	end
end