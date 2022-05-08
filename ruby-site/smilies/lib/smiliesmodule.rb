lib_require :core, 'storable/user_content'
lib_require :core, 'data_structures/sortedhash'

class SmiliesModule < SiteModuleBase
	@@smilies = SortedHash.new( [
			[':)', 'smile'],
			[':(', 'frown'],
			[':D', 'biggrin'],
			[':imslow:', 'imslow'],
			[':p', 'tongue'],
			[':P', 'tongue'],
			[':blush:', 'blush'],
			[':hearts:', 'hearts'],
			[':rolleyes:', 'rolleyes'],
			[':love:', 'love'],
			[':rofl:', 'rofl'],
			[':gjob:', 'thumbs'],
			[':sex:', 'smileysex'],
			[':err:', 'err'],
			[';)', 'wink'],
			[':clap:', 'clap'],
			[':date:', 'date'],
			[':cry:', 'crying'],
			[':drool:', 'drool'],
			[':eek:', 'eek'],
			[':beer:', 'beer'],
			[':foie:', 'nono'],
			[':cool:', 'cool'],
			[':shocked:', 'shocked'],
			[':cussing:', 'cussing'],
			[':shifty:', 'shiftyeyes'],
			[':omfg:', 'omfg'],
			[':confused:', 'confused'],
			[':nuts:', 'silly'],

			[':evil:', 'evil'],
			[':gdate:', 'girlgirl'],
			[':headache:', 'headache'],
			[':headbang:', 'headbang'],
			[':ham:', 'smash'],
			[':high5:', 'high5'],
			[':hug:', 'hugs'],
			[':hungry:', 'hungry'],
			[':iik:', 'iik'],
			[':jawdrop:', 'jawdrop'],
			[':jk:', 'jk'],
			[':kiss:', 'kiss'],
			[':lol:', 'lol'],
			[':lonely:', 'lonely'],
			[':bdate:', 'manman'],
			[':moon:', 'moon'],
			[':music:', 'music'],
			[':O', 'yawn'],
			[':nana:', 'nana'],
			[':neutral:', 'neutral'],
			[':party:', 'party'],
			[':pee:', 'pee'],
			[':phone:', 'phone'],
			[':please:', 'please'],
			[':pray:', 'pray'],
			[':psyco:', 'psyco'],
			[':puke:', 'puke'],
			[':egrin:', 'egrin'],

			[':transport:', 'transport'],
			[':crazy:', 'crazy'],
			[':skull:', 'skull'],
			[':sleep:', 'sleep'],
			[':tv:', 'tv'],
			[':steaming:', 'steaming'],
			[':stun:', 'stun'],
			[':typing:', 'typing'],
			[':throwball:', 'throwball'],
			[':show:', 'show'],
			[':speak:', 'speak'],
			[':bjob:', 'nogood-red'],
			[':wassup:', 'wassup'],
			[':no:', 'no'],
		] );

	def script_values()
		return {:smilies => @@smilies}
	end
	def smilies()
		@@smilies
	end
	
	def smilie_path(smilie)
		return "#{$site.static_url}/smilies/#{@@smilies[smilie]}.gif";
	end


	@@regexps = {}
	@@smilies.each{|code, img|
		@@regexps[/(^|\s|>)(#{Regexp.quote(code)})($|\s|<)/] = img;
	}
	
	@@prebuilt = {};
	@@smilies.each{|code, img|
		@@prebuilt[code[0]] ||= [];
		@@prebuilt[code[0]] << Regexp.quote(code);
	};
	
	@@fast_smilies = {};
	@@prebuilt.each{|pre,post|
		@@fast_smilies[pre] = /#{(post.join("|"))}(\Z|\s|<)/i
	}
	
	@@fast_smilies2 = /(\A|\s|>)(#{@@smilies.keys.map{|str| Regexp::quote(str)}.join("|")})(\Z|\s|<)/;

	@@fast_smilies2 = /(\A|\s|>)((:|;)(#{@@smilies.keys.map{|str| Regexp::quote(str[1..-1])}.join("|")}))(\Z|\s|<)/;
	
	def self.smilify(str)
		return smilify4(str);
	end
	
	def self.smilify1(str)
		str = str.dup;
		@@regexps.each{|code, img|
			#/(\s|^|>)#{code}(\s|$|<)/
			#str.gsub!(code, "<img src=\"#{$site.static_files_url}/Legacy/smilies/#{img}.gif\" alt=\"#{code}\">");
			str.gsub!(code){
				" <img src=\"#{$site.static_files_url}/Legacy/smilies/#{img}.gif\" alt=\"#{$2}\"> ";
			}
		}
		return str;
	end
	
	
	def self.smilify2(str)
		output = "";
		@@fast_smilies.each{|pre,post|
			output = "";
			scanner = StringScanner.new(str);
			while (!scanner.eos?)
				s = scanner.scan(/(\A|\s|>)[^#{Regexp.quote(pre.chr)}]+/);
				if (s)
					output << scanner.matched
					sm = scanner.scan(post)
					if (sm)
						output << " <img src=\"#{$site.static_files_url}/Legacy/smilies/#{@@smilies[sm]}.gif\" alt=\"#{scanner.matched}\"> ";	
					else
						s = scanner.scan(/#{Regexp.quote(pre.chr)}/)
						if (s)
							output << pre.chr
						end
					end
				else
					s = scanner.scan(/(.*)/m)
#					puts "Breaking, writing #{s}"
					output << s;
					break;
				end
			end
			str = output;
		}
		return output
	end
	
	def self.smilify3(str)
		str.dup
		str.gsub!(@@fast_smilies2){
			img = @@smilies[$2]
			if (img)
				" <img src=\"#{$site.static_files_url}/Legacy/smilies/#{img}.gif\" alt=\"#{$2}\"> ";	
			else
				$2
			end
		}
	end

	#this is my favourite.
	def self.smilify4(str)
		str = str.dup
		index = 0
		s_count = 0;
		
		while(index = str.index(@@fast_smilies2, index))
			if(s_count > 200)
				break;
			end
			img = @@smilies[$2]
			if (img)
				rep = "<img src=\"#{$site.static_files_url}/Legacy/smilies/#{img}.gif\" alt=\"#{$2}\">"
				str[index+$1.length,$2.length] = rep;
				index += rep.length
				s_count = s_count + 1;
			else
				index = index+1;
			end
		end
		return str;		
	end

	def self.smilify5(str)
		str = str.dup;
		2.times{
			changed = false;
			str.gsub!(@@fast_smilies2){
				img = @@smilies[$2]
				if (img)
					changed = true;
					" <img src=\"#{$site.static_files_url}/Legacy/smilies/#{img}.gif\" alt=\"#{$2}\"> ";	
				else
					$2
				end
			}
			if !changed then break; end
		}
		str
	end

	UserContent::register_converter(:smilies, SmiliesModule::method(:smilify), true, UserContent::ContentConverter::GENERATES_HTML)

end
