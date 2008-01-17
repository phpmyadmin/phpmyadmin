window.addEvent('domready', function(){
	var divs = $$(['docs', 'js', 'html', 'css']);
	divs.each(function(div){
		var link = $(div.id + 'code');
		div.setStyle('display', 'none');
		link.addEvent('click', function(e){
			e = new Event(e);
			divs.each(function(other){
				if (other != div) other.setStyle('display', 'none');
			});
			div.setStyle('display', (div.getStyle('display') == 'block') ? 'none' : 'block');
			e.stop();
		});
	});
});