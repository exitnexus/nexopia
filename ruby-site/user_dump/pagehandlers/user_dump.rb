lib_require :UserDump, "abuse_action", "abuse_reason", "abuse_report_file", "archive_type", "date_timestamp"
require 'net/http'
require 'net/smtp'
require 'rmail'
require 'uri'
require 'zip/zipfilesystem'

class UserDump < PageHandler
	declare_handlers("user_dump") {
		area :Admin
		access_level :Admin, UserDumpModule, :userdump
#		access_level :Any

		page :GetRequest, :Full, :user_dump_form
		handle :PostRequest, :user_dump, "submit"
	}

	def alert_email(user_id, start_date, end_date, requested_functions)
		user_name = User.get_by_id(user_id.to_i)
		if user_name.nil?
			user_name = "Unknown (#{user_id})"
		else
			user_name = user_name.username
		end
		start_date = start_date.strftime("%a %b %d %H:%M:%S %Z %Y")
		end_date = end_date.strftime("%a %b %d %H:%M:%S %Z %Y")

		message = RMail::Message.new
		message.body = File.open("user_dump/templates/alert_email.txt", "r").gets(nil)
		message.body.gsub!(/\{user_id\}/, user_id.to_s)
		message.body.gsub!(/\{user_name\}/, user_name)
		message.body.gsub!(/\{start_date\}/, start_date)
		message.body.gsub!(/\{end_date\}/, end_date)
		message.body.gsub!(/\{remote_addr\}/, request.headers['REMOTE_ADDR'])
