require 'zip/zipfilesystem'

# This class manages all the other classes that can be dumped out during a user dump.
# Every time a class extends Dumpable #extended gets called.  #extended will tell
# UserDumpController it's Class so that it can be called later.
class UserDumpController
	
	@@registered_classes = []
		
	class << self
		
		def registered_classes
			@@registered_classes
		end
		
		def register(klass)
			@@registered_classes.push(klass)
		end
	
		def dump_all(userid)

			file_list = []
			
			@@registered_classes.each { |klass|
				file_list.push(klass.user_dump(userid))
			}
			
			return create_zip("zipfile.zip", file_list)
			
		end # dump_all(userid)
		
		def dump(params, request)
			
			start_year = params['start_year', Integer, nil];
			start_month = params['start_month', Integer, nil];
			start_day = params['start_day', Integer, nil];

			end_year = params['end_year', Integer, nil];
			end_month = params['end_month', Integer, nil];
			end_day = params['end_day', Integer, nil];

			start_time = Time.utc(start_year, start_month, start_day)
			end_time = Time.utc(end_year, end_month, end_day)

			userid = params['user_id', Integer]

			dump_classes = @@registered_classes.collect { |klass| 				
				klass if (params.include?(klass.name))
			}			
			
			dump_classes.compact!
			
			# don't want to spam people during testing
			if ($site.config.live)
				self.alert_email(userid, start_time, end_time, dump_classes, request)
			end
			
			file_list = []
			errors = ""
			dump_classes.each { |klass|
				begin
					file_list.push(klass.user_dump(userid, start_time.to_i, end_time.to_i))
				rescue Exception => e
					errors += "Error dumping section #{klass.name} probably because there's no data there:\n#{e}\n"
				end
			}

			error_file = Dumpable.str_to_file("errors.txt", errors)
			file_list.push(error_file)
			return create_zip("#{userid}-user_dump.zip", file_list)
			
		end
		
		#
		# Given an array of file paths takes all those files and puts them in a new zip file filename in the default user_dump location.
		# Returns the path to the zip file becuase Zip::ZipFile isn't a real file and returning it is next to useless.
		#
		def create_zip(filename, file_list)
			
			# attach the name of our zip file to the location we want to store user_dumps as specified in the config.
			zip_file_path = File.catname(filename, $site.config.user_dump_cache)

			# Open a new zip file.
			begin
				zip_file = Zip::ZipFile.open(zip_file_path, Zip::ZipFile::CREATE) 
				
				# Add each file to the zip.
				file_list.each { |file|
					zip_file.add(File.basename(file.path), File.expand_path(file.path))			
				}
			ensure
				zip_file.close()
			end

			# Can't do this at the same time as the add since the zip file isn't updated until it's closed.
			file_list.each { |file|
				File.delete(file.path)
			}
			
			return zip_file_path
						
		end # create_zip(filename, file_list)
		
		def alert_email(user_id, start_date, end_date, requested_functions, request)
			user_name = User.get_by_id(user_id.to_i)
			if user_name.nil?
				user_name = "Unknown (#{user_id})"
			else
				user_name = user_name.username
			end
			start_date = start_date.getutc.ctime
			end_date = end_date.getutc.ctime

			message = RMail::Message.new
			message.body = File.open("user_dump/templates/alert_email.txt", "r").gets(nil)
			message.body.gsub!(/\{user_id\}/, user_id.to_s)
			message.body.gsub!(/\{user_name\}/, user_name)
			message.body.gsub!(/\{start_date\}/, start_date)
			message.body.gsub!(/\{end_date\}/, end_date)
			message.body.gsub!(/\{remote_addr\}/, request.headers['REMOTE_ADDR'])
			message.body.gsub!(/\{requested_functions\}/, requested_functions.join(", "))
			message.body.gsub!(/\{remote_user\}/, request.session.user.username)

			message.header.from = "#{$site.config.site_name} <no-reply@#{$site.config.email_domain}>"
			message.header.subject = "User Dump Requested: #{user_name} (#{user_id})"
			['userdump@nexopia.com'].each {|recipient|
				message.header.to = recipient
				message_text = RMail::Serialize.write("", message) # Make an SMTP compatible string to send

				# Send the e-mail.
				Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
					smtp.send_message(message_text, "no-reply@#{$site.config.email_domain}", recipient)
				}
			}
		end # alert_email(user_id, start_date, end_date, requested_functions, request)
		
	end # class << self
end # class UserDumpController