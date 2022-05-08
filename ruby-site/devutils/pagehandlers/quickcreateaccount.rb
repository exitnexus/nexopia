# This pagehandler has pages that are useful for creating an account quickly
# without going through an activation process
class QuickCreateAccount < PageHandler
	declare_handlers("account") {
		page :GetRequest, :Full, :create_quick, "create", "quick"
		handle :PostRequest, :create_submit, "create", "quick", "data"
	}

	Template.inline :Devutils, "create_quick", <<-TEMPLATE
	<t:create_quick>
		<h1>Quick Create Account</h1>
		<form method="post" action="/account/create/quick/data">
			<table>
				<tbody>
					<tr><th>Username</th><td><input type="text" name="username" /></td></tr>
					<tr><th>E-Mail Address</th><td><input type="text" name="email" /></td></tr>
					<tr><th>E-Mail Confirm</th><td><input type="text" name="email_confirm" /></td></tr>
					<tr><th>Password</th><td><input type="password" name="password" /></td></tr>
					<tr><th>Password Confirm</th><td><input type="password" name="password_confirm" /></td></tr>
					<tr><td colspan="2"><input type="submit" value="Create Account" /></td></tr>
				</tbody>
			</table>
		</form>
	</t:create_quick>
	TEMPLATE

	def create_quick()
		t = Template.instance(:Devutils, "create_quick", self);
		puts t.display
	end

	def create_submit()
		username = params["username", String]
		email = params["email", String]
		email_incorrect = email != params["email_confirm", String]
		password = params["password", String]
		password_incorrect = password != params["password_confirm", String]

		if (email_incorrect || password_incorrect)
			$log.info("Tried to create an account without correctly entering email or password.")
			site_redirect("/account/create/quick")
		end

		if (User.create(username, password, email, Time.now, :Male, 0, 0x7f000001, false, false))
			site_redirect("/")
		else
			site_redirect("/account/create/quick")
		end
	end
end
