lib_require :Core, "storable/storable", "pagehandler"
class MessageFolder < Storable
	init_storable(:usersdb, 'msgfolder');
	#constants for folders that everyone gets by default
	INBOX = 1;
	SENT = 2;
	TRASH = 3;
	COMMENTS      = 4;
    COMMENTS_SENT = 5;
    
	DEFAULT_FOLDERS = [
		INBOX,
		SENT,
		TRASH,
		COMMENTS,
		COMMENTS_SENT,
	]

	DEFAULT_FOLDER_NAMES = {
		INBOX => "Inbox",
		SENT => "Sent",
		TRASH => "Trash",
		COMMENTS      => "Comments",
		COMMENTS_SENT => "Comments Sent",
	}
		
	set_seq_initial_value(50); #leave ourselves some room in case we want other predefined folders
	
	def uri_info(mode = 'self')
		if (mode == 'self')
			return [name, url/:my/:messages_new/:folder/id];
		elsif (mode == 'admin')
			return [name, "/admin/user/#{userid}/messages/folder/#{id}"];
		end
	end
	
	class << self
		#returns a list of all folders including the predefined ones that aren't in the DB
		def all(uid=PageHandler.current.session.user.userid)
			db_folders = self.find(uid);
			folders = default_folders().merge(db_folders);
			return folders;
		end
		
		#returns an ordered map containing MessageFolder objects made for the default folders
		#TODO: Do something to these so that if you try and store them either nothing happens or we error
		def default_folders(uid=PageHandler.current.session.user.userid)
			folders = OrderedMap.new();
			DEFAULT_FOLDERS.each { |folder_id|
				folder = MessageFolder.new();
				folder.userid = uid;
				folder.id = folder_id;
				folder.name = DEFAULT_FOLDER_NAMES[folder_id];
				folders.add(folder);
			}
			return folders;
		end
	end
end
