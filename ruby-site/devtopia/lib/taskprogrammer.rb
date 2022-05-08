module Devtopia
	class TaskProgrammer < Storable
		set_db(:devtaskdb);
		set_table("taskprogrammer");
		init_storable();
	end
end