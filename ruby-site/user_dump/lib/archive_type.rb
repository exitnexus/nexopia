require 'stringio'
lib_require :UserDump, "date_timestamp"

class ArchiveType
	attr_reader :type_id
	
	MESSAGE=1
	COMMENT=11
	PROFILE=21
	GALLERYCOMMENT=31
	BLOGPOST=41
	BLOGCOMMENT=42

	@@type_descriptions = {
		ArchiveType::MESSAGE => "Message",
		ArchiveType::COMMENT => "Comment",
		ArchiveType::PROFILE => "Profile",
		ArchiveType::GALLERYCOMMENT => "Gallery Comment",
		ArchiveType::BLOGPOST => "Blog Post",
		ArchiveType::BLOGCOMMENT => "Blog Comment"
	}

	def initialize(type_id)
		if @@type_descriptions.has_key?(type_id)
			@type_id = type_id
		else
			raise Exception.new("Attempted to initialize an ArchiveType with an invalid type_id.");
		end
	end

	def ArchiveType.description(type_id)
		if @@type_descriptions.has_key?(type_id)
			@@type_descriptions.fetch(type_id)
		else
			"No textual description available for archive type ##{type_id}"
		end
	end

	def description
		return self.class.description(self.type_id)
	end

	def dump_archive(user_id, start_timestamp, end_timestamp)
		archive_report = StringIO.new
		
		start_date = Time.at(start_timestamp)
		start_date = DateTimestamp.new(start_date.year, start_date.month, start_date.day)
		end_date = Time.at(end_timestamp)
		end_date = DateTimestamp.new(end_date.year, end_date.month, end_date.day)

		i = start_date - (start_date.mday() - 1)
		while (i <= end_date)
			archive_table = i.strftime("archive%Y%m")
			
			i >>= 1

			result = $site.dbs[:usersdb].squery(user_id, "SHOW TABLES LIKE '#{archive_table}'")
			next if result.num_rows == 0
			result = $site.dbs[:usersdb].query("SELECT id, time, ip, touserid, subject, msg, userid FROM #{archive_table} WHERE type = ? AND (userid = ? OR touserid = ?) AND time BETWEEN ? AND ?", self.type_id, user_id, user_id, start_timestamp, end_timestamp)

			sortedresults = []
			result.each {|row|
				sortedresults << row
			}

			sortedresults.sort! {|a,b|
				a['time'].to_i <=> b['time'].to_i
			}

			sortedresults.each {|row|
				date_sent = Time.at(row['time'].to_i).getutc.strftime("%a %b %d %H:%M:%S %Z %Y")
				ip = Session.int_to_ip_addr(row['ip'].to_i)
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
				
				archive_report.puts("
--------------------------------------------------------------------------------
Archived #{self.description} ID #{row['id']}
Date Sent:         #{date_sent}
Sender:            #{sender} (#{row['userid']})
Sender IP Address: #{ip}
Recipient:         #{recipient} (#{row['touserid']})
Subject:           #{row['subject']}

#{msg}
--------------------------------------------------------------------------------
")
				}
		end

		return archive_report.string
	end

	def to_i()
		return @type_id
	end

	def to_s()
		self.description(@type_id)
	end
end
