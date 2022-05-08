lib_require :Json, "exported"

class CommentsModule < SiteModuleBase
	set_javascript_dependencies([SiteModuleBase.get(:Json)])
end
