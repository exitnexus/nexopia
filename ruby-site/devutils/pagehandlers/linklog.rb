lib_require :Core, "url";
lib_require :Devutils, "linklog";

class LinkLogHandler < PageHandler
	declare_handlers("linklog") {
		page :GetRequest, :Full, :linklog;

		page :GetRequest, :Full, :channel, "channel", input(String);
		page :GetRequest, :Full, :channel, "channel", input(String), input(/recent|popular/);
		page :GetRequest, :Full, :edit_channel, "channel", input(String), "edit";
		handle :PostRequest, :save_channel, "channel", input(String), "save";

		page :GetRequest, :Full, :nickname, "nickname", input(String);
		page :GetRequest, :Full, :nickname, "nickname", input(String), input(/recent|popular/);
	}

	def linklog()
		require "markaby";

		mab = Markaby::Builder.new;
		puts mab.div.bgwhite {
			h1("LinkLog");

			table(:border => 1) {
				LinkLog.channels.each {|channel|
					safechan = channel.sub(/^#/, '');

					tr {
						td(:rowspan => 4, :width=>'10%') {
							a(:href=>url/:linklog/:channel/safechan) {
								div channel;
								div IrcChannel.get(channel).subtitle;
							}
						}
						th "Most Recent Links";
					}

					recent = LinkLog.find(channel, :order => "logid DESC", :limit => 5);
					tr { td {
						ul {
							recent.each {|log|
								li {
									a("<#{log.nickname}> ", :href => url/:linklog/:nickname/log.nickname);
									a(log.text, :href => log.url, :target => "_empty");
								}
							}
						}
					}}
					recentids = recent.collect {|log| log.logid };
					active = LinkLog.find(:conditions => ['channel = ? AND logid NOT IN ?', channel, recentids], :order => "clicks DESC", :limit => 5);
					tr {
						if (active.length > 0)
							th "Most Active Links";
						end
					}
					tr {
						if (active.length > 0)
							td {
								ul {
									active.each {|log|
										li {
											a("<#{log.nickname}> ", :href => url/:linklog/:nickname/log.nickname);
											a(log.text, :href => log.url, :target => "_blank");
										}
									}
								}
							}
						end
					}
				}
			}
		}.to_s;
	end

	def channel(safechan, type = 'recent')
		require "markaby";
		channel = "##{safechan}";
		chanobj = IrcChannel.get(channel);

		i_params = params;

		mab = Markaby::Builder::new();
		puts mab.div.bgwhite {
			order, caption = if (type == 'recent')
				['logid DESC', 'Recent'];
			else
				['clicks DESC', 'Active'];
			end

			h1 "#{caption} LinkLog For #{channel}";
			h2 chanobj.subtitle;
			div {
				span "Switch to: ";
				a("Recent", :href => url/:linklog/:channel/safechan/:recent);
				span " | ";
				a("Active", :href => url/:linklog/:channel/safechan/:active);
			}
			div {
				a("Edit Channel Information", :href => url/:linklog/:channel/safechan/:edit);
			}
			div chanobj.description;

			page = i_params['page', Integer, 0];

			items = LinkLog.find(channel, :order => order, :limit => 20, :offset => page*20);
			table(:border => 1) {
				tr {
					th "Nickname";
					th "Link";
					th "Clicks";
				}
				items.each {|row|
					tr {
						td { a(row.nickname, :href=>url/:linklog/:nickname/row.nickname); }
						td { a(row.text, :href=>row.url, :target => "_blank"); }
						td row.clicks;
					}
				}
			}

			if (items.length == 20)
				a("Next Page", :href=>url/:linklog/:channel/safechan & {:page => page+1});
			end
		}.to_s
	end

	def edit_channel(safechan)
		require "markaby";
		channel = "##{safechan}";
		chanobj = IrcChannel.get(channel);

		mab = Markaby::Builder::new();
		puts mab.div.bgwhite {
			form(:action => url/:linklog/:channel/safechan/:save, :method => "post") {
				table {
					tr {
						th "Channel";
						td channel;
					}
					tr {
						th "Subtitle";
						td { input(:name => :subtitle, :type => :text, :value => chanobj.subtitle); }
					}
					tr {
						th "Description";
						td { textarea(chanobj.description, :name => :description) }
					}
					tr {
						td(:colspan => 2) {center { input(:type => :submit, :value => "Save"); } }
					}
				}
			}
		}.to_s;
	end

	def save_channel(safechan)
		channel = "##{safechan}";
		chanobj = IrcChannel.get(channel);

		chanobj.subtitle = params['subtitle', String, ''];
		chanobj.description = params['description', String, ''];
		chanobj.store;

		site_redirect(url/:linklog/:channel/safechan);
	end

	def nickname(nickname, type = 'recent')
		require "markaby";

		i_params = params;

		mab = Markaby::Builder::new();
		puts mab.div.bgwhite {
			order, caption = if (type == 'recent')
				['logid DESC', 'Recent'];
			else
				['clicks DESC', 'Active'];
			end

			h1 "#{caption} LinkLog For #{nickname}";
			div {
				span "Switch to: ";
				a("Recent", :href => url/:linklog/:nickname/nickname/:recent);
				span " | ";
				a("Active", :href => url/:linklog/:nickname/nickname/:active);
			}

			page = i_params['page', Integer, 0];

			items = LinkLog.find(:conditions => ['nickname = ?', nickname], :order => order, :limit => 20, :offset => page*20);
			table(:border => 1) {
				tr {
					th "Channel";
					th "Link";
					th "Clicks";
				}
				items.each {|row|
					tr {
						safechan = row.channel.sub(/^#/, '');
						td { a(row.channel, :href=>url/:linklog/:channel/safechan); }
						td { a(row.text, :href=>row.url, :target => "_blank"); }
						td row.clicks;
					}
				}
			}

			if (items.length == 20)
				a("Next Page", :href=>url/:linklog/:nickname/nickname & {:page => page+1});
			end
		}.to_s
	end
end
