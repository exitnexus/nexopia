lib_require :Core, "validation/display", "validation/set", "validation/results", "validation/rules", "validation/chain", "validation/rule", "validation/value_accessor";
lib_require :Promotions, "validation_rules"

require 'rmail'
require 'net/smtp'
require 'stringio'

module Promotions
	class PromotionsHandler < PageHandler

		CONTEST_EMAIL = "contest@nexopia.com";

		declare_handlers("promotions") {
			area :Public
			page :GetRequest, :Full, :terms, "terms"
		
			access_level :LoggedIn

			page :GetRequest, :Full, :waiver, "waiver"
			# Send anyone accessing /promotions to the waiver page as well
			page :GetRequest, :Full, :waiver
			page :PostRequest, :Full, :send_waiver, "send_waiver"
		}
	
	
		def waiver
			request.reply.headers['X-width'] = 0;
		
			t = Template.instance("promotions", "waiver")
		
			puts t.display
		end
	
	
		def terms
			request.reply.headers['X-width'] = 0;
		
			t = Template.instance("promotions", "terms")
		
			puts t.display
		end

	
		def send_waiver
			request.reply.headers['X-width'] = 0;

			waiver_template = Template.instance("promotions", "waiver")
			if (!validate(params, waiver_template))
				puts waiver_template.display
				return
			end		
		
			contest = params['contest', String, nil]
			firstname = params['firstname', String, nil]
			lastname = params['lastname', String, nil]
			email = params['email', String, nil]
			phone = params['phone', TypeSafeHash, nil]
			street = params['street', String, nil]
			city = params['city', String, nil]
			province = params['province', String, nil]
			postalcode = params['postalcode', TypeSafeHash, nil]
			answer = params['answer', Integer, nil]
			agree = params['agree', String, "NO OPTION SELECTED"]

			message = RMail::Message.new;

			message.body = 
				"Username: #{request.session.user.username}\n\n" + 
			
				"Contest: #{contest}\n\n" +
			 
				"First Name: #{firstname}\n" + 
			  "Last Name: #{lastname}\n\n" + 
			
				"Email: #{email}\n" + 
				"Phone: #{phone.values * '-'}\n" + 
				"Street: #{street}\n" + 
				"City: #{city}\n" + 
				"Province: #{province}\n" + 
				"Postal Code: #{postalcode.values * ' '}\n\n" +
			 
				"Answer to Skill Testing Question: #{answer}\n\n" +
			 
				"Agreement: #{agree}";
		
			message.header.to = CONTEST_EMAIL;
			message.header.from = "#{$site.config.site_name} <no-reply@#{$site.config.email_domain}>";
			message.header.subject = "Waiver from #{firstname} #{lastname} (Username: #{request.session.user.username} / Contest: #{contest})";

			# Make an SMTP compatible string to send
			message_text = RMail::Serialize.write("", message);

			# Send it out
			Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
				smtp.send_message(message_text, "no-reply@#{$site.config.email_domain}", CONTEST_EMAIL)};
			
			t = Template.instance("promotions", "confirm");
		
			puts t.display;
		end
	
	
		def validate(params, template)
			contest = params['contest', String, nil]
			firstname = params['firstname', String, nil]
			lastname = params['lastname', String, nil]
			email = params['email', String, nil]
			phone = params['phone', TypeSafeHash, nil]
			street = params['street', String, nil]
			city = params['city', String, nil]
			province = params['province', String, nil]
			postalcode = params['postalcode', TypeSafeHash, nil]
			answer = params['answer', String, nil]
			agree = params['agree', String, nil]

			template.contest = contest
			template.firstname = firstname
			template.lastname = lastname
			template.email = email
			template.phone = phone.values;
			template.street = street
			template.city = city
			template.province = province
			template.postalcode = postalcode.values;
			template.answer = answer
			template.agree = agree
		
			validation = Validation::Set.new;
		
			chain = Validation::Chain.new;
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("contest", contest)));
			chain.set_none_override(Validation::Rules::ClearOnNil.new( Validation::ValueAccessor.new("contest", contest)));
			validation.add("contest", chain);
			
			chain = Validation::Chain.new;
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("firstname", firstname)));
			chain.set_none_override(Validation::Rules::ClearOnNil.new( Validation::ValueAccessor.new("firstname", firstname)));
			validation.add("firstname", chain);

			chain = Validation::Chain.new;
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("lastname", firstname)));
			chain.set_none_override(Validation::Rules::ClearOnNil.new( Validation::ValueAccessor.new("lastname", firstname)));
			validation.add("lastname", chain);
			
			chain = Validation::Chain.new;
			chain.add( Promotions::Rules::CheckEmail.new( Validation::ValueAccessor.new("email", email)));
			chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("email", email)));
			validation.add("email", chain);

			chain = Validation::Chain.new;
			chain.add( Promotions::Rules::CheckEmail.new( Validation::ValueAccessor.new("email", email)));
			chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("email", email)));
			validation.add("email", chain);

			chain = Validation::Chain.new;
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("phone[0]", phone.values[0])));
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("phone[1]", phone.values[1])));
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("phone[2]", phone.values[2])));
			chain.add( Promotions::Rules::CheckNumeric.new( Validation::ValueAccessor.new("phone[0]", phone.values[0])));
			chain.add( Promotions::Rules::CheckNumeric.new( Validation::ValueAccessor.new("phone[1]", phone.values[1])));
			chain.add( Promotions::Rules::CheckNumeric.new( Validation::ValueAccessor.new("phone[2]", phone.values[2])));
			chain.add( Validation::Rules::CheckLength.new( Validation::ValueAccessor.new("phone[0]", phone.values[0]), "Area code", 3, 3 ));
			chain.add( Validation::Rules::CheckLength.new( Validation::ValueAccessor.new("phone[1]", phone.values[1]), "First part of phone number", 3, 3 ));
			chain.add( Validation::Rules::CheckLength.new( Validation::ValueAccessor.new("phone[2]", phone.values[2]), "Second part of phone number", 4, 4 ));
			
			chain.set_none_override(Validation::Rules::ClearOnNil.new( Validation::ValueAccessor.new("phone[0]", phone.values[0])));
			chain.set_none_override(Validation::Rules::ClearOnNil.new( Validation::ValueAccessor.new("phone[1]", phone.values[1])));
			chain.set_none_override(Validation::Rules::ClearOnNil.new( Validation::ValueAccessor.new("phone[2]", phone.values[2])));
			validation.add("phone", chain);

			chain = Validation::Chain.new;
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("street", street)));
			chain.set_none_override(Validation::Rules::ClearOnNil.new( Validation::ValueAccessor.new("street", street)));
			validation.add("street", chain);
			
			chain = Validation::Chain.new;
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("city", city)));
			chain.set_none_override(Validation::Rules::ClearOnNil.new( Validation::ValueAccessor.new("city", city)));
			validation.add("city", chain);			

			chain = Validation::Chain.new;
			chain.add(Validation::Rules::CheckNotEqualTo.new(Validation::ValueAccessor.new("province", province), "-1", "Must select a province"));
			chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("province", province)));
			validation.add("province", chain);

			chain = Validation::Chain.new;
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("postalcode[0]", postalcode.values[0])));
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("postalcode[1]", postalcode.values[1])));
			chain.add( Promotions::Rules::CheckPostal.new( 
				Validation::ValueAccessor.new("postalcode[0]", postalcode.values[0]),
				Validation::ValueAccessor.new("postalcode[1]", postalcode.values[1])));
			chain.add( Validation::Rules::CheckLength.new( Validation::ValueAccessor.new("postalcode[0]", postalcode.values[0]), "First part of postal code", 3, 3 ));
			chain.add( Validation::Rules::CheckLength.new( Validation::ValueAccessor.new("postalcode[1]", postalcode.values[1]), "Second part of postal code", 3, 3 ));
			validation.add("postalcode", chain);
			
			chain = Validation::Chain.new;
			chain.add( Validation::Rules::CheckNotEmpty.new( Validation::ValueAccessor.new("answer", answer)));
			chain.add(Validation::Rules::CheckEqualTo.new(Validation::ValueAccessor.new("answer", answer.to_i), 30, "Wrong answer"));
			chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("answer", answer)));
			validation.add("answer", chain);

			chain = Validation::Chain.new;
			chain.add(Promotions::Rules::CheckWaiverSelection.new(Validation::ValueAccessor.new("agree", agree)));
			validation.add("agree", chain, false);
			
			# TODO: Get javascript working

			validation.bind(template);
		
			return validation.valid?		
		end
	end
end