lib_require :Core, "attrs/class_attr";

class FileType
	class_attr_accessor(:path, :replication);
	self.path = Array.new();
	self.replication = 1;

	class << self
	end
end
