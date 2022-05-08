class Storable
	def listen_create(&block) 
		prechain_method(:after_create, &block)
	end

	def listen_delete(&block)
		postchain_method(:before_delete, &block)
	end

	include Mocha::Standalone
	# The following no longer works with current versions of the
	# Mocha gem.  Nathan says we only used this for testing and
	# all the tests are currently broken.  So we have no reason
	# to include this even if using the older version of the gem.
	# include Mocha::SetupAndTeardown

	def mock_instance()

		mock_obj = new();
		self.columns.each_value{|column|
			default = column.default
			if (default == nil)
				default = Time.now.to_i
			end
			mock_obj.send(column.sym_ivar_equ_name, column.parse_string(default));
		}
		[*primary_key()].each{|column|
			mock_obj.send(:"#{column}=", 1);
		}
		existing_obj = find(:first, mock_obj.get_primary_key())
		if (existing_obj)
			return existing_obj;
		end
		mock_obj.store;
		return mock_obj;
	end

	def fake_get_seq_id(*args)
		@last_id ||= 0
		@last_id += 1
		return @last_id
	end


	def _use_test_db()
		@real_db = self.db;
		@real_table = self.table;

		alias real_get_seq_id get_seq_id
		alias get_seq_id fake_get_seq_id

		self.db = $site.dbs[:generatedtestdb];
		self.table = "#{$site.dbs.invert[@real_db]}_#{self.table}";
	end

	def _unuse_test_db()
		self.db = @real_db;
		self.table = @real_table;
		alias get_seq_id real_get_seq_id
	end

	def recurse_use_test_db(pklass)
		if (@@subclasses[pklass])
			@@subclasses[pklass].each{|klass|
				recurse_use_test_db(klass);
				if (klass.db.kind_of? SqlBase)
					klass._use_test_db
				end
			}
		end
	end

	def recurse_unuse_test_db(pklass)
		if (@@subclasses[pklass])
			@@subclasses[pklass].each{|klass|
				recurse_unuse_test_db(klass);
				if (klass.db.kind_of? SqlBase)
					klass._unuse_test_db()
				end
			}
		end
	end

	def use_test_db(&block)
		begin
			recurse_use_test_db(Storable);
			yield block;	
		ensure
			recurse_unuse_test_db(Storable);
		end
	end
end