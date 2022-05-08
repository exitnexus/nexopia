#!/usr/bin/env ruby
require 'storable'
require 'user'

#Represents an individual mail message identified by userid and message id.
class Message
    include Storable
    storable_initialize(DBI.connect('DBI:Mysql:newusers:192.168.0.50', "root", "Hawaii"), "msgtext");

	attr :userid, true; #int(10) unsigned NOT NULL default '0',
	attr :id, true; #int(10) unsigned NOT NULL default '0',
	attr :date, true; #int(11) NOT NULL default '0',
	attr :html, true; #enum('n','y') NOT NULL default 'n',
	attr :parse_bbcode, true; #enum('y','n') NOT NULL default 'y',
	attr :msg, true; #text NOT NULL,

	#Returns the set of unread messages for a user.
	def Message.getMessages(userid)
		mail = MailRecord.new();
		set = MailRecord.load_set(['userid', 'status'], [userid, "new"]);
		return set;
	end

	class MailRecord
		include Storable
		storable_initialize(DBI.connect('DBI:Mysql:newusers:192.168.0.50', "root", "Hawaii"), "msgs");
	
		attr :userid, true; #int(10) unsigned NOT NULL default '0',
		attr :id, true; #int(10) unsigned NOT NULL default '0',
		attr :otheruserid, true; #int(10) unsigned NOT NULL default '0',
		attr :folder, true; #int(10) unsigned NOT NULL default '0',
		attr :to, true; #int(10) unsigned NOT NULL default '0',
		attr :toname, true; #varchar(18) NOT NULL default '',
		attr :from, true; #int(10) unsigned NOT NULL default '0',
		attr :fromname, true; #varchar(18) NOT NULL default '',
		attr :date, true; #int(11) NOT NULL default '0',
		attr :mark, true; #enum('n','y') NOT NULL default 'n',
		attr :status, true; #enum('new','read','replied') NOT NULL default 'new',
		attr :othermsgid, true; #int(10) unsigned NOT NULL default '0',
		attr :replyto, true; #int(10) unsigned NOT NULL default '0',
		attr :subject, true; #varchar(64) NOT NULL default '',
	
	end
end

