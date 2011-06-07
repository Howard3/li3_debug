<style type="text/css">
	#li3_debugBar {
		z-index: 100000;
		position: absolute;
		top: 0;
		left: 0;
		background: #000000;
		opacity: 0.85;
	}

	#li3_debugBar th {
		color: #00a8e6;
		padding: 5px 15px 5px 0px;
		text-align: left;
	}
</style>
<div id='li3_debugBar'>
	<table>
		<tr>
			<th>Execution Time</th>
			<th>Runtime</th>
			<th>Memory</th>
			<th>Event</th>
		</tr>
		<?php
		foreach ($timers as $executionTime => $timer) :
		$r = intval(255 * ($timer['runtime'] / $maxRuntime));
		$g = 255 - $r;
		?>
		<tr>
			<td><?=$executionTime?></td>
			<td style='color:rgb(<?=$r?>, <?=$g?>, 0);'><?=$timer['runtime']?></td>
			<td><?=$timer['memory'] / 1024;?> KB</td>
			<td><?=$timer['event'];?></td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>