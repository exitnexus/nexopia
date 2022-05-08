<?php

/**
 * Based on the wikipedia PHP MogileFS Client
 * http://svn.wikimedia.org/viewvc/mediawiki/trunk/extensions/MogileClient/MogileFS.php?view=co
 *
 * Requires Net_HTTP_Client
 **/


class MogileFS_Client {
	public $socket;
	public $error;
	public $trackers;
	public $domain;

	function MogileFS_Client($domain, $trackers) {
		$this->trackers = $trackers;
		$this->domain = $domain;
		$this->error = '';
		$this->open_files = array();
	}

	function connect() {
		foreach ($this->trackers as $tracker) {
			list($ip, $port) = split(':', $tracker, 2);
			if ($port == null)
				$port = 7001;
			$this->socket = fsockopen($ip, $port);
			if ($this->socket)
				break;
		}

		return $this->socket;
	}
	function cmd($cmd, $args = array(), $warn_on_error = true) {
		$params = ' domain=' . urlencode($this->domain);
		foreach ($args as $key => $value)
			$params .= '&' . urlencode($key) ."=" . urlencode($value);

		if (! $this->socket)
			$this->connect();

		fwrite($this->socket, $cmd . $params . "\n");
		$line = fgets($this->socket);

		$words = explode( ' ', $line );
		if ($words[0] == 'OK') {
			parse_str(trim($words[1]), $result);
		} else {
			$result = false;
			$this->error = join(' ', $words);
			if ($warn_on_error)
				trigger_error("$cmd$params : " . $this->error, E_USER_WARNING);
		}
		return $result;
	}

	function domains() {
		$res = $this->cmd('GET_DOMAINS');
		if (!$res)
			return false;
		$domains = array();
		for ($i = 1; $i <= $res['domains']; ++$i) {
			$dom = 'domain' . $i;
			$classes = array();
			for ( $j = 1; $j <= $res[$dom.'classes']; $j++)
				$classes[$res[$dom.'class'.$j.'name']] = $res[$dom.'class'.$j.'mindevcount'];
			$domains[] = array('name' => $res[$dom], 'classes' => $classes);
		}

		return $domains;
	}

	function paths($key) {
		$res = $this->cmd('GET_PATHS', array('key' => $key));
		unset($res['paths']);
		return $res;
	}

	function delete($key) {
		// nuke from mogilefs
		$res = $this->cmd('DELETE', array('key' => $key), false);

		if ($res === false)
			return false;

		return true;
	}

	function rename ($from, $to) {
		$res = $this->cmd('RENAME', array('from_key' => $from, 'to_key' => $to));
		if ($res === false)
			return false;
		return true;
	}

	function get($key) {
		$paths = $this->paths($key);
		if ($paths == false)
			return false;

		foreach ($paths as $path) {
			$fh = fopen($path, 'r');
			$contents = '';
			if ($fh) {
				while (!feof($fh))
					$contents .= fread($fh, 8192);
				fclose($fh);
				return $contents;
			}
		}
		return false;
	}

	function put($key, $class, $data) {

	}

	function set($key, $class, $data) {
		$res = $this->cmd('CREATE_OPEN', array('key' => $key, 'class' => $class));
		if (!$res)
			return false;

		$put_res = $this->doPut($res['path'], $data);
		$res = $this->cmd('CREATE_CLOSE', array('key' => $key, 'class' => $class, 'devid' => $res['devid'], 'fid' => $res['fid'], 'path' => urldecode($res['path']), 'size' => strlen($data)));
		if ($res)
			return TRUE;
		else
			return FALSE;
	}

	// This will only work with the real mogilefs server (the broken perbal one)
	function doPut($path, $data) {
		// we need to use a temp file because curl is b0rked and the php
		// extension is poorly documented for what we are trying to do
		$len = strlen($data);
		$tmp = tmpfile();
		fwrite($tmp, $data);
		fseek($tmp, 0);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_PUT, 1);
		curl_setopt($ch, CURLOPT_URL, $path);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_INFILE, $tmp);
		curl_setopt($ch, CURLOPT_INFILESIZE, $len);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$rv = curl_exec($ch);

		if (!$rv || curl_errno($ch) != 0) {
			trigger_error("curl_exec(): " . curl_error($ch), E_USER_WARNING);
			curl_close($ch);
			fclose($tmp);
			return FALSE;
		} else {
			curl_close($ch);
			fclose($tmp);
			return TRUE;
		}
	}

}
