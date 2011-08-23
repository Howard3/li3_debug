$(document).ready(function() {
	$('.callLink').click(function(e) {
		e.preventDefault();
		var src = $(this).attr('href');
		$.modal('<iframe src="' + src + '" height="450" width="830" style="border:0">', {
			closeHTML:"",
			containerCss:{
				backgroundColor:"#fff",
				borderColor:"#fff",
				height:450,
				padding:0,
				width:830
			},
			overlayClose:true
		});
	});
	$('div.callRecord').each(function() {
		var depth = $(this).attr('data-depth');
		var prefix = '';
		var i = 0;
		for (i = 0; i < depth - 1; i++) {
			prefix = prefix + '  |';
		}
		if (prefix.length > 0) {
			prefix = prefix + '-';
		}
		prefix = depth.toString() + ' ' + prefix;
		$(this).prepend(prefix);
	});
});