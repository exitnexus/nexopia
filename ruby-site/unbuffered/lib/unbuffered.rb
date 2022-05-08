lib_require :Core, 'storable/storable'

class Unbuffered < Storable
	init_storable(:db, "unbuffered")
	
	def to_s()
		return self.unbuffered
	end
	
	def populate()
		results = db.query("TRUNCATE TABLE unbuffered")
		for i in 1..250
			u = Unbuffered::new
			u.contents1 = u.contents2 = "Content #{i}" * 8
			u.store
		end
		s = "INSERT INTO unbuffered (contents1, contents2) "
		s += "SELECT u1.contents1, u2.contents2 "
		s += "FROM unbuffered u1 CROSS JOIN unbuffered u2"
		db.query(s)
	end
	
	def retrieve_buffered()
		results = db.query("SELECT id, contents1, contents2 FROM unbuffered")
		count = 0
		while row = results.fetch_array do
			$log.error("retrieve_buffered returned empty content") if row[1] == ""
			count += 1
			ObjectSpace.garbage_collect() if (count % 1000) == 0
		end
		return count
	end
		
	def retrieve_unbuffered()
		results = db.unbuffered_query("SELECT id, contents1, contents2 FROM unbuffered")
		count = 0
		while row = results.fetch_array do
			$log.error("retrieve_buffered returned empty content") if row[1] == ""
			count += 1
			ObjectSpace.garbage_collect() if (count % 1000) == 0
		end
		return count
	end
	
	def compare_queries()
		res = db.query("SELECT id, contents1, contents2 FROM unbuffered")
		result1 = Hash.new
		while row = res.fetch_array do
			result1[row[0]] = [row[1], row[2]]
		end
		
		res = db.unbuffered_query("SELECT id, contents1, contents2 FROM unbuffered")
		result2 = Hash.new
		while row = res.fetch_array do
			result2[row[0]] = [row[1], row[2]]
		end
		
		return result1.sort() == result2.sort()
	end		
end
