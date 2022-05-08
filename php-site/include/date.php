<?

function getusertimedata()
{
	global $userData, $config;
	if(isset($userData['timeoffset']) && $userData['timeoffset'] >= 0)
		return gettimezones($userData['timeoffset']);
	else
		return gettimezones($config['timezone']);
}

function getusertimeoffset($time = false)
{
	$tmp = userlocaltime($time);
	$result = gmmktime($tmp[2], $tmp[1], $tmp[0], $tmp[4] + 1, $tmp[3], $tmp[5]);
	$result2 = usermktime($tmp[2], $tmp[1], $tmp[0], $tmp[4] + 1, $tmp[3], $tmp[5]);
	return $result - $result2;
}

function tz_gmtime($time=0) {
	// this will break if TZ is not set to UTC or GMT.
	$ltime = localtime($time);
	$ltime[5] += 1900; //fix the stupidity of localtime returning years since 1900.
	return $ltime;
}

function tz_is_leap_year($year) {
	if($year % 4) return 0;
	if($year % 100) return 1;
	if($year % 400) return 0;
	return 1;
}

function tz_days_in_month($month, $year) {
	$days = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if($month >= 12) $month %= 12;
	if($month == 1) return (tz_is_leap_year($year) ? 29 : 28);
	return $days[$month];
}

function tz_checkdate($date, $dst) {
	// Quick check on months
	if($date[4] > $dst[4]) return 1;
	if($date[4] < $dst[4]) return -1;

	if($dst[6] <= 6) {
		$tmp = $date;
		$tmp[3] = 1;
		if($dst[3] == 5) {
			$tmp[3] = tz_days_in_month($tmp[4], $tmp[5]);
		}

		$tmp = tz_gmtime(gmmktime($tmp[2], $tmp[1], $tmp[0], $tmp[4] + 1, $tmp[3]));

		if($dst[3] == 5) {
			$i = $tmp[6] - $dst[6];
			if($i < 0) $i += 7;
			$day = $tmp[3] - $i;
		} else {
			$i = $dst[6] - $tmp[6];
			if($i < 0) $i += 7;
			$day = $tmp[3] + (7 * ($dst[3] - 1)) + $i;
		}
	} else {
		// Use day of month
		$day = $dst[3];
	}

	$day = (($day * 24) + $dst[2]) * 60 + $dst[1] * 60;
	$daytime = (($date[3] * 24 + $date[2]) * 60 + $date[1]) * 60 + $date[0];

	if($daytime < $day) return -1;
	if($daytime > $day) return 1;
	return 0;
}

function tz_localtime($time, $tz_zone) {
	// only do initial adjustment if zone does not practice DST
	if($tz_zone[2] == 0 && $tz_zone[4] == 0) // no dst or std adjustments
	{
		// Adjust for initial offset
		$rtime = $time + ($tz_zone[0] * 60);
		$tmout = tz_gmtime($rtime);
		return $tmout;
	}

	// Check for daylight time
	$rtime = $time + (($tz_zone[0] + $tz_zone[4]) * 60);
	$dtm = tz_gmtime($rtime);
	$dtm[9] = true;
	$st = tz_checkdate($dtm, $tz_zone[3]);

	// Check for standard time
	$rtime = $time + (($tz_zone[0] + $tz_zone[2]) * 60);
	$stm = tz_gmtime($rtime);
	$stm[9] = false;
	$dt = tz_checkdate($stm, $tz_zone[1]);

	// Now check if we are in DST or not
	if($st < 0 || $dt >= 0) $tmout = $stm;
	else $tmout = $dtm;
	return $tmout;
}

function tz_mktime($tz_zone, $hour, $minute, $second, $month, $day, $year)
{
	// get the timestamp for it as if it were utc, then we adjust it for the real utc.
	$utc_time = my_gmmktime($hour, $minute, $second, $month, $day, $year);
	$btime = tz_gmtime($utc_time); // so we get the extra details back out (day of week, day of year)

	// if it doesn't observe dst, just return here.
	if ($tz_zone[2] == 0 && $tz_zone[4] == 0)
		return $utc_time - $tz_zone[0];

	// compare it with daylight time
	$dts = $utc_time - (($tz_zone[0] + $tz_zone[4]) * 60);
	$st = tz_checkdate($btime, $tz_zone[3]);

	// compare it with standard time
	$sts = $utc_time - (($tz_zone[0] + $tz_zone[2]) * 60);
	$dt = tz_checkdate($btime, $tz_zone[1]);

	// now return either the dst or the std timestamp depending on what part of the year
	// we're in
	if ($st < 0 || $dt >= 0)
		return $sts;
	else
		return $dts;
}

