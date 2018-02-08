<?php

date_default_timezone_set('America/New_York');

if ($argc < 2)
	die("No reporting source specified\n");

$data = array();

if (!is_file($argv[1]))
	die("Invalid reporting source\n");

// Read in .csv file
if (($file = fopen($argv[1], 'r')) !== false) {
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
				$record['date'] = date('Y-m-d', strtotime($record['date']));

				$data[$record['package_name']][] = $record;
			}
		}
	}

	fclose($file);
}

$mysql = new mysqli('127.0.0.1', 'www', 'www', 'play', 3300);

if ($mysql->connect_errno)
	die("MySQL Error #$mysql->connect_errno: $mysql->connect_error\n");

// Prepare insert statement
$app_id = 0;

$date    = '';
$country = '';

$daily_device_installs   = 0;
$daily_device_upgrades   = 0;
$daily_device_uninstalls = 0;
$daily_user_installs     = 0;
$daily_user_uninstalls   = 0;

$sql_insert = 'REPLACE INTO country_installs (
	`app_id`,
	`date`,
	`country`,
	`daily_device_installs`,
	`daily_device_upgrades`,
	`daily_device_uninstalls`,
	`daily_user_installs`,
	`daily_user_uninstalls`
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

if ($stmt_insert = $mysql->prepare($sql_insert)) {
	$bind = $stmt_insert->bind_param(
		'dssddddd', $app_id, $date, $country,
		$daily_device_installs,
		$daily_device_upgrades,
		$daily_device_uninstalls,
		$daily_user_installs,
		$daily_user_uninstalls
	);

	if (!$bind)
		die("MySQL Error #$stmt->errno: $stmt->error\n");
} else {
	die("MySQL Error #$mysql->errno: $mysql->error\n");
}


foreach ($data as $package => $package_data) {
	// Locate package
	if ($stmt = $mysql->prepare('SELECT * FROM `apps` WHERE `package` = ?')) {
		if ($stmt->bind_param('s', $package) && $stmt->execute() && $result = $stmt->get_result()) {
			if ($record = $result->fetch_object()) {
				$app_id = intval($record->id);
				echo "Importing data for $record->title\n";
			} else {
				continue;
			}

			$result->close();
		} else {
			die("MySQL Error #$stmt->errno: $stmt->error\n");
		}

		$stmt->close();
	} else {
		die("MySQL Error #$mysql->errno: $mysql->error\n");
	}

	// Import data for package
	foreach ($package_data as $record) {
		if ('unknown' == $record['country'])
			$record['country'] = '';

		$date    = $record['date'];
		$country = $record['country'];

		$daily_device_installs   = intval($record['daily_device_installs']);
		$daily_device_upgrades   = intval($record['daily_device_upgrades']);
		$daily_device_uninstalls = intval($record['daily_device_uninstalls']);
		$daily_user_installs     = intval($record['daily_user_installs']);
		$daily_user_uninstalls   = intval($record['daily_user_uninstalls']);

		$record_data = compact(
			'app_id',
			'date',
			'country',
			'daily_device_installs',
			'daily_device_upgrades',
			'daily_device_uninstalls',
			'daily_user_installs',
			'daily_user_uninstalls'
		);

		if ($stmt_insert->execute())
			echo implode("\t", $record_data) . "\n";
		else
			echo "MySQL Error #$stmt->errno: $stmt->error\n";
	}
}

$stmt_insert->close();

$mysql->close();
