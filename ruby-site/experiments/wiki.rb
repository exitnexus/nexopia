require "storable";

class WikiPage
	include(Storable);
	storable_initialize(DBI.connect('DBI:Mysql:newwiki:192.168.0.50', "root", "Hawaii"), "wikipages");
end

class WikiData
	include(Storable);
	storable_initialize(DBI.connect('DBI:Mysql:newwiki:192.168.0.50', "root", "Hawaii"), "wikipagedata");
end
