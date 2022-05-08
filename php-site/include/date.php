<?

function getusertimeoffset()
{
	global $userData, $config;
	$offset = 0;
	if (isset($userData['jstimezone']) && $userData['trustjstimezone'])
		return $userData['jstimezone']*60;
	else if(isset($userData['timeoffset']) && $userData['timeoffset'] >= 0)
		return gettimezones($userData['timeoffset'])*60;
	else
		return gettimezones($config['timezone']);
}

function userDate($format, $time = false){
	global $userData;
	if($time === false)
		$time = time();

	$time += getusertimeoffset();
	return gmdate($format, $time);
//	return date($format, $time);
}

function prefdate($format) // for preferences page, gets date as the user set tz says
{
	global $userData;
	if(isset($userData['timeoffset']) && $userData['timeoffset'] >= 0)
		return gmdate($format, time() + gettimezones($userData['timeoffset'])*60);
	else
		return gmdate($format, time() + gettimezones($config['timezone']));
}


function jsdate($format) // for preferences page, gets date as the autodetected tz says
{
	global $userData;
	if(isset($userData['jstimezone']))
		return gmdate($format, time() + $userData['jstimezone']*60);
}

function userMkTime($hr,$min,$sec,$mon,$day,$year){

//	return my_mktime($hr, $min, $sec, $mon, $day, $year);

	global $userData, $timezones;
	$offset = 0;

	return my_mktime($hr, $min, $sec, $mon, $day, $year, 0 - getusertimeoffset());
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
