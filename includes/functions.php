<?php

function date_title($year, $month) {
	return date('F, Y', mktime(0, 0, 0, $month, 1, $year));
}

function date_link($year, $month, $page = PAGE, $source = SOURCE) {
	if ($month < 1) {
		$month += 12;
		$year--;
	} else if ($month > 12) {
		$month -= 12;
		$year++;
	}

	return sprintf('%s/%s/%04d/%02d', $source, $page, $year, $month);
}

function get_request_date() {
	$year  = get_request_year();
	$month = get_request_month();

	if ($year < MIN_YEAR || $year == MIN_YEAR && $month < MIN_MONTH) {
		$year = MIN_YEAR;
		$month = MIN_MONTH;
	} elseif ($year > CUR_YEAR || $year == CUR_YEAR && $month > CUR_MONTH) {
		$year = CUR_YEAR;
		$month = CUR_MONTH;
	}

	return array($year, $month);
}

function get_request_year() {
	return isset($_REQUEST['y']) ? intval($_REQUEST['y']) : CUR_YEAR;
}

function get_request_month() {
	return isset($_REQUEST['m']) ? intval($_REQUEST['m']) : CUR_MONTH;
}

function import($file) {
	$path = sprintf("includes/%s-%s.php", $file, SOURCE);

	if (is_file($path))
		require_once $path;
}

function load_template($name, $key = SOURCE) {
	$base = sprintf("templates/%s.php", $name);
	$file = sprintf("templates/%s-%s.php", $name, $key);
	$path = is_file($file) ? $file : $base;

	include $path;
}
