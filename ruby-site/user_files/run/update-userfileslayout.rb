
lib_require :UserFiles, "user_files_layout"

module UserFiles
	Layout.find(:all, :scan).each {|file|
		if (file.id == 0)
			file.id = Layout.get_seq_id(file.userid)
		end
	}
end