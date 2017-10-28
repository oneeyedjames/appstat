<?php

global $sources, $year, $month;

foreach ($sources as $source_key => $source_meta) : $is_source = $source_key == SOURCE; ?>
	<h3><?php echo $source_meta['title']; ?></h3>
	<nav class="list">
		<?php foreach ($source_meta['pages'] as $page_key => $page_meta) : $is_page = $page_key == PAGE;
			$class = $is_source && $is_page ? 'active' : ''; ?>
			<a href="<?php echo date_link($year, $month, $page_key, $source_key); ?>"
				class="<?php echo $class; ?>"><?php echo $page_meta['title']; ?></a>
		<?php endforeach; ?>
	</nav>
<?php endforeach; ?>