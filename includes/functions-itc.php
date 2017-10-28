<?php

function &get_app($sku) {
	return get_product($sku);
}

function &get_product($sku) {
	global $mysql;

	static $apps = array();

	if (isset($apps[$sku]))
		return $apps[$sku];

	$stmt = $mysql->prepare("SELECT * FROM `product` WHERE `sku` = ?");
	$stmt->bind_param('i', $sku);

	if ($stmt->execute()) {
		if ($result = $stmt->get_result()) {
			if ($record = $result->fetch_assoc())
				$apps[$sku] = $record;

			$result->free();
		}

		$stmt->close();
	}

	return $record;
}

function &get_release($sku, $version) {
	global $mysql;

	static $releases = array();

	if (isset($releases[$sku][$version]))
		return $releases[$sku][$version];

	$query = "SELECT *
		FROM `release` INNER JOIN `product` ON `release`.`sku` = `product`.`sku`
		WHERE `release`.`sku` = ? AND `release`.`version` = ?";

	$stmt = $mysql->prepare($query);
	$stmt->bind_param('is', $sku, $version);

	if ($stmt->execute()) {
		if ($result = $stmt->get_result()) {
			if ($record = $result->fetch_assoc())
				$releases[$sku][$version] = $record;

			$result->free();
		}

		$stmt->close();
	}

	return $record;
}

function get_report($page, $year = CUR_YEAR, $month = CUR_MONTH) {
	global $mysql;

	$time = mktime(0, 0, 0, $month, 1, $year);

	$min_day = 1;
	$max_day = intval(date('t', $time));

	$report = array();
	$dates  = array();
	$types = array(1 => 'installs', 7 => 'upgrades');

	if ($year > CUR_YEAR || $year == CUR_YEAR && $month > CUR_MONTH)
		return false;
	elseif ($year == CUR_YEAR && $month == CUR_MONTH)
		$max_day = intval(date('d')) - 1;

	$sku_list = implode(', ', $page['skus']);

	$query = "SELECT `release`.`sku`,`download`.`type_id`, `download`.`date`,
		SUM(`download`.`count`) AS `count`
		FROM `release` LEFT JOIN `download`
		ON `download`.`release_id` = `release`.`id`
		WHERE `release`.`sku` IN ($sku_list)
		AND YEAR(`download`.`date`) = $year
		AND MONTH(`download`.`date`) = $month
		GROUP BY `release`.`sku`, `download`.`type_id`, `download`.`date`
		ORDER BY `download`.`date` DESC, `release`.`sku` ASC";

	if ($result = $mysql->query($query)) {
		while ($record = $result->fetch_assoc()) {
			$type = $record['type_id'];
			$date = $record['date'];
			$sku  = $record['sku'];

			if ($type = $types[intval($type)])
				$report[$date][$sku][$type] += $record['count'];
		}

		$result->free();
	}

	for ($i = 1; $i <= $max_day; $i++)
		$dates[] = sprintf('%04d-%02d-%02d', $year, $month, $i);

	// populate empty cells
	foreach ($dates as $date) {
		foreach ($page['skus'] as $sku) {
			$report[$date][$sku]['installs'] += 0;
			$report[$date][$sku]['upgrades'] += 0;
		}

		ksort($report[$date]);
	}

	krsort($report);

	return $report;
}

function get_country_report($page, $year = CUR_YEAR, $month = CUR_MONTH) {
	global $mysql, $regions;

	$countries = array();

	foreach ($regions as $region) {
		foreach ($region['countries'] AS $country)
			$countries[] = $country;
	}

	sort($countries);

	$package_count = count($page['skus']);
	$country_count = count($countries);

	$package_spaces = implode(', ', array_fill(0, $package_count, '?'));
	$country_spaces = implode(', ', array_fill(0, $country_count, '?'));

	$types = str_repeat('s', $package_count) . str_repeat('s', $country_count) . 'ii';

	$sql = "SELECT `product`.`sku`, `title`, `package`, `country`, `type_id`,
		YEAR(`date`) AS `year`, MONTH(`date`) AS `month`, SUM(`count`) AS `count`
		FROM `product`
		INNER JOIN `release` ON `product`.`sku` = `release`.`sku`
		INNER JOIN `download` ON `release`.`id` = `download`.`release_id`
		WHERE `product`.`sku` IN ($package_spaces)
		AND `country` IN ($country_spaces)
		GROUP BY `sku`, `type_id`, `country`, `year`, `month`
		HAVING `year` = ? AND `month` = ?
		ORDER BY `country` ASC";

	if ($stmt = $mysql->prepare($sql)) {
		$params = array();
		$params[] =& $types;

		for ($i = 0; $i < $package_count; $i++)
			$params[] =& $page['skus'][$i];

		for ($i = 0; $i < $country_count; $i++)
			$params[] =& $countries[$i];

		$params[] =& $year;
		$params[] =& $month;

		call_user_func_array(array($stmt, 'bind_param'), $params);

		$report = array();

		if ($stmt->execute() && $result = $stmt->get_result()) {
			$types = array(1 => 'installs', 7 => 'upgrades');

			while ($record = $result->fetch_assoc()) {
				$type    = $record['type_id'];
				$country = $record['country'];
				$sku     = $record['sku'];

				if ($type = $types[intval($type)])
					$report[$country][$sku][$type] += $record['count'];
			}

			// populate empty cells
			foreach ($countries as $country) {
				foreach ($page['skus'] as $sku) {
					$report[$country][$sku]['installs'] += 0;
					$report[$country][$sku]['upgrades'] += 0;
				}

				ksort($report[$country]);
			}

			ksort($report);

			$result->free();
		}

		$stmt->close();

		return $report;
	} else {
		trigger_error("MySQL Error #$mysql->errno: $mysql->error", E_USER_WARNING);
	}

	return false;
}

