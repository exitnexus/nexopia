lib_require :Core, 'template/template';
lib_require :Devutils, "defect_tag";

require 'stringio'

# DefectTags (PageHandler)
#
# Handles:
#		/defects/tags/list
#		/defects/tags/update
class DefectTags < PageHandler

	def initialize(*args)
		super(*args);
		@dump = StringIO.new;
	end


	declare_handlers("defects/tags") {
		# User Level Handlers
		area :Self
		access_level :Any
		
		# Public Level Handlers
		area :Public
		
		page :GetRequest, :Full, :edit_tags, "edit"
		page :PostRequest, :Full, :update_tags, "update"
	}

		
	# Handles the update that results from the form post of the edit handler.
	def update_tags()
		
		# DefectTag values for the DefectTags that already exist in the database.
		existing_tags = params['tag', TypeSafeHash];
		existing_usernames = params['username', TypeSafeHash];
		
		# DefectTag values for DefectTags that will need to be added to the database.
		new_tags = params['tag_new', TypeSafeHash];
		new_usernames = params['username_new', TypeSafeHash];
		
		# Take care of retrieving and updating any existing DefectTags
		if (!existing_tags.nil?)
			# TODO:
			# It would be nice to only retrieve the DefectTag records that have changed, instead
			# of every single DefectTag. If this particular PageHandler was being used by a lot
			# of people, or the DefectTag list grew fairly large, it would be a good thing to look
			# into. However, this is not likely to happen, and so the extra work isn't currently
			# justified. If I was to try and do this, I'd want to (a) use javascript on the template
			# to figure out which existing DefectTags were actually touched (i.e. listen for key
			# events on the form fields), and pass only those back to the update_tags handler. Then,
			# something like the following could be used to convert the string into something that
			# could be used as an SQL condition:
			#
			# keys = existing_tags.keys;
			# key_string = keys * ",";
			#
			# The SQL condition would be a WHERE constraint to specify that "DEFECTTAG.ID IN (id_1,
			# id_2,id_3,...,id_n)"
			
			# Retrieve all of the DefectTag records and hash them with a hash key that is equal to
			# the defecttag.id field.
			defect_tags = DefectTag.find(:all);
			defect_tag_hash = Hash[
				*defect_tags.collect { |defect_tag|
					[defect_tag.id, defect_tag]
				}.flatten];
			
			# For each of the existing tags passed from the form...
			existing_tags.each { |key, value|
				tag = existing_tags[key, String];
				username = existing_usernames[key, String];
				
				# Check the stored values
				stored_defect_tag = defect_tag_hash[key.to_i];
				
				# If there's been a change from what was stored in the database...
				if (stored_defect_tag.tag != tag || stored_defect_tag.username != username)
					stored_defect_tag.tag = tag;
					
					user = User.get_by_name(username);
					if (!user.nil?)
						stored_defect_tag.userid = user.userid;
						stored_defect_tag.store();
					end
				end
			}
		end
		
		# Take care of creating and storing any new DefectTags
		if (!new_tags.nil?)
			new_tags.each { |key, value|
				tag = new_tags[key, String];
				username = new_usernames[key, String];
	
				defect_tag = DefectTag.new;
				defect_tag.tag = tag;
				user = User.get_by_name(username);		
				if (!user.nil?)
					defect_tag.userid = user.userid;
					defect_tag.store();
				end
				# TODO: Otherwise, raise an error.
			}
		end
		
		# Reload the edit_tags handler.
		site_redirect('/defects/tags/edit');
	end
	
	
	# Allow authorized users to add/edit tags and their associated users.
	def edit_tags()
		defect_tags = DefectTag.find(:all);

		t = Template::instance('devutils', 'edit_defect_tags');
		t.defect_tags = defect_tags;
		t.handler_root = '/defects/tags';
		puts t.display();
	end

end