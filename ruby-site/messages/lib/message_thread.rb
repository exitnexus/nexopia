class MessageThread
	attr_accessor(:mark, :user_names, :messages, :latest_message)
	include(Enumerable);

	#construct with an array/orderedmap of messages in the thread
	def initialize(message_list)
		self.user_names = [];
		self.messages = message_list

		self.messages.each { |message|
			self.mark = true if (message.mark);
			self.user_names << message.fromname if (!self.user_names.include?(message.fromname));
			self.latest_message = message if (self.latest_message.nil? || self.latest_message.date < message.date);
		}
	end

	def user_names_list
		return self.user_names.join(', ');
	end

	def length
		return self.messages.length;
	end

	def each(&block)
		messages.each(&block);
	end

	def subject
		return self.latest_message.subject.gsub(/^(RE:)+/, '');
	end

	def order_by_date!
		self.messages = self.messages.sort_by { |message| -message.date }
	end

	def method_missing(*args)
		self.latest_message.send(*args);
	end
end
