require 'cgi'
require 'iconv'
require 'net/https'
require 'uri'
lib_require :Plus, 'invoice', 'plus'

module Plus
	class PlusPageHandler < PageHandler
		declare_handlers("plus") {
			area :Public
			page	:GetRequest, :Full, :plus_page
			page	:GetRequest, :Full, :plus_more_features,
			 	'plus_more_features'
			
			page	:OpenPostRequest, :Full, :pp_mobile_handler,
			 	'pp_mobile_handler'
			
			page	:OpenPostRequest, :Full, :pp_phone_handler,
			 	'pp_phone_handler'

			handle :PostRequest, :check_username, 'check_username'
			handle :PostRequest, :check_pluspin, 'check_pluspin'
			handle :PostRequest, :check_pluspin, 'add_pluspin'

			access_level :LoggedIn
			page	:PostRequest, :Full, :pp_mobile_start, 'pp_mobile_start'
			page	:PostRequest, :Full, :pp_phone_start, 'pp_phone_start'
		}
		
		def plus_page()
 			# Show through the skin background
			request.reply.headers['X-width'] = 0
	
			t = Template::instance('plus', 'plus')
			if (request.session.user.anonymous?)
				t.username = 'Username'
				t.username_escaped = ''
			else
				t.username = demand(request.session.user.username)
				t.username_escaped = CGI.escape(t.username)
			end
			
			puts t.display
		end

		def plus_more_features()
			t = Template::instance('plus', 'plus_more_features')
			
			puts t.display
		end
		
		def pp_mobile_start()
			request.reply.headers['X-width'] = 0

			username = params['username_mobile', String, '']
			user = User.get_by_name(username, true)
			site_redirect('/plus') if (user.nil?)
			
			purchaser_id = request.session.user.userid
			user_amounts = { user.userid => 3 }
			charge_gst = false # Handled by paymentpin.com
			
			invoice = Invoice::create(purchaser_id, user_amounts, charge_gst)
			return plus_page if (invoice.nil?)
			
			t = Template::instance('plus', 'pp_mobile_start')
			t.price = '3.01' # Paymentpin defines this as Canada&US only
			
			puts t.display
		end
		
		def pp_phone_start()
			request.reply.headers['X-width'] = 0

			price = params['select_land', Integer, 5]
			if (price == 5)
				price = '4.95'
			elsif (price == 10)
				price = '9.95'
			else
				$log.info "Confused price value: #{price}", :error
				price = '4.95'
			end

			username = params['username_land', String, '']
			user = User.get_by_name(username, true)
			site_redirect('/plus') if (user.nil?)			
			purchaser_id = request.session.user.userid
			user_amounts = { user.userid => price.to_f }
			charge_gst = false # Handled by paymentpin.com
			
			invoice = Invoice::create(purchaser_id, user_amounts, charge_gst)
			return plus_page if (invoice.nil?)
			
			t = Template::instance('plus', 'pp_phone_start')
			t.price = price

			puts t.display
		end
		
		def pp_phone_handler()
 			# Show through the skin background
			request.reply.headers['X-width'] = 0

			price = params['price', String, nil]
			user = params['user', String, nil]
			pin = params['nip', String, nil]
			pin_duration = params['nip_duration', String, nil]
			
			purchaser_id = request.session.user.userid
			conditions = "userid = #{purchaser_id} AND completed = 'n'
				AND total = #{price.to_f}"
			invoice = Invoice.find(:first, :conditions => conditions,
				:order => 'creationdate DESC', :limit => 1)
			if (invoice.nil?)
				raise "Unable to find associated invoice, aborted"
			end

			# Need to confirm the payment by sending an HTTP POST
			# and reading the response
			merchantID = $site.config.payment_pin_merchant
			customValue = ""
			
			values = [
				['merchantID', merchantID],
				['caller', user],
				['paymentPIN', pin],
				['price', price],
				['pin_duration', pin_duration],
				['customValue', invoice.id]
			]
			# Convert into escaped string, key=value&key=value&key=value...
			post_data = values.map { |keyvalue|
				"#{keyvalue[0]}=#{CGI::escape(keyvalue[1].to_s)}"
			}.join('&')
			
			url = URI.parse($site.config.payment_pin_validate_phone)
			http = Net::HTTP.new(url.host, url.port)
			http.use_ssl = true if url.scheme == 'https' # enable SSL/TLS
			http.start {
				http.request_post(url.path, post_data) { |res|
					response = unserialize(res.read_body)
					if (response['valid'] == true)
						# Award the plus, record the data, put on our party hats
						t = Template::instance('plus', 'pp_validation_success')
						t.lines = Array.new
						invoice.invoice_items.each { |item|
							Invoice::update(invoice.id, 'pp_land', '',
						 		price.to_f, response['transactionID'],
						 		'y')
							if (item.quantity == 1)
								duration = '1 month'
							else
								duration = '2 months'
							end
							t.lines << [duration,
								User.get_by_id(item.input).username]
						}
						Invoice::complete(invoice.id)

						puts t.display
					elsif ((response['faultcode'].to_i == 5) ||
						   (response['faultcode'].to_i == 14))
						t = Template::instance('plus', 'pp_validation_fail_pin')
						puts t.display
					else
						# Failed, display general error message
						t = Template::instance('plus', 'pp_validation_fail')
						t.faultcode = response['faultcode']
						t.faultstring = response['faultstring']
						puts t.display
					end
				}
			}
		end

		def pp_mobile_handler()
 			# Show through the skin background
			request.reply.headers['X-width'] = 0
			
			price_country = params['price_country', String, nil]
			price_currency = params['price_currency', String, nil]
			country = params['country', String, nil]
			priceId = params['priceId', String, nil]
			transactionId = params['transactionId', String, nil]
			nip = params['nip', String, nil]
			pin_duration = params['nip_duration', String, nil]
			
			raise "Unknown priceid" if (priceId != '3.01')
			price = 3.00
			
			purchaser_id = request.session.user.userid
			conditions = "userid = #{purchaser_id} AND completed = 'n'
				AND total = #{price}"
			invoice = Invoice.find(:first, :conditions => conditions,
				:order => 'creationdate DESC', :limit => 1)
			if (invoice.nil?)
				raise "Unable to find associated invoice, aborted"
			end

			# Need to confirm the payment by sending an HTTP POST
			# and reading the response
			merchantID = $site.config.payment_pin_merchant
			
			values = [
				['merchantID', merchantID],
				['transactionId', transactionId],
				['paymentPIN', nip],
				['NIPprice', price_country],
				['pin_duration', pin_duration],
				['customValue', invoice.id]
			]
			# Convert into escaped string, key=value&key=value&key=value...
			post_data = values.map { |keyvalue|
				"#{keyvalue[0]}=#{CGI::escape(keyvalue[1].to_s)}"
			}.join('&')
			
			url = URI.parse($site.config.payment_pin_validate_mobile)
			http = Net::HTTP.new(url.host, url.port)
			http.use_ssl = true if url.scheme == 'https' # enable SSL/TLS
			http.start {
				http.request_post(url.path, post_data) { |res|
					response = unserialize(res.read_body)
					if (response['valid'] == true)
						# Award the plus, record the data, put on our party hats
						t = Template::instance('plus', 'pp_validation_success')
						t.lines = Array.new
						invoice.invoice_items.each { |item|
							Invoice::update(invoice.id, 'pp_mobile', '',
						 		price, response['transactionId'], 'y')
							t.lines << ['2 weeks',
								User.get_by_id(item.input).username]
						}
						Invoice::complete(invoice.id)
						puts t.display
					elsif ((response['faultcode'].to_i == 5) ||
						   (response['faultcode'].to_i == 14))
						t = Template::instance('plus', 'pp_validation_fail_pin')
						puts t.display
					else
						# Failed, display general error message
						t = Template::instance('plus', 'pp_validation_fail')
						t.faultcode = response['faultcode']
						t.faultstring = response['faultstring']
						puts t.display
					end
				}
			}
		end

		# AJAX pagehandlers.
		def check_username()
			request.reply.headers['Content-Type'] =
			 	PageRequest::MimeType::PlainText

			username = params["validator", String, nil]
			user = User.get_by_name(username, true)
			if (user.nil? || !user.activated?)
				print ''
			else
				# Only allow buying plus for activated accounts
				username = demand(user.username)
				username_escaped = CGI.escape(username)
				# Maybe we should change encoding back?
				begin
					username = Iconv.new('UTF-8', 
						'WINDOWS-1252').iconv(username)
				rescue
					# Reencoding failed, ho hum
				end
				if (username.nil? || username.empty?)
					print ''
				else
					print username + ':' + username_escaped
				end
			end
		end

		# This also allows adding a new pluspin field.
		def check_pluspin()
			secret = params["validator", String, '']
			which = params["which", Integer, 0]
			otherpins = params["otherpins", Array, Array.new]

			if (which == 0)
				t = Template::instance('plus', 'plusvoucher')
			else
				t = Template::instance('plus', 'pluspin')
			end
			
			t.which = which
			if (secret.empty?)
				t.secret = ''
				t.value = '0'
			else
				t.secret, t.value = Plus::cardValue(secret)
				# Has user used a free pin from this batch?
				if (t.value == '2.00')
					res = $site.dbs[:shopdb].query("SELECT t2.id
						FROM paygcards AS t1, paygcards AS t2
						WHERE t1.batchid = t2.batchid AND
						t1.secret = ? AND t2.useuserid = #",
						t.secret, request.session.user.userid)

					res.each { |row|
						# Already used pin from batch
						t.secret = nil
						t.value = false
					}
				end
				
				if (t.secret.nil? || (t.value == false) ||
					otherpins.include?(t.secret) )
					t.secret = 'Invalid PIN'
					t.value = '0'
				end
			end
			
			puts t.display
		end

		private
		# This code is a reimplementation of the Php version that
		# comes with the PaymentPin tools code.  See the Php version
		# below this method.  The intent is to keep the code as similar
		# as possible.
		def unserialize(serializedTxt)
			obj = nil
			serializedTxt.strip!

			subpatt = 
			serializedTxt.match(/^(\[([^\]]*)\])*\(([a-z]{3})\)\{(.*)\}$/i)
			unless subpatt.nil?
				case subpatt[3]
				when 'ARR':
					obj = Hash.new
					tab_item = subpatt[4].split(',')
					tab_item.each { |v|
						v.strip!

						subsubpatt = v.match(/^\[([^\]]*)\](.*)$/i)
						unless subsubpatt.nil?
							obj[subsubpatt[1]] =
								unserialize(subsubpatt[2])
						end # unless subsubpatt.nil?
					} # tab_item.each
				when 'BOO':
					obj = (CGI::unescape(subpatt[4]) == 'true')
				when 'INT':
					obj = CGI::unescape(subpatt[4])
				when 'DBL':
					obj = CGI::unescape(subpatt[4])
				when 'STR':
					obj = CGI::unescape(subpatt[4])
				when 'OBJ':
					obj = nil
				when 'RES':
					obj = nil
				when 'NUL':
					obj = nil
				when 'UNK':
					obj = nil
				end # case subpatt[3]
			end # unless subpatt.nil?

			return obj

		end # unserialize

