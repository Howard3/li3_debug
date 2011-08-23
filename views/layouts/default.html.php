<!doctype html>
<html>
<head>
	<?php echo $this->html->charset();?>
	<title>Application > <?php echo $this->title(); ?></title>
	<?php echo $this->styles(); ?>
	<?php echo $this->Li3DebugHtml->script(array('jquery-1.6.2.min.js')); ?>
	<?php echo $this->scripts(); ?>
</head>
<body class="app">
<div id="container">
	<div id="header">
		<h1>Li3 Debugger Panel</h1>
	</div>
	<div id="content">
		<?php echo $this->content(); ?>
	</div>
</div>
</body>
</html>