lib_require :Core, 'users/user', 'users/locs'

sleep(1);

locs = Locs.get_children(0);

puts "id,parent";
locs.each {|loc|
	puts "#{loc.id},#{loc.parent}";
}