#		message.body.gsub!(/\{remote_user\}/, request.session.user.username)
		message.body.gsub!(/\{requested_functions\}/, requested_functions)

		message.header.from = "#{$site.config.site_name} <user_dump@#{$site.config.email_domain}>"
		message.header.subject = "User Dump Requested: #{user_name} (#{user_id})"
		['hostmaster@nexopia.com', 'angus@nexopia.com'].each {|recipient|
			message.header.to = recipient
			message_text = RMail::Serialize.write("", message) # Make an SMTP compatible string to send

			# Send the e-mail.
			Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
				smtp.send_message(message_text, "user_dump@#{$site.config.email_domain}", recipient)
			}
		}
	end

	def user_dump
		user_id = params['user_id', Integer]
		start_date = params['start_date', Date]
		end_date = params['end_date', Date]

		if ! user_id.kind_of?(Integer) or ! start_date.instance_of?(Date) or ! end_date.instance_of?(Date) or start_date > end_date
			site_redirect(url/:user_dump & params.to_hash)
		end

		start_date = DateTimestamp.jd(start_date.jd())
		end_date = DateTimestamp.jd(end_date.jd() + 1)

		report_files = []
		requested_functions = ""
		params.each{|k|
			if k =~ /^dump_user_/ and params[k, Boolean] and self.respond_to?(k)
				requested_functions += "\t#{k}\n"
				report_files += self.send(k, user_id, start_date, end_date)
			end
		}
		self.alert_email(user_id, start_date, end_date, requested_functions)

		zip_file_path = File.catname("user#{user_id}.zip", $site.config.user_dump_cache)
		Zip::ZipFile.open(zip_file_path, Zip::ZipFile::CREATE) {|zipfile|
			report_files.each {|report|
				path = ""
				File.dirname(report.get_path()).split('/').each {|d|
					next if d == "."
					path += "/" unless path.empty?
					path += d
					if ! zipfile.dir.entries(File.dirname(path)).include?(d)
						zipfile.dir.mkdir(path)
					end
				}
				zipfile.file.open(report.get_path(), "w") {|f|
					f.write(report.get_content())
				}
			}
		}

		begin
			archive_contents = File.open(zip_file_path, "r").gets(nil)
		ensure
			File.delete(zip_file_path) if File.exists?(zip_file_path)
		end

		reply.headers['Content-Type'] = PageRequest::MimeType::ZIP
		reply.headers['Content-Disposition'] = 'attachment; filename="' + File.basename(zip_file_path) + '"'
		puts archive_contents
	end

	def user_dump_form
		t = Template.instance("user_dump", "user_dump_form")

		t.submit_uri = request.headers['REQUEST_URI']
		if t.submit_uri !~ %r!/$!
			t.submit_uri += "/"
		end
		t.submit_uri += "submit"
		if t.submit_uri !~ /:Body/
			t.submit_uri.gsub!(/:Body/, "")
			t.submit_uri.gsub!(%r!//!, "/")
			t.submit_uri += ":Body"
		end

		t.user_id = params['user_id', Integer]

		t.start_date = params['start_date', Date]
		if t.start_date.instance_of?(Date)
			t.start_date = t.start_date.strftime("%d/%m/%Y")
		else
			t.start_date = "DD/MM/YYYY"
		end

		t.end_date = params['end_date', Date]
		if t.end_date.instance_of?(Date)
			t.end_date = t.end_date.strftime("%d/%m/%Y")
		else
			t.end_date = "DD/MM/YYYY"
		end

		params.each{|k|
			if k =~ /^dump_user_/ and params[k, Boolean]
				t.send(:"#{k}=", "true")
			end
		}

		print t.display()
	end

	def dump_archive(archive_type, user_id, start_date, end_date)

		archive_type_description = ArchiveType.description(archive_type)
		archive_reports = []

		i = start_date - (start_date.mday() - 1)
		while (i <= end_date)
			archive_table = i.strftime("archive%Y%m")
			archive_report = AbuseReportFile.new(archive_type_description + " Archive/" + i.strftime("%Y-%m.txt"))
			i >>= 1

			result = $site.dbs[:usersdb].squery(user_id, "SHOW TABLES LIKE '#{archive_table}'")
			next if result.num_rows == 0
			result = $site.dbs[:usersdb].query("SELECT id, time, ip, touserid, subject, msg, userid FROM #{archive_table} WHERE type = ? AND (userid = ? OR touserid = ?) AND time BETWEEN ? AND ?", archive_type, user_id, user_id, start_date.to_i, end_date.to_i)

			sortedresults = []
			result.each {|row|
				sortedresults << row
			}

			sortedresults.sort! {|a,b|
				a['time'].to_i <=> b['time'].to_i
			}

			sortedresults.each {|row|
				date_sent = Time.at(row['time'].to_i).strftime("%a %b %d %H:%M:%S %Z %Y")
				ip = self.long2ip(row['ip'].to_i)
				recipient = UserName.find(:first, row['touserid'].to_i)
				if recipient.nil?
					recipient = "Unknown User ID ##{row['touserid']}"
				else
					recipient = recipient.username
				end
				sender = UserName.find(:first, row['userid'].to_i)
				if sender.nil?
					sender = "Unknown User ID ##{row['userid']}"
				else
					sender = sender.username
				end
				msg = row['msg'].gsub(/(.{1,80})( +|$)\n?|(.{80})/, "\\1\\3\n")

				archive_report.append(<<-EOM)
--------------------------------------------------------------------------------
Archived #{archive_type_description} ID #{row['id']}
Date Sent:         #{date_sent}
Sender:            #{sender} (#{row['userid']})
Sender IP Address: #{ip}
Recipient:         #{recipient} (#{row['touserid']})
Subject:           #{row['subject']}

#{msg}
--------------------------------------------------------------------------------
				EOM
			}
			archive_reports.push(archive_report) unless archive_report.get_content().nil?
		end

		archive_reports
	end

	def dump_user_abuse_log(user_id, start_date, end_date)
		result = $site.dbs[:moddb].query("SELECT * FROM abuselog WHERE userid = ? AND time BETWEEN ? AND ?", user_id, start_date.to_i, end_date.to_i)

		sortedresults = []
		result.each {|row|
			sortedresults << row
		}

		sortedresults.sort! {|a,b|
			a['time'].to_i <=> b['time'].to_i
		}

		abuse_report = AbuseReportFile.new("abuselog.txt")
		sortedresults.each {|row|
			action = AbuseAction.description(row['action'].to_i)
			date_reported = Time.at(row['time'].to_i).strftime("%a %b %d %H:%M:%S %Z %Y")
			reason = AbuseReason.description(row['reason'].to_i)

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

			abuse_report.append <<-EOM

Abuse Log Message ID #{row['id']}
User Reported:          #{user_reported} (#{row['userid']})
Reported By:            #{user_reporter} (#{row['reportuserid']})
Date Reported:          #{date_reported}
Administrator Assigned: #{moderator} (#{row['modid']})
Action:                 #{action}
Reason:                 #{reason}
Subject:                #{row['subject']}

#{msg}

			EOM
		}
		[abuse_report]
	end

	def dump_user_ip_activity(user_id, start_date, end_date)
		result = $site.dbs[:usersdb].query("SELECT activetime, ip, hits FROM userhitlog WHERE userid = # AND activetime BETWEEN ? AND ?", user_id, start_date.to_i, end_date.to_i)

		sortedresults = []
		result.each {|row|
			sortedresults << row
		}

		sortedresults.sort! {|a,b|
			a['activetime'].to_i <=> b['activetime'].to_i
		}

		ip_report = AbuseReportFile.new("ipactivity.csv")
		ip_report.append(%Q!"Username","User ID","IP Address","Last Activity","Total User Hits From IP"\n!)
		sortedresults.each {|row|
			user = UserName.find(:first, user_id.to_i)
			if user.nil?
				user = "Unknown User ID ##{user_id}"
			else
				user = user.username
			end
			ip = self.long2ip(row['ip'].to_i)
			last_active = Time.at(row['activetime'].to_i).strftime("%a %b %d %H:%M:%S %Z %Y")
			ip_report.append(%Q!"#{user}","#{user_id}","#{ip}","#{last_active}","#{row['hits']}"\n!)
		}
		[ip_report]
	end

	def dump_user_pictures(user_id, start_date, end_date)
		result = $site.dbs[:usersdb].query("SELECT id, description FROM pics WHERE userid = #", user_id)

		picture_descriptions_report = AbuseReportFile.new("pictures/descriptions.csv")
		picture_descriptions_report.append(%Q!"Filename","Description"\n!)

		user_pictures = []
		result.each {|row|
			user_section = (user_id.to_f / 1000).floor()
			image_filename = "#{row['id']}.jpg"
			#image_url = $site.image_url/:userpics/user_section/user_id/image_filename
			image_url = "http://images.nexopia.com/userpics/#{user_section}/#{user_id}/#{image_filename}"

			#image_response = Net::HTTP.get_response(URI.parse(image_url))
			#case image_response
			#when Net::HTTPSuccess
			#	image = AbuseReportFile.new("pictures/#{row['id']}.jpg")
			#	image.set_content(image_response.body)
			#else
				image = AbuseReportFile.new("pictures/#{row['id']}.jpg.txt")
				image.append("The image file at #{image_url} could not be retrieved successfully.\n")
		#		image.append("The server responded with " + image_response.to_s + ".\n")
			#end
			user_pictures.push(image)
			picture_descriptions_report.append(%Q!"#{image_filename}","#{row['description']}"\n!)
		}
		user_pictures.push(picture_descriptions_report)
	end

	def dump_user_friend_list(user_id, start_date, end_date)
		result = $site.dbs[:usersdb].query("SELECT friendid FROM friends WHERE userid = #", user_id)

		sortedresults = []
		result.each {|row|
			sortedresults << row
		}

		sortedresults.sort! {|a,b|
			a['friendid'].to_i <=> b['friendid'].to_i
		}

		friends_report = AbuseReportFile.new("friends.csv")
		friends_report.append(%Q!"Friend User ID","Friend Username","Friend Age","Friend Sex"\n!)
		sortedresults.each {|row|
			friend = User.get_by_id(row['friendid'].to_i)
			if friend.nil?
				friend = UserName.find(:first, row['friendid'].to_i) || "Unknown User ID ##{row['friendid']}"
				friends_report.append(%Q!"#{row['friendid']}",friend.username,"?","?"\n!)
			else
				friends_report.append(%Q!"#{row['friendid']}","#{friend.username}","#{friend.age}","#{friend.sex}"\n!)
			end
		}
		[friends_report]
	end

	def dump_user_messages(user_id, start_date, end_date)
		dump_archive(ArchiveType::MESSAGE, user_id, start_date, end_date)
	end

	def dump_user_comments(user_id, start_date, end_date)
		dump_archive(ArchiveType::COMMENT, user_id, start_date, end_date)
	end

	def long2ip(long)
	  ip = []
	  4.times {|i|
		ip.push(long.to_i & 255)
		long = long.to_i >> 8
	  }
	  ip.reverse.join(".")
	end
end
