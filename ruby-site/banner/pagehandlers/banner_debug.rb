lib_require :Banner, 'banner_client'

require 'rexml/parsers/treeparser'
require 'rexml/xpath'

class BannerDebug < PageHandler
	declare_handlers("banner") {
		handle :GetRequest, :xml_dump, "dump"
		page :GetRequest, :Full, :banners, 'banners'
		page :GetRequest, :Full, :campaigns, 'campaigns'
	}
	
	def initialize(*args)
		super(*args)
		@banner_client = BannerClient.new
	end
	
	def xml_dump
		request.reply.headers['Content-Type'] = 'application/xml';
		puts @banner_client.command_all("xml").first
	end
	
	def campaigns
		xml = @banner_client.command_all("xml").first;
		rexml = REXML::Document.new(xml)
		t = Template::instance("banner", "campaign_list")
		campaigns = Array.new
		REXML::XPath.each(rexml, "//campaign") {|campaign|
			unless (campaign.attribute("reference"))
				campaign_info = Meta::MetaObject.new
				campaign_info.id = REXML::XPath.first(campaign, "id/text()")
				campaign_info.payrate = REXML::XPath.first(campaign, "payrate/text()")
				campaign_info.paytype = REXML::XPath.first(campaign, "paytype/text()")
				campaign_info.views = REXML::XPath.first(campaign, "views/text()")
				campaign_info.clicks = REXML::XPath.first(campaign, "clicks/text()")
				campaign_info.times = REXML::XPath.first(campaign, "allowedTimes/timetable/string/text()")
				campaign_info.clicksperday = REXML::XPath.first(campaign, "clicksperday/text()")
				campaign_info.viewsperday = REXML::XPath.first(campaign, "viewsperday/text()")
				campaign_info.viewsperuser = REXML::XPath.first(campaign, "viewsPerUser/text()")
				campaign_info.limitbyperiod = REXML::XPath.first(campaign, "limitByPeriod/text()")
				campaign_info.banners = REXML::XPath.match(campaign, "//banner/id/text()").map{|id| id.to_s.to_i}.sort.join(',')
				campaign_info.enabled = REXML::XPath.match(campaign, "enabled/text()")
				campaigns << campaign_info
			end
		}
		t.campaigns = campaigns
		puts t.display
	end
	
	def banners
		xml = @banner_client.command_all("xml").first;
		rexml = REXML::Document.new(xml)
		t = Template::instance("banner", "banner_list")
		banners = Array.new
		REXML::XPath.each(rexml, "//banner") {|banner|
			unless (banner.attribute("reference"))
				banner_info = Meta::MetaObject.new
				banner_info.id = REXML::XPath.first(banner, "id/text()")
				banner_info.payrate = REXML::XPath.first(banner, "payrate/text()")
				banner_info.paytype = REXML::XPath.first(banner, "paytype/text()")
				banner_info.views = REXML::XPath.first(banner, "views/text()")
				banner_info.clicks = REXML::XPath.first(banner, "clicks/text()")
				banner_info.times = REXML::XPath.first(banner, "allowedTimes/timetable/string/text()")
				banner_info.clicksperday = REXML::XPath.first(banner, "clicksperday/text()")
				banner_info.viewsperday = REXML::XPath.first(banner, "viewsperday/text()")
				banner_info.viewsperuser = REXML::XPath.first(banner, "viewsPerUser/text()")
				banner_info.limitbyperiod = REXML::XPath.first(banner, "limitByPeriod/text()")
				banner_info.enabled = REXML::XPath.match(banner, "enabled/text()")
				banners << banner_info
			end
		}
		t.banners = banners.sort_by {|banner| banner.id.to_s.to_i}
		puts t.display
	end
end