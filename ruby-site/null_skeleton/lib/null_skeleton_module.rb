lib_require :Core, "storable/user_content"
mod_require :Nexoskel

class NullSkeletonModule < SiteModuleBase
	tags = :skin, :skeleton

	def skeleton?()
		return true;
	end

	def smilies
		return SMILIES;
	end

	def smilie_path(smilie)
		return "#{$site.static_url}/smilies/#{SMILIES[smilie]}.gif";
	end
	def script_path()
		ns = site_module_get(:Nexoskel)
		return "#{ns.directory_name}/script";
	end
	def layout_path()
		ns = site_module_get(:Nexoskel)
		return "#{ns.directory_name}/layout";
	end
	def control_path()
		ns = site_module_get(:Nexoskel)
		return "#{ns.directory_name}/control";
	end
	def static_path()
		ns = site_module_get(:Nexoskel)
		return "#{ns.directory_name}/static";
	end
	def skin_data_path()
		ns = site_module_get(:Nexoskel)
		return "#{ns.directory_name}/skindata";
	end


	SMILIES = {
		':)' => 'smile',
		':(' => 'frown',
		':D' => 'biggrin',
		':imslow:' => 'imslow',
		':p' => 'tongue',
		':blush:' => 'blush',
		':hearts:' => 'hearts',
		':rolleyes:' => 'rolleyes',
		':love:' => 'love',
		':rofl:' => 'rofl',
		':gjob:' => 'thumbs',
		':sex:' => 'smileysex',
		':err:' => 'err',
		';)' => 'wink',
		':clap:' => 'clap',
		':date:' => 'date',
		':cry:' => 'crying',
		':drool:' => 'drool',
		':eek:' => 'eek',
		':beer:' => 'beer',
		':foie:' => 'nono',
		':cool:' => 'cool',
		':shocked:' => 'shocked',
		':cussing:' => 'cussing',
		':shifty:' => 'shiftyeyes',
		':omfg:' => 'omfg',
		':confused:' => 'confused',
		':nuts:' => 'silly',

		':evil:' => 'evil',
		':gdate:' => 'girlgirl',
		':headache:' => 'headache',
		':headbang:' => 'headbang',
		':ham:' => 'smash',
		':high5:' => 'high5',
		':hug:' => 'hugs',
		':hungry:' => 'hungry',
		':iik:' => 'iik',
		':jawdrop:' => 'jawdrop',
		':jk:' => 'jk',
		':kiss:' => 'kiss',
		':lol:' => 'lol',
		':lonely:' => 'lonely',
		':bdate:' => 'manman',
		':moon:' => 'moon',
		':music:' => 'music',
		':O' => 'yawn',
		':nana:' => 'nana',
		':neutral:' => 'neutral',
		':party:' => 'party',
		':pee:' => 'pee',
		':phone:' => 'phone',
		':please:' => 'please',
		':pray:' => 'pray',
		':psyco:' => 'psyco',
		':puke:' => 'puke',
		':egrin:' => 'egrin',

		':transport:' => 'transport',
		':crazy:' => 'crazy',
		':skull:' => 'skull',
		':sleep:' => 'sleep',
		':tv:' => 'tv',
		':steaming:' => 'steaming',
		':stun:' => 'stun',
		':typing:' => 'typing',
		':throwball:' => 'throwball',
		':show:' => 'show',
		':speak:' => 'speak',
		':bjob:' => 'nogood-red',
		':wassup:' => 'wassup',
		':no:' => 'no',
	}
end

