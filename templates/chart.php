<?php

global $json, $page;

?><script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
	google.load('visualization', '1.0', { packages: ['corechart'] });
	google.setOnLoadCallback(function() {
		var tabbar = document.getElementById('view-tabs');

		var chart = document.getElementById('chart');
		chart.data = google.visualization.arrayToDataTable(<?php echo $json; ?>);
		chart.type = getCookie('type', 'column');
		chart.options = {
			width: tabbar.offsetWidth,
			height: window.innerHeight - tabbar.offsetTop - tabbar.offsetHeight - 60,
			title: "<?php echo $page['title']; ?>",
			titleTextStyle: { fontSize: 36 },
			hAxis: { title: "Date", titleTextStyle: { fontSize: 24 }, slantedText: true },
			vAxis: { title: "Downloads", titleTextStyle: { fontSize: 24 }, minValue: 0 },
			isStacked: getCookie('stacked', 'true')
		};

		chart.draw = function() {
			var gChart, options = {};

			for (var key in chart.options)
				options[key] = chart.options[key];

			if (chart.type == 'column') {
				gChart = new google.visualization.ColumnChart(chart);
			} else if (chart.type == 'area') {
				gChart = new google.visualization.AreaChart(chart);
			} else if (chart.type == 'line') {
				gChart = new google.visualization.LineChart(chart);
				options.isStacked = 'false';
			}

			gChart.draw(chart.data, options);

			if (typeof chart.onDrawCallback == 'function')
				chart.onDrawCallback(chart.type, options);
		};

		chart.onDraw = function(callback) {
			if (typeof callback == 'function')
				chart.onDrawCallback = callback;
		};

		chart.setType = function(type) {
			if (['column', 'area', 'line'].indexOf(type) >= 0)
				setCookie('type', chart.type = type);

			chart.draw();
		};

		chart.setStacked = function(stacked) {
			chart.options.isStacked = !!stacked ? 'true' : 'false';
			setCookie('stacked', chart.options.isStacked);

			chart.draw();
		};

		chart.onDraw(function(type, options) {
			var stacked = options.isStacked == 'true' ? 'stacked' : 'unstacked';

			activateElement('chart-type', type, 'button');
			activateElement('chart-stacked', stacked, 'button');
		});

		chart.draw('column', true);
	});
</script>
<aside id="chart-options">
	<nav id="chart-type" class="pill">
		<a href="javascript:chart.setType('column');" id="column-button">Column</a>
		<a href="javascript:chart.setType('area');" id="area-button">Area</a>
		<a href="javascript:chart.setType('line');" id="line-button">Line</a>
	</nav>
	<nav id="chart-stacked" class="pill">
		<a href="javascript:chart.setStacked(true);" id="stacked-button">Stacked</a>
		<a href="javascript:chart.setStacked(false);" id="unstacked-button">Unstacked</a>
	</nav>
</aside>
<div id="chart"></div>