if $0 == __FILE__
require 'test/unit'
	Dir['./**/*.test.rb'].each {|f|
		require f
	}

end