function read_report($path, $name = '') {
	if (!is_file($path))
		return null;

	$data = substr($name, -3) == '.gz' ? gzfile($path) : file($path);
	$keys = _split_report_row(array_shift($data));

	array_walk($data, '_parse_report_row', $keys);

	return $data;
}

function import_report($report) {
	global $mysql;

	$data = array();

	foreach ($report as $record) {
		$sku     = $record['SKU'];
		$title   = $record['Title'];
		$version = $record['Version'];
		$type    = $record['Product Type Identifier'];
		$date    = $record['End Date'];
		$units   = $record['Units'];
		$country = $record['Country Code'];

		if ($release =& get_release($sku, $version)) {
			$release_id = $release['id'];
		} else {
			$app =& get_app($sku);

			if (!$app) {
				$query = "INSERT INTO `product` (`sku`, `title`) VALUES (?, ?)";

				$stmt = $mysql->prepare($query);
				$stmt->bind_param('is', $sku, $title);
				$stmt->execute();
				$stmt->close();
			}

			$query = "INSERT INTO `release` (`sku`, `version`) VALUES (?, ?)";

			$stmt = $mysql->prepare($query);
			$stmt->bind_param('is', $sku, $version);

			if ($stmt->execute())
				$release_id = $mysql->insert_id;

			$stmt->close();
		}

		$date = date('Y-m-d', strtotime($date));

		$data[$release_id][$date][$country][intval($type)] += $units;
	}

	$fields = implode('`, `', array(
		'release_id',
		'type_id',
		'country',
		'date',
		'count'
	));

	$query = "INSERT INTO `download` (`$fields`) VALUES (?, ?, ?, ?, ?)";

	foreach ($data as $release_id => $dates) {
		foreach ($dates as $date => $countries) {
			foreach ($countries as $country => $counts) {
				foreach ($counts as $type_id => $count) {
					$stmt = $mysql->prepare($query);
					$stmt->bind_param('iissi', $release_id, $type_id, $country, $date, $count);
					$stmt->execute();
					$stmt->close();
				}
			}
		}
	}
}

function get_report_data($year, $month = 0, $day = 0) {
	if ($day == 0)
		$file = sprintf('reports/S_M_85012707_%04d%02d.txt', $year, $month);
	elseif ($month == 0)
		$file = sprintf('reports/S_Y_85012707_%04d.txt', $year);
	else
		$file = sprintf('reports/S_D_85012707_%04d%02d%02d.txt', $year, $month, $day);

	if (!is_file($file))
		return null;

	$data = file($file);
	$keys = _split_report_row(array_shift($data));

	array_walk($data, '_parse_report_row', $keys);

	return $data;
}

function _parse_report_row(&$row, $index, $keys = array()) {
	$values = array_pad(_split_report_row($row), count($keys), '');
	$row = array_combine($keys, $values);
}

function _split_report_row($row) {
	return explode("\t", trim($row));
}

function serialize_report($report) {
	$titles = array('x');
	$data = array(array());

	$i = 0;

	foreach ($report as $date => $product_data) {
		$j = 1;

		foreach ($product_data as $sku => $counts) {
			if ($i == 0) {
				$product = get_app($sku);
				$titles[$j] = $product['title'];
			}

			$data[$i][0]  = $date;
			$data[$i][$j] = $counts['installs'];

			$j++;
		}

		$i++;
	}

	$data[] = $titles;
	$data = array_reverse($data);

	$json = json_encode($data);

	return $json;
}
