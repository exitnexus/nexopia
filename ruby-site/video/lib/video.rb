lib_require :Core, 'storable/storable'
lib_require :Video, 'video_comment'
lib_require :Video, 'video_report'

require 'uri';


class Video < Storable

	attr_reader :reports;

	init_storable(:videodb, 'video');

	# The amount of time to wait since the last update of the recent views property before resetting it back
	# to zero. If recenttime + RECENT_TIME_BLOCK < Time.now.to_i, then we set recentviews to 0. Otherwise, we
	# increment Video#recentviews by 1. Video#recentviews is used when sorting by "popularity".
	#
	# The current value resets recentviews every week.
	RECENT_TIME_BLOCK = 60 * 60 * 24 * 7;


	def Video.find_reported(page, limit_per_page)
		videos = Video.find(:total_rows, :limit => limit_per_page, :page => page, :conditions => ["ban = 'n' AND reported = 'y'"]);

		return videos;
	end


	def Video.find_banned(page, limit_per_page)
		videos = Video.find(:total_rows, :limit => limit_per_page, :page => page, :conditions => ["ban = 'y'"]);

		return videos;
	end


	def nex_embed(new_width=width)
		# check for a zero-width video
		ratio = height.to_f / width.to_f;
		new_height = (new_width * ratio).to_i;

		new_embed = adjembed.dup;
		new_embed.gsub!("{width}", "#{new_width}");
		new_embed.gsub!("{height}", "#{new_height}");
		new_embed.gsub!("{videoid}","#{id}");

		return new_embed;
	end


	def comments
		comments = VideoComment.find(:all, :conditions => ["videoid = ?", id]);

		return comments;
	end


	def after_load()
		@reports	= VideoReport.find(:all, :promise, :conditions => ["videoid = ?", id]) || Array.new;
	end


	def after_create()
		@reports	= VideoReport.find(:all, :promise, :conditions => ["videoid = ?", id]) || Array.new;
	end


	def share_subject()
		subject_line = "Video: #{title}";
		return subject_line;
	end


	def share_body()
		body_text = <<-EOS
Check out this video:

#{embed}
EOS

		return body_text;
	end


	def repreason
		reason = "";

		if (!reports.empty?)
			reports.each { |report|
				reported_by = report.username || "Anonymous";
				reason += "<b>#{reported_by}:</b><br/>";
				reason += "#{report.reason}<br/><br/>";
			};
		end

		return reason;
	end


	def display_addtime()
		time = Time.at(addtime);
		display_time = time.strftime("%B %d, %Y");

		return display_time;
	end


	def hit()
		self.views = self.views + 1;
		current_time = Time.now.to_i;

		# Check to see if the RECENT_TIME_BLOCK has passed.
		if (self.recenttime + RECENT_TIME_BLOCK < current_time)
			self.recenttime = current_time; # Reset the recenttime to the current time.
			self.recentviews = 1;						# Reset recentviews, but count this view.
		else
			self.recentviews = self.recentviews + 1;
		end
	end
end
