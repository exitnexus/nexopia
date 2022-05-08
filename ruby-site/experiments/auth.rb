require 'var_dump'
require "cgi/session"
require "dbi"
require "storable"
require 'md5'
require 'user'

$cookies = Array[]

#Obviously needs to be improved.
def makeRandKey
    return rand()
end

class Password

    include(Storable);
    storable_initialize(DBI.connect('DBI:Mysql:newusers:192.168.0.50', "root", "Hawaii"), "userpasswords");

    attr :password
    attr :userid

    @@salt = "<removed>"; #random string from random.org
    def Password.check_password(password, userid)
        pass = Password.new().load!(['userid'], [userid]);
        hash = pass.password;
        return (hash == MD5.new( @@salt + password).to_s);
    end

end

#Resolves userid from username.  Backed by our DB.
class UserNamesDB
    @@storable_db = DBI.connect('DBI:Mysql:rubytest:192.168.0.50', "root", "Hawaii");
    include Storable
    storable_initialize(@@storable_db, "usernames")
    attr :userid, true;
    attr :username, true;
    def UserNamesDB.getID(name)
        entry = UserNamesDB.new();
        entry.load!(["username"], [name]);
        return entry.userid;
    end
end

#Represents a session.  Backed by cookies and the DB.
#To check for an existing session, run Session.get().
class Session
    include(Storable);
    storable_initialize(DBI.connect('DBI:Mysql:rubytest:192.168.0.50', "root", "Hawaii"), "sessions");

    attr :ip, true;
    attr :userid, true;
    attr :activetime, true;
    attr :sessionid, true;
    attr :cachedlogin, true;
    attr :lockip, true;
    attr :jstimezone, true;
    attr :ignore_user, true;

    def to_s
        return "Session: " + userid.to_s + ":" + sessionid.to_s;
    end
    def Session.get(pagehandler)
        key = pagehandler.cookie('key');
        userid = pagehandler.cookie('userid');
        session = Session.new();
        session.load!(['userid', 'sessionid'], [userid.to_s, key.to_s]);
        if (session.sessionid == nil)
            return nil;
        else
            session.ignore_user = User.new(userid);
            return session;
        end
    end

    def get_user
        return @ignore_user;
    end

    def create_session(pagehandler, userid, cachedlogin)
        cookiedomain = "";

        expire = (cachedlogin == 'y' ?
                  time + config['longsessiontimeout'] : Time.now + (60 * 60 * 24 * 30));  #cache for 1 month

        key = makeRandKey();
        pagehandler.set_cookie("key", key.to_s, expire, '/', cookiedomain);
        pagehandler.set_cookie("userid", userid.to_s, expire, '/', cookiedomain);

        session = Session.new();
        session.ip = $cgi.remote_addr;
        session.userid = userid;
        session.activetime = Time.now;
        session.sessionid = key;
        session.lockip = 'n';
        session.jstimezone = -360;
        session.store();
    end

    def Session.destroy_session(pagehandler)
        cookiedomain = "";

        expire = Time.now - 1;  #now
        session = Session.get(pagehandler);
        if (session == nil)
			return;
        end

        pagehandler.set_cookie("key", session.sessionid.to_s, expire, '/', cookiedomain);
        pagehandler.set_cookie("userid", session.userid.to_s, expire, '/', cookiedomain);

        session.activetime = (Time.now - 1).to_s;
        session.store();
    end

end

#if ($newtz = $this->checktimezone())
#    $this->usersdb->prepare_query("INSERT INTO sessions
#                                SET ip = #,
#                                userid = %,
#                                activetime = #,
#                                sessionid = ?,
#                                cachedlogin = ?,
#                                lockip = ?,
#                                jstimezone = #",
#    $ip, $userid, $time, $key, $cachedlogin, $lockip, $newtz);
#else
#    $this->usersdb->prepare_query("INSERT INTO sessions
#                                SET ip = #,
#                                userid = %,
#                                activetime = #,
#                                sessionid = ?,
#                                cachedlogin = ?,
#                                lockip = ?",
#                                $ip, $userid, $time, $key, $cachedlogin, $lockip);
#end



