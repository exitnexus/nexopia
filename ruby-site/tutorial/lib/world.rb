class World < Storable
  init_storable(:db, "worlds")
  
  def before_create
    $log.info "Creating world.", :spam
  end
  
  def after_create
    $log.info "World created.", :spam
  end
  
  def moons
    moonlist = World.find(:order => "orbitalradius ASC", :conditions => ["`primary` = ?", self.worldname])
  end
end