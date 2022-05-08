lib_require :Core, 'template/template';
lib_require :Devutils, "defect", "defecthistorygroup";

require 'rmail'
require 'net/smtp'
require 'stringio'

# Defects (PageHandler)
#
# Handles:
#		/my/defects/create
#		/my/defects/{defect.id}
#		/my/defects/update
#		/defects/list
class Defects < PageHandler

	def initialize(*args)
		super(*args);
		@dump = StringIO.new;
	end


	declare_handlers("defects") {
		# User Level Handlers
		area :Self
		access_level :Any
		
		page :GetRequest, :Full, :create_defect, "create"
		page :GetRequest, :Full, :edit_defect, input(Integer)
		page :PostRequest, :Full, :update_defect, "update" 

		# Public Level Handlers
		area :Public
		
		page :GetRequest, :Full, :list_defects, "list"
		page :GetRequest, :Full, :view_defect, input(Integer)
	}

	
	def create_defect()
		t = Template::instance("devutils", "create_defect");
		
		user = session.user;
		
		t.handler_root = "/my/defects";

		puts t.display;
	end
	
	
	def list_defects
		defects = Defect.find(:all);
		t = Template::instance('devutils', 'list_defects');
		t.defects = defects;
		
		# Check the existance of a session. If it's nil, then just go to the publically accessible "view" page.
		# Otherwise, go to the "edit" page.
		session = request.session;
		if (session.anonymous?)
			t.handler_root = "/defects";
		else
			t.handler_root = "/my/defects";
		end
		
		puts t.display();
	end
	
	
	def edit_defect(id)
		defect = Defect.find(:first, id);

		t = Template::instance('devutils', 'edit_defect');
		t.defect = defect;

		defecthistorygroups = DefectHistoryGroup.find_by_defectid(id);
		t.defecthistorygroups = defecthistorygroups;

		test_results = TestStatus.find(:all, :conditions => ["testclass = ? && test = ?", defect.testclass, defect.test]);
		t.test_results = test_results;

		t.handler_root = "/my/defects";
		puts t.display();
	end


	def view_defect(id)
		defect = Defect.find(:first, id);

		t = Template::instance('devutils', 'view_defect');
		t.defect = defect;

		defecthistorygroups = DefectHistoryGroup.find_by_defectid(id);
		t.defecthistorygroups = defecthistorygroups;

		test_results = TestStatus.find(:all, :conditions => ["testclass = ? && test = ?", defect.testclass, defect.test]);
		t.test_results = test_results;

		t.handler_root = "/defects";
		puts t.display();
	end

	
	def update_defect()
		defect = Defect.find(:first, params['id', Integer]);

		user = request.session.user;
		
		# If we're creating a new defect.
		new_defect = false;
		
		if (defect.nil?)
			new_defect = true;
			defect = Defect.new;

			repuser = user;
			
			# TODO: Display error if user is nil here?	Is there a good way to just stop processing and
			# keep the form here? Add a "cancel" button and the user could be forced to either get things
			# correct or "cancel". Better than simply silencing errors or crapping out and forcing the
			# user to start over when an error occurs.
			if (!user.anonymous?)
				defect.repuserid = repuser.userid;
			end

		end
		
		defect.description = params['description', String];
		defect.workaround = params['workaround', String];

		# Look up the user that has been typed in as the one to assign the Defect to.
		# If the user cannot be found, the property will not get set.
		# TODO: There should be an indication that there was an error here.	 What's a
		# good user-friendly way to do this?
		ownusername = params['ownusername', String];
		ownuser = User.get_by_name(ownusername);
		if (!ownuser.nil?)
			defect.ownuserid = ownuser.userid;
		end
		
		defect.type = params['type', Symbol];
		defect.priority = params['priority', Symbol];
		defect.status = params['status', Symbol];
			
		defect.testclass = params['testclass', String];
		defect.test = params['test', String];
		defect.tags = params['tags', String];
			
		defect.store();
		
		if (!new_defect)			
			defect.store_defecthistory(user.userid, params['comment', String]);
		end
		
		# Send an email to anyone who is associated with the defect tags.
		notify_tag_owners(user, defect);
		
		# Go back to the defect list after the update is complete.
		#list_defects();
		site_redirect('/defects/list');
	end
	
	
	def notify_tag_owners(user, defect)
		tags = defect.tags;
		
		# Tags are comma-separated, so split on commas, removing any surrounding
		# space in the process.
		tag_array = tags.split(%r{\s*,\s*});
		
		user_emails_to_notify = Array.new;
		
		# Go through the tags to find out which users need to be notified.
		tag_array.each { |tag|
			defect_tags = DefectTag.find_by_tag(tag);
			defect_tags.each { |defect_tag|
				user_emails_to_notify << defect_tag.user.email;
			}
		};
		
		# Make a uniqe comma-separated list of email addresses
		user_emails_to_notify.uniq!;
		user_emails_to_notify_string = user_emails_to_notify * ", ";
		
		# Create the message
		message = RMail::Message.new;
		sio = StringIO.new;
		sio.puts("The following defect has been updated:\n\n");
		sio.puts("Defect ID: #{defect.id}\n\n");
		sio.puts("Description: #{defect.description}\n\n");
		
		message.body = sio.string;
		message.header.to = "#{user_emails_to_notify_string}";
		message.header.from = "#{user.email}";
		message.header.subject = "Defect ##{defect.id} has been updated";
		
		# Make an SMTP compatible string to send
		message_text = RMail::Serialize.write("", message);

		# Send it out
		Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
			smtp.send_message(message_text, user.email, user_emails_to_notify)};
	end
	
end