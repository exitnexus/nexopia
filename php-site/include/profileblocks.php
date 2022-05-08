<?
	class profileBlocks {
		private $db, $cache, $uid, $blocks;

		function __construct ($uid) {
			global $usersdb, $cache;

			$this->db = $usersdb;
			$this->cache = $cache;
			$this->uid = $uid;
			$this->_fetchBlocks();
		}

		// internal function - fetches profile blocks (cache checked first, then database)
		function _fetchBlocks() {
//			if ( ($this->blocks = $this->cache->get("profileblocks-{$this->uid}")) !== false )
//				return;

			$sth = $this->db->prepare_query('SELECT * FROM profileblocks WHERE userid = %', $this->uid);

			$this->blocks = array();
			while ($row = $sth->fetchrow())
				$this->blocks[ $row['blockid'] ] = $row;

			$this->_cacheBlocks();
		}

		// internal function - adds profile blocks to cache
		function _cacheBlocks () {
			sortCols($this->blocks, SORT_ASC, SORT_NUMERIC, 'blockid', SORT_ASC, SORT_NUMERIC, 'blockorder');
			$this->cache->put("profileblocks-{$this->uid}", $this->blocks, 60 * 60 * 24); //24h cache
		}

		// fetch the profile blocks
		function getBlocks () {
			return $this->blocks;
		}

		// delete any number of profile blocks. takes an array of block ids to delete.
		function delBlocks ($blockids = array()) {
			foreach ($blockids as $index => $blockid) {
				if (isset($this->blocks[$blockid]))
					unset($this->blocks[$blockid]);
				else
					unset($blockids[$index]);
			}

			if (count($blockids)) {
				$this->db->prepare_query('DELETE FROM profileblocks WHERE userid = % AND blockid IN (?)', $this->uid, $blockids);
				$this->_cacheBlocks();
			}
		}

		// adds new blocks if they don't exist, modifies them if they do. takes an AoA, where
		// each second-level array contains blocktitle, blockcontent, blockorder, permission,
		// and possibly a blockid (blockid present modifies existing block, blockid ommitted
		// adds a new block.
		function saveBlocks ($blocks = array()) {
			foreach ($blocks as $index => $block) {
				// add new block
				if (! isset($block['blockid'])) {
					$seqid = $this->db->getSeqID($this->uid, DB_AREA_PROFILE_BLOCKS);
					$block['blockid'] = $seqid;

					$this->db->prepare_query(
						'INSERT INTO profileblocks (userid, blockid, blocktitle, blockcontent, blockorder, permission) VALUES (%, #, ?, ?, #, ?)',
						$this->uid, $seqid, $block['blocktitle'], $block['blockcontent'], $block['blockorder'], $block['permission']
					);
				}

				// save new content for existing block
				elseif (isset($block['blockid']) && isset($this->blocks[ $block['blockid'] ])) {
					$this->db->prepare_query(
						'UPDATE profileblocks SET blocktitle = ?, blockcontent = ?, blockorder = #, permission = ? WHERE userid = % AND blockid = #',
						$block['blocktitle'], $block['blockcontent'], $block['blockorder'], $block['permission'], $this->uid, $block['blockid']
					);
				}
				else
					unset($blocks[$index]);

				// we either added a new block or modified an existing one
				if (isset($blocks[$index]))
					$this->blocks[ $block['blockid'] ] = array(
						'blockid'		=> $block['blockid'],
						'userid'		=> $this->uid,
						'blocktitle'	=> $block['blocktitle'],
						'blockcontent'	=> $block['blockcontent'],
						'blockorder'	=> $block['blockorder'],
						'permission'	=> $block['permission']
					);
			}

			$this->_cacheBlocks();
		}
	}