=begin
function unserialize($serializedTxt)
{
	$obj=NULL;
	$serializedTxt=trim($serializedTxt);
	if(preg_match('/^(\[([^\]]*)\])*\(([a-z]{3})\)\{(.*)\}$/i',$serializedTxt,$subpatt))
	{
	
		switch ($subpatt[3])
		{
			case 'ARR':
			
					$obj=array();
					$tab_item=explode(',',$subpatt[4]);
					foreach($tab_item as $i => $v)
					{
					
						$v=trim($v);
						
						if(preg_match('/^\[([^\]]*)\](.*)$/i',$v,$subsubpatt))
						{
						
							$obj[$subsubpatt[1]]=telnip_serialization::unserialize($subsubpatt[2]);
						}
					}
				break; 
			case 'BOO':$obj=(urldecode($subpatt[4])=='true'?true:false);break;
			case 'INT':$obj=urldecode($subpatt[4]);break;
			case 'DBL':$obj=urldecode($subpatt[4]);break;
			case 'STR':$obj=urldecode($subpatt[4]);break;
			case 'OBJ':$obj=NULL;break;break;
			case 'RES':$obj=NULL;break;
			case 'NUL':$obj=NULL;break;
			case 'UNK':$obj=NULL;break;

		}
		
	}
	
	return $obj;
}
=end

	end # class PlusPageHandler
	
end # module Plus