function userlocaltime($time = false)
{
	static $data = null;
	if($data === null)
		$data = getusertimedata();

	if($time === false)
		$time = time();

	// get as the local time and then convert back to unix timestamp for gmdate
	$localdate = tz_localtime($time, $data);
	return $localdate;
}

function userDate($format, $time = false){
	$localdate = userlocaltime($time);

	$time = gmmktime($localdate[2], $localdate[1], $localdate[0], $localdate[4] + 1, $localdate[3], $localdate[5]);

	return gmdate($format, $time);
}

function prefdate($format) // for preferences page, gets date as the user set tz says
{
	return userdate($format);
}

function usermktime($hour, $minute, $second, $month, $day, $year)
{
	static $data = null;
	if ($data === null)
		$data = getusertimedata();

	return tz_mktime($data, $hour, $minute, $second, $month, $day, $year);
}

function is_leaf_year($year) {
	$year = year_digit_check($year);
	if ($year % 400 == 0) {
		return true;
	} elseif ($year % 100 == 0 ) {
		return false;
	} elseif ($year % 4 == 0 ) {
		return true;
	} else {
		return false;
	}
}

function year_digit_check ($year) {

	 if ($year < 100) {
		if ($year < 70) {
			$year = $year + 2000;
		} else {
			$year = $year + 1900;
		}
	}
	return $year;
}

function get_gmt_different() {
	$result = mktime(0,0,0,1970,1,1);
	$result2 = gmmktime(0,0,0,1970,1,1);
	return $result - $result2;
}

function my_gmmktime($hr,$min,$sec,$mon,$day,$year){
	return my_mktime($hr,$min,$sec,$mon,$day,$year,0);
}

function my_mktime($hr, $min, $sec, $mon, $day, $year, $offset = false){
	if($offset===false)
		$gmt_different = get_gmt_different();
	else
		$gmt_different = $offset;

	$hr = intval($hr);
	$min = intval($min);
	$sec = intval($sec);
	$mon = intval($mon);
	$day = intval($day);
	$year = intval($year);

	$year = year_digit_check($year);


	$_day_power = 86400;
	$_hour_power = 3600;
	$_min_power = 60;

	$_month_table_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
	$_month_table_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);

	$_total_date = 0;
	if ($year >= 1970) {
		for ($a = 1970 ; $a <= $year; $a++) {
			$leaf = is_leaf_year($a);
			if ($leaf == true) {
				$loop_table = $_month_table_leaf;
				$_add_date = 366;
			} else {
				$loop_table = $_month_table_normal;
				$_add_date = 365;
			}
			if ($a < $year) { $_total_date += $_add_date;
			} else {
				for($b=1;$b<$mon;$b++) {
					$_total_date += $loop_table[$b];
				}
			}
		}
		$_total_date +=$day-1;
		return $_total_date * $_day_power + $hr * $_hour_power + $min * $_min_power + $sec + $gmt_different;
	} else {
		for ($a = 1969 ; $a >= $year; $a--) {
			$leaf = is_leaf_year($a);
			if ($leaf == true) {
				$loop_table = $_month_table_leaf;
				$_add_date = 366;
			} else {
				$loop_table = $_month_table_normal;
				$_add_date = 365;
			}
			if ($a > $year) { $_total_date += $_add_date;
			} else {
				for($b=12;$b>$mon;$b--) {
					$_total_date += $loop_table[$b];
				}
			}
		}
		$_total_date += $loop_table[$mon] - $day;

		$_day_time = $hr * $_hour_power + $min * $_min_power + $sec;
		$_day_time = $_day_power - $_day_time;
		return -( $_total_date * $_day_power + $_day_time - $gmt_different);
	}
}

// gets a 2d array (7 columns, 6 rows) of what days of the month
// fall on what day of the week. This can be used to display a calendar.
function getCalendar($year, $month)
{
	$weekarray = array(0 => false, 1 => false, 2 => false, 3 => false, 4 => false, 5 => false, 6 => false);

	$calarray = array();
	$firstday = gmmktime(0, 0, 0, $month, 1, $year);
	$lastday = gmmktime(0, 0, 0, $month + 1, 0, $year);

	$firstweekday = gmdate('w', $firstday) + 0;
	$firstmonthday = 1;
	$lastmonthday = gmdate('d', $lastday) + 0;

	$day = $firstmonthday;
	$weekday = $firstweekday;
	while ($day <= $lastmonthday)
	{
		$thisweek = $weekarray;
		while ($day <= $lastmonthday && isset($thisweek[$weekday]))
		{
			$thisweek[$weekday++] = $day++;
		}
		$calarray[] = $thisweek;
		$weekday = 0;
	}

	// make it 5 rows
	while (count($calarray) < 6)
	{
		$calarray[] = $weekarray;
	}

	return $calarray;
}
