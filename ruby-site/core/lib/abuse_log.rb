lib_require :Core, "users/user"
lib_want :user_dump, 'dumpable'

class Abuse < Storable
	init_storable(:db, "abuse")
end

# Properties: id, userid, reportuserid, modid, time, action, reason, subject, msg
class AbuseLog < Storable
	set_db(:moddb);
	set_table("abuselog");
	init_storable();

	if (site_module_loaded?(:UserDump))
		extend(Dumpable)
		def self.user_dump(uid, start=0, finish=Time.now.to_i)
			result = $site.dbs[:moddb].query("SELECT * FROM abuselog WHERE userid = ? AND time BETWEEN ? AND ?", uid, start, finish)

			sortedresults = []
			result.each {|row|
				sortedresults << row
			}

			sortedresults.sort! {|a,b|
				a['time'].to_i <=> b['time'].to_i
			}

			abuse_report = StringIO.new
			sortedresults.each {|row|
				action = ACTIONS[row['action'].to_i][1]
				date_reported = Time.at(row['time'].to_i).strftime("%a %b %d %H:%M:%S %Z %Y")
				reason = REASONS[row['reason'].to_i][1]

				moderator = UserName.find(:first, row['modid'].to_i)
				if moderator.nil?
					moderator = "Unknown User ID ##{row['modid']}"
				else
					moderator = moderator.username
				end
				user_reported = UserName.find(:first, row['userid'].to_i)
				if user_reported.nil?
					user_reported = "Unknown User ID ##{row['userid']}"
				else
					user_reported = user_reported.username
				end
				user_reporter = UserName.find(:first, row['reportuserid'].to_i)
				if user_reporter.nil?
					user_reporter = "Unknown User ID ##{row['reportuserid']}"
				else
					user_reporter = user_reporter.username
				end

				msg = row['msg'].gsub(/(.{1,80})( +|$)\n?|(.{80})/, "\\1\\3\n")

				abuse_report.puts "
Abuse Log Message ID #{row['id']}
User Reported:          #{user_reported} (#{row['userid']})
Reported By:            #{user_reporter} (#{row['reportuserid']})
Date Reported:          #{date_reported}
Administrator Assigned: #{moderator} (#{row['modid']})
Action:                 #{action}
Reason:                 #{reason}
Subject:                #{row['subject']}

#{msg}
"			}
			return Dumpable.str_to_file("#{uid}-abuse_log.txt", abuse_report.string)
		end
	end

	ABUSE_ACTION_WARNING = 1;
	ABUSE_ACTION_FORUM_BAN = 2;
	ABUSE_ACTION_DELETE_PIC = 3;
	ABUSE_ACTION_PROFILE_EDIT = 4;
	ABUSE_ACTION_FREEZE_ACCOUNT = 5;
	ABUSE_ACTION_DELETE_ACCOUNT = 6;
	ABUSE_ACTION_NOTE = 7;
	ABUSE_ACTION_SIG_EDIT = 8;
	ABUSE_ACTION_IP_BAN = 9;
	ABUSE_ACTION_EMAIL_BAN = 10;
	ABUSE_ACTION_UNFREEZE_ACCOUNT = 11;
	ABUSE_ACTION_USER_REPORT = 12;
	ABUSE_ACTION_FORUM_WARNING = 13;
	ABUSE_ACTION_FORUM_NOTE = 14;
	ABUSE_ACTION_LOGGED_MSG = 15;
	ABUSE_ACTION_BLOG_EDIT = 16;
	ABUSE_ACTION_TAGLINE_EDIT = 17;
	ABUSE_ACTION_PICMOD_WARNING = 18;
	ABUSE_ACTION_EDIT_GALLERY = 19;
	ABUSE_ACTION_EDIT_COMMENTS = 20;
	ABUSE_ACTION_EDIT_GALLERY_COMMENTS = 21;
	ABUSE_ACTION_EDIT_BLOG_COMMENTS = 22;


	ABUSE_REASON_NUDITY = 1;
	ABUSE_REASON_RACISM = 2;
	ABUSE_REASON_VIOLENCE = 3;
	ABUSE_REASON_SPAMMING = 4;
	ABUSE_REASON_FLAMING = 5; # harassment
	ABUSE_REASON_PEDOFILE = 6;
	ABUSE_REASON_FAKE = 7;
	ABUSE_REASON_OTHER = 8;
	ABUSE_REASON_UNDERAGE = 9;
	ABUSE_REASON_ADVERT = 10;
	ABUSE_REASON_NOT_USER = 11;
	ABUSE_REASON_DRUGS = 12;
	ABUSE_REASON_BLOG = 13;
	ABUSE_REASON_CREDIT = 14;
	ABUSE_REASON_DISCRIM = 15; # discrimination
	ABUSE_REASON_WEAPONS = 16;
	ABUSE_REASON_THREATS = 17;
	ABUSE_REASON_HACKED = 18;
	ABUSE_REASON_REQUEST = 19; # by request of the user.

	
	ACTIONS = [
		[ABUSE_ACTION_WARNING, 'Official Warning'],
		[ABUSE_ACTION_LOGGED_MSG, 'Logged Message'],
		[ABUSE_ACTION_DELETE_PIC, 'Delete Picture'],
		[ABUSE_ACTION_PROFILE_EDIT, 'Profile Edit'],
		[ABUSE_ACTION_SIG_EDIT, 'Signature Edit'],
		[ABUSE_ACTION_FREEZE_ACCOUNT, 'Freeze Account'],
		[ABUSE_ACTION_UNFREEZE_ACCOUNT, 'Unfreeze Account'],
		[ABUSE_ACTION_DELETE_ACCOUNT, 'Delete Account'],
		[ABUSE_ACTION_IP_BAN, 'IP Ban'],
		[ABUSE_ACTION_EMAIL_BAN, 'Email Ban'],
		[ABUSE_ACTION_NOTE, 'Note'],
		[ABUSE_ACTION_USER_REPORT, 'User Report'],
		[ABUSE_ACTION_BLOG_EDIT, 'Blog Edit'],
		[ABUSE_ACTION_TAGLINE_EDIT, 'Tagline Edit'],
		[ABUSE_ACTION_FORUM_BAN, 'Forum Ban'],
		[ABUSE_ACTION_FORUM_WARNING, 'Forum Warning'],
		[ABUSE_ACTION_FORUM_NOTE, 'Forum Note'],
		[ABUSE_ACTION_PICMOD_WARNING, 'Pic Mod Warning'],
		[ABUSE_ACTION_EDIT_GALLERY, 'Edit Gallery'],
		[ABUSE_ACTION_EDIT_COMMENTS, 'Edit Comments'],
		[ABUSE_ACTION_EDIT_GALLERY_COMMENTS, 'Edit Gallery Comments']

	];


	REASONS = [
		[ABUSE_REASON_NUDITY, 'Nudity/Porn'],
		[ABUSE_REASON_RACISM, 'Racism'],
		[ABUSE_REASON_DISCRIM, 'Discrimination'],
		[ABUSE_REASON_VIOLENCE, 'Gore/Violence'],
		[ABUSE_REASON_WEAPONS, 'Weapons'],
		[ABUSE_REASON_DRUGS, 'Drugs'],
		[ABUSE_REASON_SPAMMING, 'Spamming'],
		[ABUSE_REASON_FLAMING, 'Harassment'],
		[ABUSE_REASON_THREATS, 'Threats'],
		[ABUSE_REASON_PEDOFILE, 'Pedophile'],
		[ABUSE_REASON_UNDERAGE, 'Underage'],
		[ABUSE_REASON_FAKE, 'Fake'],
		[ABUSE_REASON_ADVERT, 'Advertising'],
		[ABUSE_REASON_NOT_USER, 'User not in Picture'],
		[ABUSE_REASON_BLOG, 'Blog'],
		[ABUSE_REASON_CREDIT, 'Credit Card'],
		[ABUSE_REASON_HACKED, 'Hacked'],
		[ABUSE_REASON_REQUEST, 'User Request'],
		[ABUSE_REASON_OTHER, 'Other']
	];
	
	
	# Enter an abuse_log_id if you want to update an existing AbuseLog object
	def AbuseLog.make_entry(modid, userid, action, reason, subject, msg, abuse_log_id=nil, reportuserid=0)
		abuse_log = AbuseLog.find(:first, abuse_log_id) || AbuseLog.new;
		abuse_log.userid = userid;
		abuse_log.reportuserid = reportuserid;
		abuse_log.modid = modid;
		abuse_log.time = Time.now.to_i;
		abuse_log.action = action;
		abuse_log.reason = reason;
		abuse_log.subject = subject;
		abuse_log.msg = msg;
		
		abuse_log.store;
		
		# only up the abuse count for completely new abuse log entries
		if (abuse_log_id.nil?)			
			user = User.get_by_id(userid);			
			user.abuses = user.abuses + 1;

			user.store;
		end
	end
end
