window.addEvent('domready', function(){
	var szNormal = 151, szSmall  = 151, szFull   = 151;
  var menuSelect = "url(/images/menu_selection.png)";
	var kwicks = $$("#kwicks .kwick");
	var fx = new Fx.Elements(kwicks, {wait: false, duration: 200, transition: Fx.Transitions.Back.easeOut});
	document.getElementById("kwick_lunch").style.backgroundImage = menuSelect;
	document.getElementById("lunch_link").style.color = '#000000';
	kwicks.each(function(kwick, i) {
		kwick.addEvent("mouseenter", function(event) {
			var o = {};
			o[i] = {width: [kwick.getStyle("width").toInt(), szFull]}
			kwicks.each(function(other, j) {
				if(i != j) {
					var w = other.getStyle("width").toInt();
					if(w != szSmall) o[j] = {width: [w, szSmall]};
				}
			});
			fx.start(o);
		});
	});

	$("kwicks").addEvent("mouseleave", function(event) {
		var o = {};
		kwicks.each(function(kwick, i) {
			o[i] = {width: [kwick.getStyle("width").toInt(), szNormal]}
		});
		fx.start(o);
	})


	var scroll = new Fx.Scroll('demo-wrapper', {
			wait: false,
			duration: 1000,
			transition: Fx.Transitions.Quad.easeInOut
	});

	$('kwick_lunch').addEvent('click', function(event) {
		event = new Event(event).stop();
		scroll.toElement('content_lunch');
		document.getElementById("lunch_link").style.color = '#000000';
		document.getElementById("dinner_link").style.color = '#fff';
		document.getElementById("sushi_link").style.color = '#fff';
		document.getElementById("bar_link").style.color = '#fff';
		document.getElementById("tatami_link").style.color = '#fff';
	  document.getElementById("kwick_lunch").style.backgroundImage = menuSelect;
		document.getElementById("kwick_dinner").style.backgroundImage = "none"
		document.getElementById("kwick_sushi").style.backgroundImage = "none"
		document.getElementById("kwick_drinks").style.backgroundImage = "none";
		document.getElementById("kwick_tatami").style.backgroundImage = "none"
	});

	$('kwick_dinner').addEvent('click', function(event) {
		event = new Event(event).stop();
		scroll.toElement('content_dinner');
		document.getElementById("lunch_link").style.color = '#fff';
		document.getElementById("dinner_link").style.color = '#000';
		document.getElementById("sushi_link").style.color = '#fff';
		document.getElementById("bar_link").style.color = '#fff';
		document.getElementById("tatami_link").style.color = '#fff';
		document.getElementById("kwick_lunch").style.backgroundImage = "none"
		document.getElementById("kwick_dinner").style.backgroundImage = menuSelect;
		document.getElementById("kwick_sushi").style.backgroundImage = "none"
		document.getElementById("kwick_drinks").style.backgroundImage = "none";
		document.getElementById("kwick_tatami").style.backgroundImage = "none"
	});

	$('kwick_sushi').addEvent('click', function(event) {
		event = new Event(event).stop();
		scroll.toElement('content_sushi');
		document.getElementById("lunch_link").style.color = '#fff';
		document.getElementById("dinner_link").style.color = '#fff';
		document.getElementById("sushi_link").style.color = '#000';
		document.getElementById("bar_link").style.color = '#fff';
		document.getElementById("tatami_link").style.color = '#fff';
		document.getElementById("kwick_lunch").style.backgroundImage = "none"
		document.getElementById("kwick_dinner").style.backgroundImage = "none";
		document.getElementById("kwick_sushi").style.backgroundImage = menuSelect;
		document.getElementById("kwick_drinks").style.backgroundImage = "none";
		document.getElementById("kwick_tatami").style.backgroundImage = "none"
	});

	$('kwick_drinks').addEvent('click', function(event) {
		event = new Event(event).stop();
		scroll.toElement('content_drinks');
		document.getElementById("lunch_link").style.color = '#fff';
		document.getElementById("dinner_link").style.color = '#fff';
		document.getElementById("sushi_link").style.color = '#fff';
		document.getElementById("bar_link").style.color = '#000';
		document.getElementById("tatami_link").style.color = '#fff';
		document.getElementById("kwick_lunch").style.backgroundImage = "none"
		document.getElementById("kwick_dinner").style.backgroundImage = "none";
		document.getElementById("kwick_sushi").style.backgroundImage = "none"
		document.getElementById("kwick_drinks").style.backgroundImage = menuSelect;
		document.getElementById("kwick_tatami").style.backgroundImage = "none"
	});
	$('kwick_tatami').addEvent('click', function(event) {
		event = new Event(event).stop();
		scroll.toElement('content_tatami');
		document.getElementById("lunch_link").style.color = '#fff';
		document.getElementById("dinner_link").style.color = '#fff';
		document.getElementById("sushi_link").style.color = '#fff';
		document.getElementById("bar_link").style.color = '#fff';
		document.getElementById("tatami_link").style.color = '#000';
		document.getElementById("kwick_lunch").style.backgroundImage = "none"
		document.getElementById("kwick_dinner").style.backgroundImage = "none";
		document.getElementById("kwick_sushi").style.backgroundImage = "none"
		document.getElementById("kwick_drinks").style.backgroundImage = "none";
		document.getElementById("kwick_tatami").style.backgroundImage = menuSelect;
		
	});
	
	var kwords = $$("#kwords .kword");
	kwords.each(function(kw, i) {
		var newsize = Math.floor(Math.random()*20)+15;
		kw.effect('font-size').set(newsize);
	});
		
});