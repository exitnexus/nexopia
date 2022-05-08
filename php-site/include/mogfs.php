<?

	define('FS_RETRY', 10);	// number of times we'll try to store a file before giving ip

	class mogfs {
		private $mogile;
		public $FS_MAP;

		function __construct ($domain, $hosts = array()) {
			$this->mogile = new MogileFS_Client($domain, $hosts);

			global $typeid;

			define('FS_USERPICSTHUMB', 1);
			define('FS_USERPICS', 2);
			define('FS_GALLERY', 3);
			define('FS_GALLERYFULL', 4);
			define('FS_GALLERYTHUMB', 5);
			define('FS_SOURCE', 6);
			define('FS_BANNERS', $typeid->getTypeID("BannerFileType"));
			define('FS_UPLOADS', $typeid->getTypeID("UserFiles::FileType"));

			define('FS_MAXCLASS', max(FS_BANNERS, FS_UPLOADS));

			$this->FS_MAP = array(
				FS_BANNERS => 'source',
				FS_UPLOADS => 'source'
			);
		}


		// usage:
		// 		$res = $mogfs->add(FS_CLASS, 'key1', 'data1');
		// 		$res = $mogfs->add(FS_CLASS, array('key1' => 'data1', 'key2' => 'data2'));

		// with first usage, returns boolean true on success, false on failure.
		// with second usage, returns true if all keys are saved, otherwise an array with values
		// set to names of keys that failed. test with if ( ($failed = $mogfs->add(...)) !== true ) { }
		function add ($class, $keys, $data = null) {
			$items = is_array($keys) ? $keys : array($keys => $data);

			$failed = array();
			foreach ($items as $key => $data) {
				if(!$data)
					continue;

				if ($class < 1 || $class > FS_MAXCLASS) {
					trigger_error("mogilefs class must be an integer between 1 and " . FS_MAXCLASS, E_USER_WARNING);
					$failed[] = $key;
					continue;
				}

				$i = 0;
				while (true) {
					if (++$i >= FS_RETRY) {
						trigger_error("mogilefs reached max retry ($i >= " . FS_RETRY . ") on $key", E_USER_WARNING);
						$failed[] = $key;
						break;
					}

					if ($this->mogile->set("${class}/${key}", $this->FS_MAP[$class], $data))
						break;
				}
			}
			if (count($failed) == 0)
				return true;

			else
				return is_array($keys) ? $failed : false;
		}


		// usage:
		// 		$res = $mogfs->delete(FS_CLASS, 'key1');
		// 		$res = $mogfs->delete(FS_CLASS, array('key1', 'key2'));

		// if second parameter is a string, attempts to delete that key. returns boolean true on success, false on failure.
		// if second parameter is an array, returns boolean true if all keys are successfully deleted, otherwise an array
		// with values set to names of keys that failed. test with if ( ($failed = $mogfs->delete(...)) !== true ) { }
		function delete ($class, $items) {
			$keys = is_array($items) ? $items : array($items);

			$failed = array();
			foreach ($keys as $key) {
				if ($class < 1 || $class > FS_MAXCLASS) {
					trigger_error("mogilefs class must be an integer between 1 and " . FS_MAXCLASS, E_USER_WARNING);
					$failed[] = $key;
					continue;
				}

				if (! $this->mogile->delete("${class}/${key}"))
					$failed[] = $key;
			}

			if (count($failed) == 0)
				return true;

			else
				return is_array($items) ? $failed : false;
		}


		// usage:
		// 		$res = $mogfs->move(FS_CLASS, 'from_key', 'to_key');
		// 		$res = $mogfs->move(FS_CLASS, array('from_key1' => 'to_key1', 'from_key2' => 'to_key2');

		// if second parameter is a string, returns boolean true if that key is successfully renamed to the third parameter, false otherwise
		// if second parameter is an array, returns boolean true if all keys are successfully renamed, otherwise an array with values
		// set to the names of keys that failed. test with if ( ($failed = $mogfs->move(...)) !== true ) {}
		function move ($class, $keys, $moveto = null) {

			$items = is_array($keys) ? $keys : array($keys => $moveto);

			$failed = array();
			foreach ($items as $from => $to) {
				if ($class < 1 || $class > FS_MAXCLASS) {
					trigger_error("mogilefs class must be an integer between 1 and " . FS_MAXCLASS, E_USER_WARNING);
					$failed[] = $from;
					continue;
				}

				if ( ($contents = $this->fetch($class, $from)) === false ) {
					$failed[] = $from;
					continue;
				}

				if (! $this->add($class, $to, $contents)) {
					$failed[] = $from;
					continue;
				}

				$this->delete($class, $from);
			}

			if (count($failed) == 0)
				return true;

			else
				return is_array($keys) ? $failed : false;
		}


		// usage:
		// 		$contents =	$mogfs->fetch(FS_CLASS, 'key1');
		//		$items =	$mogfs->fetch(FS_CLASS, array('key1', 'key2');

		// if second parameter is a string, returns the contents of a single key, boolean false if the key could not be fetched.
		// if second parameter is an array, returns an array of key => contents pairs (content value will be boolean false for each failed key)
		function fetch ($class, $items) {
			$keys = is_array($items) ? $items : array($items);

			$fetched = array();
			foreach ($keys as $key) {
				if ($class < 1 || $class > FS_MAXCLASS) {
					trigger_error("mogilefs class must be an integer between 1 and " . FS_MAXCLASS, E_USER_WARNING);
					$fetched[$key] = false;
					continue;
				}

				$fetched[$key] = $this->mogile->get("${class}/${key}");
			}

			return is_array($items) ? $fetched : array_shift($fetched);
		}
		
		// usage:
		//  $isthere = $mogfs->test(FS_CLASS, 'key1');
		// Returns true if the file exists as far as mog is concerned, false otherwise.
		function test($class, $key) {
			$paths = $this->mogile->paths("${class}/${key}");
			if (!$paths || count($paths) < 1)
				return false;
			else
				return true;
		}
		
	}
