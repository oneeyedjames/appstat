<?php

global $report, $page;

?><table class="report-dates <?php echo SOURCE; ?>">
	<thead>
		<tr>
			<th>Date</th>
		</tr>
		<tr>
			<th>&nbsp;</th>
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
			<?php foreach ($page['skus'] as $sku) : $product = get_app($sku); ?>
				<th colspan="2"><?php echo $product['title']; ?></th>
			<?php endforeach; ?>
		</tr>
		<tr>
			<?php foreach ($page['skus'] as $sku) : ?>
				<th>Installs</th>
				<th>Upgrades</th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($report as $date => $record) : ?>
			<tr>
				<?php foreach ($page['skus'] as $sku) : ?>
					<td><?php echo $record[$sku]['installs']; ?></td>
					<td><?php echo $record[$sku]['upgrades']; ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>