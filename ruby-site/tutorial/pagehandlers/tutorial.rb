lib_require :Tutorial, "world"

class Tutorial < PageHandler
  declare_handlers("tutorial") {
    area :Public
    page :GetRequest, :Full, :tutorial
    handle :GetRequest, :tutorial2, "goodbye"
    page :GetRequest, :Full, :worlds, "worlds"
    handle :PostRequest, :worldspost, "worldspost"
  }

  def tutorial()
    t = Template.instance("tutorial","tutorial")
    t.tutorial_text = "Hello, world!"
    puts t.display()
  end
  
  def tutorial2()
    paramarray = []
    params.each {
      |key|
      val = params[key, String]
      paramarray.push key + '=' + val
    }
    paramstring = paramarray.join(",")
    puts "<div>Goodbye, cruel world. (#{paramstring})</div>"
  end
  
  def worlds()
    t = Template.instance("tutorial", "tags")
    t.worlds = getworlds(t, params)
		t.form_key = SecureForm.encrypt(request.session.user, url)
    puts t.display()
  end
  
  def worldspost()
    worlds
  end

  def getworlds(t, params)
    sorttype = params["sort", String] || "r"
    sortdirect = params["sortdirect", String] || "asc"
    directindicator = (sortdirect == "asc" ? "v" : "^")
    if (sorttype) == "r"
      t.radiusclass = "sort"
      t.radiusdirect = directindicator
      sort = "orbitalradius " + sortdirect
    else
      t.massclass = "sort"
      t.massdirect = directindicator
      sort= "mass2 " + sortdirect
    end
    World.find(:order => sort, :conditions => "`primary` = 'Sun'")
  end
end