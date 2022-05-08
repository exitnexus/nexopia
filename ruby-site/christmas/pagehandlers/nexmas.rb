lib_require :Christmas, 'christmas_messager', 'christmas_message'
lib_require :Plus, 'plus_log'

class Nexmas < PageHandler
	declare_handlers("nexmas") {
		area :Admin
		access_level :Admin, CoreModule, :contests
		
		page :GetRequest, :Full, :nexmas
		page :PostRequest, :Full, :send_messages, 'send'
		
		handle :GetRequest, :all_stats, 'stats'
		handle :GetRequest, :batch_stats, 'stats', input(Integer)
	}
	
	def nexmas
		t = Template::instance('christmas', 'nexmas')
		puts t.display
	end
	
	def send_messages
		batch_id = params['batch_id', Integer]
		offset = params['offset', Integer]
		begin
			if (batch_id && offset)
				cm = ChristmasMessager.new(batch_id, offset)
				cm.send_messages
				puts "Messages sent successfully."
			else
				puts "Invalid batch id or offset."
			end
		rescue
			puts "Invalid batch id or offset."
			puts $!
			$log.object $!, :error
		end
	end
	
	def all_stats
		request.reply.headers["Content-Type"] = PageRequest::MimeType::PlainText
		messages = ChristmasMessage.find(:scan, :order => "batchid ASC")
		puts ChristmasMessage.csv_headers
		puts messages.map{|message| message.csv}.join("\n")
	end
	
	def batch_stats(batch_id)
		request.reply.headers["Content-Type"] = PageRequest::MimeType::PlainText
		messages = ChristmasMessage.find(batch_id, :batch, :order => "batchid ASC")
		puts ChristmasMessage.csv_headers
		puts messages.map{|message| message.csv}.join("\n")
	end
end