require 'net/http'
require 'uri'

module FriendFinder
	module AddressBookImporter
		Contact = Struct.new(:name, :email, :user_id, :invite_id, :alt_names)
		
		class << self
			def fetch_contacts(email, password)
				url = URI.parse($site.config.contacts_url)
				result = Net::HTTP.start(url.host, url.port) {|http|
					http.post(url.path, "email=#{email}&password=#{password}")
				}
				contacts = parse_results(result)
				return contacts
			end
			
			def fetch_file_contacts(file_name, file_type)
				url = URI.parse($site.config.contacts_url)
				result = Net::HTTP.start(url.host, url.port) {|http|
					http.post(url.path, "file_name=#{$site.config.pending_dir}/#{file_name}&file_type=#{file_type}")
				}
				contacts = parse_results(result)
				return contacts
			end
			
			def convert_emails_to_contacts(emails)
				contacts = []
				emails.each {|email|
					contact = Contact.new
					if(email.length < 1)
						next;
					end
					contact.email = email
					contacts << contact
				}
				return contacts
			end
			
			def parse_results(result)
				contacts = []
				result = result.body.to_s
				result.each {|file_line|
					contact = Contact.new
					line = file_line.split(':')
					if (line.length > 1)
						contact.name = line[0].gsub(/(^\s+|\s+$)/, '')
						contact.email = line[1].gsub(/(^\s+|\s+$)/, '')
						if(contact.email.length < 1)
							next;
						end
						contacts << contact
					else
						$log.info("Malformed line from address book importer: #{file_line}", :warning);
					end
				}
				return contacts
			end
		end
	end
end
