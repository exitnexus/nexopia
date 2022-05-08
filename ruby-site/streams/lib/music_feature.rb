lib_require :Core, 'storable/cacheable', 'users/user'

module Music
	class MusicFeature < Cacheable
		attr_accessor :date_string, :creation_date_string, :edit_date_string;
		
		init_storable(:streamsdb, 'musicfeatures');
		
		relation_singular(:user, :userid, User);
		
		def after_load
			@date_string = Time.at(startdate).strftime("%b %d, %Y");
			@creation_date_string = Time.at(date).strftime("%b %d, %Y");
			@edit_date_string = Time.at(startdate).strftime("%m/%d/%Y");
		end
		
		def MusicFeature.current()
			return find(:first, :conditions => ["startdate <= ?", Time.now.to_i()], :order => "date DESC");
		end
	end
end