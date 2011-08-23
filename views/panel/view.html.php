<?php echo $this->Li3DebugHtml->script(array('view', 'simpleModal'), array('inline' => false)); ?>
<?php echo $this->Li3DebugHtml->style(array('view', 'simpleModal'), array('inline' => false)); ?>
<div id="simpleModal"></div>
<?php foreach($records as $record): ?>
<div data-depth="<?=$record->depth?>" class='callRecord'>
	<?=$this->html->link($record->method, array(
			'panel::call', 'id' => $record->_id, 'type' => 'li3d-ajax'
		),
		array('class' => 'callLink')
	)?>
	<span class='small'>
		called at: <?=round($record->call_time - $record->start, 5)?> -
		runtime: <?=round($record->return_time - $record->call_time, 5)?>
	</span>
</div>
<?php endforeach; ?>