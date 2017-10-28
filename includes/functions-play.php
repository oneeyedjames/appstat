<?php

function read_report($path, $name = '') {
	if (($file = fopen($path, 'r')) !== false) {
		$meta = array();
		$data = array();

		if (($line = fgets($file)) !== false) {
			$line = preg_replace('/[^A-Za-z0-9\s,._-]/i', '', $line);
			$keys = str_getcsv($line);

			array_walk($keys, function(&$value, $index) {
				$value = strtolower(str_replace(' ', '_', $value));
			});

			while (($line = fgets($file)) !== false) {
				$line = preg_replace('/[^A-Za-z0-9\s,._-]/i', '', $line);

				if (!empty($line)) {
					$values = str_getcsv($line);
					$record = array_combine($keys, $values);

					$date = date('Y-m-d', strtotime($record['date']));
					$data[$date] = $record;
				}
			}
		}

		fclose($file);

		return $data;
	}
}

function import_report($report) {
	global $mysql;

	$table_fields = array(
		'daily_device_installs',
		'daily_device_uninstalls',
		'daily_device_upgrades',
		'daily_user_installs',
		'daily_user_uninstalls'
	);

	foreach ($report as $date => $record) {
		$app = get_app($record['package_name']);

		$values = array('app_id' => $app['id'], 'date' => $date);

		foreach ($record as $field => $value) {
			if (in_array($field, $table_fields))
				$values[$field] = intval($value);
		}

		$count = count($values);

		$fields = implode(', ', array_keys($values));
		$spaces = implode(', ', array_fill(0, $count, '?'));
		$values = array_values($values);

		$query = "INSERT INTO overall_installs ($fields) VALUES ($spaces)";

		$stmt = $mysql->prepare($query);

		$types = 'is' . str_repeat('i', $count - 2);

		$params = array();
		$params[] =& $types;

		for ($i = 0; $i < $count; $i++)
			$params[] =& $values[$i];

		call_user_func_array(array($stmt, 'bind_param'), $params);

		$stmt->execute();
		$stmt->close();
	}
}

function get_app($package) {
	global $mysql;

	static $apps = array();

	if (!is_string($package)) {
		echo '<pre>';
		debug_print_backtrace();
		echo '</pre>';
	}

	if (isset($apps[$package]))
		return $apps[$package];

	$stmt = $mysql->prepare("SELECT * FROM apps WHERE package = ?");
	$stmt->bind_param('s', $package);

	if ($stmt->execute()) {
		if ($result = $stmt->get_result()) {
			$record = $result->fetch_assoc();
			$result->free();

			if (empty($record)) {
				$stmt->close();
				$stmt = $mysql->prepare("INSERT INTO apps (package) VALUES (?)");
				$stmt->bind_param('s', $package);

				if ($stmt->execute()) {
					$record = array(
						'id'      => $mysql->insert_id,
						'title'   => '',
						'package' => $package
					);
				}
			}

			$apps[$package] = $record;
		}

		$stmt->close();

		return $record;
	}
}

function get_report($page, $year = CUR_YEAR, $month = CUR_MONTH) {
	global $mysql;

	$count = count($page['packages']);

	$spaces = implode(', ', array_fill(0, $count, '?'));
	$types = str_repeat('s', $count) . 'ii';

	$query = "SELECT `apps`.`package`, `installs`.*
		FROM `apps` INNER JOIN `overall_installs` AS `installs`
		ON `apps`.`id` = `installs`.`app_id`
		WHERE `apps`.`package` IN ($spaces)
		AND YEAR(`installs`.`date`) = ?
		AND MONTH(`installs`.`date`) = ?
		ORDER BY `apps`.`id` ASC, `installs`.`date` DESC";

	$stmt = $mysql->prepare($query);
	echo $mysql->error;

	$params = array();
	$params[] =& $types;

	for ($i = 0; $i < $count; $i++)
		$params[] =& $page['packages'][$i];

	$params[] =& $year;
	$params[] =& $month;

	call_user_func_array(array($stmt, 'bind_param'), $params);

	if ($stmt->execute()) {
		$result = $stmt->get_result();
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$result->free();
	}

	$stmt->close();

	if (!empty($data)) {
		foreach ($data as $record) {
			$package = $record['package'];
			$date    = $record['date'];

			unset($record['package'], $record['app_id'], $record['date']);

			$report[$date][$package] = $record;
		}

		return $report;
	}
}

function serialize_report($report) {
	$titles = array('x');
	$data = array(array());

	$i = 0;

	foreach ($report as $date => $product_data) {
		$j = 1;

		foreach ($product_data as $package => $counts) {
			if ($i == 0) {
				$product = get_app($package);
				$titles[$j] = !empty($product['title']) ? $product['title'] : $product['package'];
			}

			$data[$i][0]  = $date;
			$data[$i][$j] = $counts['daily_user_installs'];

			$j++;
		}

		$i++;
	}

	$data[] = $titles;
	$data = array_reverse($data);

	$json = json_encode($data);

	return $json;
}

function get_country_report($page, $year = CUR_YEAR, $month = CUR_MONTH) {
	global $mysql, $regions;

	$countries = array();

	foreach ($regions as $region) {
		foreach ($region['countries'] AS $country)
			$countries[] = $country;
	}

	sort($countries);

	$package_count = count($page['packages']);
	$country_count = count($countries);

	$package_spaces = implode(', ', array_fill(0, $package_count, '?'));
	$country_spaces = implode(', ', array_fill(0, $country_count, '?'));

	$types = str_repeat('s', $package_count) . str_repeat('s', $country_count) . 'ii';

	$query = "SELECT `country`, `package`,
		YEAR(`date`) AS `year`, MONTH(`date`) AS `month`,
		SUM(`daily_device_installs`) AS `installs`,
		SUM(`daily_device_upgrades`) AS `upgrades`
		FROM `apps` INNER JOIN `country_installs` AS `installs`
		ON `apps`.`id` = `installs`.`app_id`
		WHERE `package` IN ($package_spaces)
		AND `country` IN ($country_spaces)
		GROUP BY `country`, `package`, `year`, `month`
		HAVING `year` = ? AND `month` = ?
		ORDER BY `country` ASC";

	$data = false;

	if ($stmt = $mysql->prepare($query)) {
		$params = array();
		$params[] =& $types;

		for ($i = 0; $i < $package_count; $i++)
			$params[] =& $page['packages'][$i];

		for ($i = 0; $i < $country_count; $i++)
			$params[] =& $countries[$i];

		$params[] =& $year;
		$params[] =& $month;

		call_user_func_array(array($stmt, 'bind_param'), $params);

		if ($stmt->execute() && $result = $stmt->get_result()) {
			$report = array();

			while ($record = $result->fetch_assoc()) {
				$report[$record['country']][$record['package']]['installs'] += $record['installs'];
				$report[$record['country']][$record['package']]['upgrades'] += $record['upgrades'];
			}

			$result->free();

			foreach ($countries as $country) {
				foreach ($page['packages'] as $package) {
					$report[$country][$package]['installs'] += 0;
					$report[$country][$package]['upgrades'] += 0;
				}

				ksort($report[$country]);
			}

			ksort($report);

			return $report;
		}

		$stmt->close();
	} else {
		trigger_error("MySQL Error #$mysql->errno: $mysql->error", E_USER_WARNING);
		echo $query;
		die("MySQL Error #$mysql->errno: $mysql->error");
	}

	return $data;
}
