require 'rubygems'
spec = Gem::Specification.new {|s|
	s.name = "BBCode"
	s.version = "0.1.3"
	s.author = "Thomas Roy"
	s.email = "never."
	s.summary = "A BBcode parser."
	s.require_path = "."
	s.platform = Gem::Platform::RUBY
	s.files = ["ext/main.cpp", "ext/Parser.cpp", "ext/Parser.h", "ext/Scanner.cpp", "ext/Scanner.h"];
	s.extensions = ["ext/extconf.rb"];
}

if $0 == __FILE__
	Gem::manage_gems
	Gem::Builder.new(spec).build
end
