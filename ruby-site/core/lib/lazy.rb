require('lazy');

class Lazy::Promise
	def __evaluated__
		if @computation
			return false;
		else
			return true;
		end
	end

	def respond_to?( message ) #:nodoc:
		message = message.to_sym
		message == :__result__ or
		message == :inspect or
		message == :__evaluated__ or
		__result__.respond_to? message
	end

end

module Kernel
	def evaluated?( promise )
		if promise.respond_to? :__evaluated__
			return promise.__evaluated__;
		else # not really a promise
			return true;
		end
	end
end
