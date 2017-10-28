<?php

global $page, $year, $month;

$report = get_country_report($page, $year, $month);

?><table class="report <?php echo SOURCE; ?>">
	<thead>
		<tr>
			<th>Country</th>
			<?php foreach ($page['packages'] as $package) : $product = get_app($package); ?>
				<th colspan="2"><?php echo $product['title']; ?></th>
			<?php endforeach; ?>
		</tr>
		<tr>
			<th>&nbsp;</th>
			<?php foreach ($page['packages'] as $package) : ?>
				<th>Installs</th>
				<th>Upgrades</th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($report as $country => $record) : ?>
			<tr>
				<td><?php echo $country; ?></td>
				<?php foreach ($page['packages'] as $package) : ?>
					<td><?php echo $record[$package]['installs']; ?></td>
					<td><?php echo $record[$package]['upgrades']; ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
