require 'rubygems'
require 'rmail'
require 'net/smtp'
require 'base64'

module Orwell

	# Send an email.
	# This uses rmail to send the actual message.
	# 'to' is the email address or array of addresses to send the
	# message to.
	# 'subject' is the subject line for the message.
	# 'msg_plain' is the plain-text message body.
	# The rest of the arguments are optional, and are as follows:
	# :msg_html => HTML-ised message to send.
	# :msg_parts => If specified, other parts of the message body,
	#  items such as embedded images, css, etc., to send along.  Would
	#  normally only ever be specified if we also added :msg_html.
	#  It is [Content-Type, ID, Content], such as
	#  ['image/png', 'part1', ...content...].
	#  We do not expect the Content to be encoded yet, we'll convert it
	#  to base64 encoding.
	# :from => If specified, the 'from' address to use.
	# :smtp_server => Use this to send the message instead of the default.
	# :smtp_port => If specified, use this instead of the default.
	# We return true if we were able to dispatch the message successfully.
	# This does not necessarily mean the message will be delivered properly.
	# Otherwise, we will raise an appropriate error.
	def self.send_email(to, subject, msg_plain, params = {} )
		to_addrs = [*to].map { |addr|
			addr.to_s.strip
		}
		if params.has_key?(:from)
			from = params[:from].to_s.strip
		else
			from = "no-reply@#{$site.config.email_domain}"
		end
		subject = subject.to_s.strip
		raise ArgumentError.new("Empty 'from'") if from.empty?
		raise ArgumentError.new("Empty 'to'") if to_addrs.empty?
		raise ArgumentError.new("Empty 'subject'") if subject.empty?
		raise ArgumentError.new("Empty plain-text body") if msg_plain.empty?
		if params.has_key?(:msg_parts) && !params.has_key?(:msg_html)
			raise ArgumentError.new("Message parts but no html container")
		end
		if params.has_key?(:msg_parts)
			params[:msg_parts].each { |msg_part|
				if (!msg_part.kind_of?(Array) || (msg_part.length() != 3))
					raise ArgumentError.new("msg_parts specified incorrectly")
				end
				if (msg_part[0].strip() == "")
					raise ArgumentError.new("invalid msg_part content type")
				end
				if (msg_part[1].strip() == "")
					raise ArgumentError.new("invalid msg_part id")
				end
			}
		end

		# Convert 'to' addresses to rmail format
		to_addrs = RMail::Address::Parser.new(to_addrs.join(", ")).parse
		if to_addrs.empty?
			raise ArgumentError.new("Cannot find any valid 'to' addresses")
		end
		
		# Convert 'from' address to rmail format
		from_addrs = RMail::Address::Parser.new(from).parse
		if from_addrs.empty?
			raise ArgumentError.new("Cannot find any valid 'from' address")
		end
		if from_addrs.length > 1
			raise ArgumentError.new("Multiple 'from' addresses specified")
		end
		from = from_addrs[0]
		
		# Get SMTP server, port
		if params.has_key?(:smtp_server)
			smtp_server = params[:smtp_server]
		else
			smtp_server = $site.config.mail_server
		end
		if params.has_key?(:smtp_port)
			smtp_port = params[:smtp_port]
		else
			smtp_port = $site.config.mail_port
		end
		
		# Build the message
		msg = RMail::Message.new
		msg.header.to = to_addrs
		msg.header.from = from
		msg.header.subject = subject
		
		if (params.has_key?(:msg_html) || params.has_key?(:msg_parts))
			# Multi-part message

			# Add plain-text part
			part = RMail::Message.new
			part.header.set('Content-Type', 'text/plain',
				'charset' => 'us-ascii', 'boundary' => '900')
			part.body = msg_plain
			msg.add_part(part)

			# Add HTML part
			html_part = RMail::Message.new
			if (params.has_key?(:msg_html))
				part = RMail::Message.new
				part.header.set('Content-Type', 'text/html',
					'charset' => 'ISO-8859-1', 'boundary' => '900')
				part.body = "<html><body>#{params[:msg_html]}</body></html>"
				html_part.add_part(part)
			end

			if (params.has_key?(:msg_parts))
				params[:msg_parts].each { |msg_part|
					part = RMail::Message.new
					# Probably need to fix the following
					part.header.set('Content-Type', msg_part[0])
					part.header.set('Content-Transfer-Encoding', 'base64')
					part.header.set('Content-ID', "<#{msg_part[1]}>")
					part.body = Base64.encode64(msg_part[2])
					html_part.header.set('Content-Type', 'multipart/related')
					html_part.add_part(part)

					raise "Confused multipart body" unless html_part.multipart?
				}
			end
			
			msg.add_part(html_part) if (params.has_key?(:msg_html))
			
			raise "Confused multipart body" unless msg.multipart?
			msg.header.set('Content-Type', 'multipart/alternative') 
		else
			# Simple message
			msg.body = msg_plain
		end
		
		# Make an SMTP compatible string to send
		message_text = RMail::Serialize.write("", msg);
		
		# Okay, we have the message, now send it
		Net::SMTP.start(smtp_server, smtp_port) {|smtp|
			smtp.send_message(message_text,
				msg.header.from.format(), msg.header.to.format())
		}
	end # def self.send_email(to, subject, msg_plain, params = {} )
	
end
