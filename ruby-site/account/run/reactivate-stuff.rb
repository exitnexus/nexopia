lib_require :Core, 'users/user', 'users/useremails'
lib_require :Orwell, 'send_email'

result = $site.dbs[:masterdb].query("SELECT * FROM `useremails` WHERE active = 'n' AND time > ? AND time < ?", Time.utc(2009, "Feb", 3, 10, 0, 0).to_i, Time.utc(2009, "Feb", 3, 13, 0, 0).to_i)

userids = []
result.each {| line|
	$log.object line, :error
	userids.push(line['userid'].to_i)
}

$log.object userids

users = User::find(*userids)

users.each { |user|

	$log.info "user.username = #{user.username}", :error
	
	email = UserEmail::find(:first, user.userid)
	user.email = email

	msg = Orwell::SendEmail.new
	msg.subject = "#{$site.config.site_name} Activation Link"
	msg.send(user, 'activation_email_plain', :html_template => 'activation_email_html', :template_module => 'account', :key => email.key)

}