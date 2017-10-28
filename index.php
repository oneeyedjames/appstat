<?php

require_once "config.php";
require_once 'includes/functions.php';

import('functions');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	foreach (@$_FILES['report']['tmp_name'] as $index => $tmp_name) {
		$name = $_FILES['report']['name'][$index];

		if ($report = read_report($tmp_name, $name))
			import_report($report);

		if (is_file($tmp_name))
			move_uploaded_file($tmp_name, 'reports/' . SOURCE . "/$name" );
	}

	header('Location: ' . $_SERVER['REQUEST_URI']);

	exit;
}

list($year, $month) = get_request_date();

$report = get_report($page, $year, $month);

$json = serialize_report($report);

header('Content-type: text/html; charset=utf8');

?><!DOCTYPE html>
<html>
	<head>
		<base href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/">
		<title><?php echo $page['title']; ?> &bull; <?php echo $source['title']; ?></title>
		<link rel="stylesheet" type="text/css" href="styles.css">
		<script type="text/javascript" src="functions.js"></script>
	</head>
	<body>
		<header id="page-header">
			<hgroup>
				<div id="page-logo">&Sigma;</div>
				<h1><a href="/">App Stat Reporting</a></h1>
				<form method="POST" enctype="multipart/form-data">
					<label for="form-report">Upload Report</label>
					<input type="file" name="report[]" id="form-report" multiple="multiple">
					<input type="submit" value="Go">
				</form>
			</hgroup>
		</header>
		<div id="page-wrapper">
			<article id="content">
				<?php load_template('nav','dates'); ?>
				<nav id="view-tabs" class="tabs">
					<?php foreach (array('chart', 'table', 'country') as $key) :
						$url = "javascript:selectTab('view-tabs', '$key');";
						$class = $key == $_COOKIE['tab'] ? 'tab active' : 'tab'; ?>
						<a href="<?php echo $url; ?>" id="<?php echo $key; ?>-tab"
							class="<?php echo $class; ?>"><?php echo ucfirst($key); ?></a>
					<?php endforeach; ?>
				</nav>
				<?php foreach (array('chart', 'table', 'country') as $key) :
					$class = $key == $_COOKIE['tab'] ? 'panel active' : 'panel'; ?>
					<section id="<?php echo $key; ?>-panel" class="<?php echo $class; ?>">
						<?php load_template($key); ?>
					</section>
				<?php endforeach; ?>
			</article>
			<aside id="sidebar">
				<?php load_template('nav', 'sources'); ?>
			</aside>
		</div>
	</body>
</html>
