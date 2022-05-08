
lib_require :Forums, 'forum'
lib_require :Devutils, 'quiz'

class TestNotWorkingTotalRows < Quiz
	def test_totalRows
		ret = Forum::Forum.find(:limit => 5, :total_rows => true);
					   
		ret.each { |cat| 
            $log.info "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
            $log.info "total_rows is returning nil."
            $log.info "By the time this was wrote it should be returning 2112."
            
            assert(cat.total_rows != nil);
        }
    end
end