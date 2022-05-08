require 'template'
require 'auth'

def getPOSTval(key)
    return $cgi.params[key][0];
end

username = getPOSTval('username');
password = getPOSTval('password')

if(username != nil)
    session = Session.new();
    id = UserNamesDB.getID(username);
    if (id != nil)
        correct_password = Password.check_password(password, id);
        if (correct_password)
            session.create_session(id, 'n');
            puts "Congrats, #{username} #{id}!";
        else
            puts "Password for #{username} is incorrect.";
        end
    else
        puts "Username #{username} does not exist!";
    end
else

    template = Template.new('login/login');
    template.set('checkSecure', "checkbox1");
    template.set('checkRememberMe', "checkbox2");
    template.set('referer', "sdaf");

    str = template.toString();
    rhtml = ERB.new(str, 0);

    def htmlentities(str)
        return str;
    end
    #Run with the current binding, which allows the template to access
    #all of the variables we set.
    rhtml.run(template.get_binding);

end


