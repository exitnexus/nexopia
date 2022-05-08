#!/usr/bin/env ruby

require 'auth'

class User
    include Storable
    storable_initialize(DBI.connect('DBI:Mysql:newusers:192.168.0.50', "root", "Hawaii"), "users");

    def friendsonline()
        return 666;
    end
    def initialize(*args)
        if (args.length > 0)
            load!(['userid'], args);
        end

    end
    def to_s
        return "User: " + userid.to_s;
    end

    def plus?
		return Time.now < Time.at(premiumexpiry);
    end

    attr :userid, true; #int(10) unsigned NOT NULL default '0',
    attr :state, true; #enum('new','active','frozen','deleted') NOT NULL default 'new',
    attr :frozentime, true; #int(11) NOT NULL default '0',
    attr :jointime, true; #int(11) NOT NULL default '0',
    attr :activetime, true; #int(11) NOT NULL default '0',
    attr :loginnum, true; #int(10) unsigned NOT NULL default '0',
    attr :ip, true; #int(11) NOT NULL default '0',
    attr :timeonline, true; #int(10) unsigned NOT NULL default '0',
    attr :online, true; #enum('n','y') NOT NULL default 'n',
    attr :premiumexpiry, true; #int(11) NOT NULL default '0',
    attr :dob, true; #int(11) NOT NULL default '0',
    attr :age, true; #tinyint(3) unsigned NOT NULL default '0',
    attr :sex, true; #enum('Male','Female') NOT NULL default 'Male',
    attr :loc, true; #int(10) unsigned NOT NULL default '0',
    attr :fwmsgs, true; #enum('y','n') NOT NULL default 'n',
    attr :enablecomments, true; #enum('y','n') NOT NULL default 'y',
    attr :friendslistthumbs, true; #enum('y','n') NOT NULL default 'y',
    attr :recentvisitlistthumbs, true; #enum('y','n') NOT NULL default 'y',
    attr :recentvisitlistanon, true; #enum('y','n') NOT NULL default 'y',
    attr :onlyfriends, true; #enum('neither','msgs','comments','both') NOT NULL default 'neither',
    attr :ignorebyage, true; #enum('neither','msgs','comments','both') NOT NULL default 'neither',
    attr :timeoffset, true; #smallint(5) unsigned NOT NULL default '4',
    attr :trustjstimezone, true; #enum('y','n') NOT NULL default 'y',
    attr :limitads, true; #enum('y','n') NOT NULL default 'y',
    attr :single, true; #enum('n','y') NOT NULL default 'n',
    attr :sexuality, true; #tinyint(3) unsigned NOT NULL default '0',
    attr :spotlight, true; #enum('y','n') NOT NULL default 'y',
    attr :firstpic, true; #int(10) unsigned NOT NULL default '0',
    attr :signpic, true; #enum('n','y') NOT NULL default 'n',
    attr :newmsgs, true; #tinyint(3) unsigned NOT NULL default '0',
    attr :newcomments, true; #tinyint(3) unsigned NOT NULL default '0',
    attr :showrightblocks, true; #enum('n','y') NOT NULL default 'n',
    attr :threadupdates, true; #enum('n','y') NOT NULL default 'n',
    attr :posts, true; #int(10) unsigned NOT NULL default '0',
    attr :forumrank, true; #varchar(24) NOT NULL default '',
    attr :showpostcount, true; #enum('y','n') NOT NULL default 'y',
    attr :forumpostsperpage, true; #tinyint(3) unsigned NOT NULL default '25',
    attr :forumsort, true; #enum('post','thread') NOT NULL default 'post',
    attr :replyjump, true; #enum('forum','thread') NOT NULL default 'forum',
    attr :onlysubscribedforums, true; #enum('n','y') NOT NULL default 'n',
    attr :orderforumsby, true; #enum('mostactive','mostrecent','alphabetic') NOT NULL default 'mostactive',
    attr :autosubscribe, true; #enum('n','y') NOT NULL default 'n',
    attr :showsigs, true; #enum('y','n') NOT NULL default 'y',
    attr :anonymousviews, true; #enum('n','y','f') NOT NULL default 'n',
    attr :friendsauthorization, true; #enum('n','y') NOT NULL default 'y',
    attr :hideprofile, true; #enum('n','y') NOT NULL default 'n',
    attr :defaultminage, true; #tinyint(3) unsigned NOT NULL default '14',
    attr :defaultmaxage, true; #tinyint(3) unsigned NOT NULL default '30',
    attr :defaultsex, true; #enum('Male','Female') NOT NULL default 'Male',
    attr :defaultloc, true; #int(10) unsigned NOT NULL default '1',
    attr :gallery, true; #enum('none','friends','loggedin','anyone') NOT NULL default 'none',
    attr :skin, true; #varchar(24) NOT NULL default '',
    attr :commentskin, true; #int(10) unsigned NOT NULL default '0',
    attr :blogskin, true; #int(10) unsigned NOT NULL default '0',
    attr :friendskin, true; #int(10) unsigned NOT NULL default '0',
    attr :galleryskin, true; #int(10) unsigned NOT NULL default '0',
    attr :abuses, true; #tinyint(3) unsigned NOT NULL default '0',
    attr :forumjumplastpost, true; #enum('n','y') NOT NULL default 'n',
    attr :fileslisting, true; #enum('private','public','loggedin','friends') NOT NULL default 'public',
    attr :parse_bbcode, true; #enum('y','n') NOT NULL default 'y',
    attr :bbcode_editor, true; #enum('y','n') NOT NULL default 'n',
    attr :filestoolbar, true; #tinyint(1) NOT NULL default '1',
    attr :filesquota, true; #int(10) unsigned NOT NULL default '10485760',
end

$anon = User.new();
$anon.limitads = 'n';

User.new(203.to_s);
