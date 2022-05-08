class LinkLog < Storable
	init_storable(:old_taskdb, "linklog");
	register_selection :channels, :channel;

	def LinkLog.channels()
		return find(:group => "channel").map {|channel| channel.channel};
	end

	def chanobj
		return IrcChannel.get(channel);
	end
end

class IrcChannel < Storable
	init_storable(:old_taskdb, "ircchannel");

	def IrcChannel.get(channel)
		ret =  find(:first, channel);
		if (!ret)
			ret = new();
			ret.channel = channel;
		end
		return ret;
	end
end
