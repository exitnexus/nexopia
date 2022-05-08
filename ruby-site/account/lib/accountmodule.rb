lib_want :Gallery, "gallery_folder", "gallery_helper"
lib_require :Userpics, "userpics_helper"

class AccountModule < SiteModuleBase
	class << self
		def upload_handle(file, userid, params, original_filename)
			# We'll want to create a new gallery for the user to store his/her first picture in.
			gallery = Gallery::GalleryFolder.new();
			gallery.ownerid = userid;
			gallery.id = Gallery::GalleryFolder.get_seq_id(userid)
			gallery.name = "My First Gallery"
			gallery.description = "";
			gallery.permission = "anyone";
			gallery.store
			
			gallery_pic = Gallery::GalleryHelper.store_pic(file, userid, gallery.id, "");

			# gallery_pic = Gallery::Pic.find(:first, userid, gallery_pic_id);
			
			Userpics::UserpicsHelper.set_as_userpic(userid, gallery_pic);
		end
	end

	WARN_ADMIN_COUNT 	= 10
	WARN_ADMIN_PERIOD = 60 * 60 * 24 * 30
	
	def self.warn_admins(ip, ip_as_int)
		spam_alert_sent = $site.memcache.get("spam-alert-sent-#{ip_as_int}")
		return if !spam_alert_sent.nil?
		
		days = WARN_ADMIN_PERIOD / (60 * 60 * 24)
		month_ago = Time.now.to_i - WARN_ADMIN_PERIOD
		users_at_ip = User.find(:all, 
														:conditions => ["ip = ? AND jointime > ?", ip_as_int, month_ago], 
														:order => "jointime DESC", 
														:limit => (WARN_ADMIN_COUNT + 1))
		if(users_at_ip.length > WARN_ADMIN_COUNT)
			if (site_module_loaded?(:Orwell))
				to = $site.config.webmaster_email
				subject = "SPAM ALERT ON IP: #{ip}"
				body_plain = "More than #{WARN_ADMIN_COUNT} accounts were created on #{$site.www_url} " +
					"in the last #{days} days from #{ip}.\n\n" +
					"These are the last #{WARN_ADMIN_COUNT} users that created accounts on this IP:\n\n"
				
				count = 0
				users_at_ip.each { |user|
					break if count >= WARN_ADMIN_COUNT
					count = count + 1
					body_plain = body_plain + user.username + " (userid: #{user.userid})\n"
				}
				Orwell::send_email(to, subject, body_plain)
				$site.memcache.set("spam-alert-sent-#{ip_as_int}", Time.now.to_i, 60 * 60 * 24)
			end
		end
	end
	register_task AccountModule, :warn_admins
end