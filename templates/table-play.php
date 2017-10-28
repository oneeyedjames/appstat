<?php

global $report, $page;

?><table class="report-dates <?php echo SOURCE; ?>">
	<thead>
		<tr>
			<th>Date</th>
		</tr>
		<tr>
			<th>&nbsp;<br>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($report as $date => $record) : ?>
			<tr>
				<td><?php echo $date; ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<table class="report <?php echo SOURCE; ?>">
	<thead>
		<tr>
			<?php foreach ($page['packages'] as $package) : $product = get_app($package); ?>
				<th colspan="3"><?php echo !empty($product['title']) ? $product['title'] : $product['package']; ?></th>
			<?php endforeach; ?>
		</tr>
		<tr>
			<?php foreach ($page['packages'] as $package) : ?>
				<th>User<br>Installs</th>
				<th>Device<br>Installs</th>
				<th>Device<br>Upgrades</th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($report as $date => $record) : ?>
			<tr>
				<?php foreach ($record as $package => $data) : ?>
					<td><?php echo $data['daily_user_installs']; ?></td>
					<td><?php echo $data['daily_device_installs']; ?></td>
					<td><?php echo $data['daily_device_upgrades']; ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>