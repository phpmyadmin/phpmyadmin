window.addEvent('domready', function() {
	var r = new MooRainbow('myRainbow', {
		'startColor': [58, 142, 246],
		'imgPath': 'js/mooRainbow/images/',
		'onChange': function(color) {
			top.frame_navigation.document.getElementById('body_leftFrame').style.backgroundColor = color.hex;
			top.frame_navigation.document.getElementById('pmalogo').style.backgroundColor = color.hex;
			top.frame_content.document.body.style.backgroundColor = color.hex;
			},
		'onComplete': function(color) {
			top.frame_content.document.getElementById('rainbowform').custom_color.value = color.hex;
			top.frame_content.document.getElementById('rainbowform').custom_color_rgb.value = color.rgb;
			top.frame_content.document.getElementById('rainbowform').submit();
			}
	});
});
