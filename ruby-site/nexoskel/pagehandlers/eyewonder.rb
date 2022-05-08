module NexoSkel
  class EyeWonder < PageHandler
	  declare_handlers("eyewonder") {
		  area :Public

      handle :GetRequest, :interim, "interim.html"
	  }
	  
	  def interim()
	    reply.headers['X-No-Header'] = "No-Header"
	    print %Q{
<script language="JavaScript">
  var query = window.location.search;
 	var adUrl = query.substring(5, query.length);
 	var clickthru;
 	var failclickthru;	
  document.write('<s'+'cript language="JavaScript" src="');
  document.write(adUrl+'"></s'+'cript>');
</script>}
    end
  end
end
