lib_require :Core, "memusage"

class Blorp
	extend UserContent
	attr :str
	def initialize(str)
		@str = str
	end
	user_content :str
end

puts("Before: #{MemUsage.total}")
x = Blorp.new(%Q{[img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1491_00.jpg[/img][url][img]http://s159.photobucket.com/albums/t145/shorty_1967/th_100_1466_00.jpg[/img][url]})
puts(x.str.parsed)
puts("After: #{MemUsage.total}")
