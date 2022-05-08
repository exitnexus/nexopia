require "uploader/lib/file_handler"

class UploaderModule < SiteModuleBase
	file_handler ["users"], proc{|filename, fclass|
		imgid = nil;
		if (filename =~ /^\d+\/(\d+)\.jpg/) 
			imgid = $1;
			fclass = 'userpics';
		elsif (filename =~ /^thumbs\/\d+\/(\d+)\.jpg/) 
			imgid = $1;
			fclass = 'userpicsthumb';
		else 
			print "Status: 404 File Not Found\r\n\r\n";
			return;
		end
		uid = memd.get('imgiduid-#{imgid}');
		if (!uid) 
			uid = imgid2uid(imgid);
			memd.set('imgiduid-#{imgid}', uid) if (uid);
		end
		if (uid) 
			mogfilename = "#{$site.mogilefs.class_code[fclass]}/uid/imgid";
		else 
			print "Status: 404 File Not Found\r\n\r\n";
			return;
		end
	}
	
	
	file_handler ["banners","uploads"], proc{|filename, fclass|
		if (filename =~ /^(\d+)\/(\d+)\/(.*)/)  
			f = NexFile.load(fclass, $2.to_i, $3)
			if (!f.request)
				raise(PageError.new(404), "Not a valid userfile") if (!f.exists?)
				f.get_file
				f.request
			end
		elsif (fclass == "banners")
			f = NexFile.load(fclass, filename)
			if (!f.request)
				raise(PageError.new(404), "Not a valid banner") if (!f.exists?)
				f.get_file
				f.request
			end
		end
		throw :page_done;
	}
	
end

