<?php

global $year, $month;

$prev_title = date_title($year, $month - 1);
$next_title = date_title($year, $month + 1);

$prev_link = date_link($year, $month - 1);
$next_link = date_link($year, $month + 1);

?><nav id="dates-nav" class="buttons">
	<a href="<?php echo date_link(CUR_YEAR, CUR_MONTH); ?>">Today</a>
	<?php if ($year > MIN_YEAR || $year == MIN_YEAR && $month > MIN_MONTH) : ?>
		<a href="<?php echo $prev_link; ?>">&larr;</a>
	<?php else : ?>
		<a class="disabled">&larr;</a>
	<?php endif; ?>
	<?php if ($year < CUR_YEAR || $year == CUR_YEAR && $month < CUR_MONTH) : ?>
		<a href="<?php echo $next_link; ?>">&rarr;</a>
	<?php else : ?>
		<a class="disabled">&rarr;</a>
	<?php endif; ?>
	<h3><?php echo date_title($year, $month); ?></h3>
</nav>
