<table>
	<tr>
		<th>Time</th>
		<th>Page</th>
	</tr>
<?php foreach ($records as $record) : ?>
	<tr>
		<td><?=date("F j, Y, g:i a", $record->call_time)?></td>
		<td>
			<?=$this->html->link($record->method, array(
				'panel::view', 'id' => $record->_id
			))?>
		</td>
	</tr>
<?php endforeach; ?>
</table>