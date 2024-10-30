var xml = make_xml();

function $ (id) { return document.getElementById(id); }
function load_handler (e) {
	parent = $('__is_human_inline');
	child = document.createElement('a');
	child.href = '#';
	child.onclick = function () {
		xml.open('get', root + '/engine.php?action=captcha-reload');
		xml.onreadystatechange = function () {
			if (xml.readyState == 4) {
				$('__is_human_inline').innerHTML = xml.responseText;
				load_handler();
			} else {
				$('__is_human_loading').innerHTML = 'Loading...';	
			}
		}
		xml.send(null);
		return false;
	}
	child.style.backgroundImage = 'url(' + root + '/img/reload.gif)';
	child.style.backgroundPosition = 'top left';
	child.style.backgroundRepeat = 'no-repeat';
	child.style.height = '10px';
	child.style.display = 'block';
	child.style.margin = '4px 0px 8px 0px';
	child.style.padding = '0px 0px 6px 20px';
	child.id = '__is_human_loading';
	child.innerHTML = reload_text;
	child.title = reload_tooltip_text;
	parent.appendChild(child);
}
function make_xml () {
	if (typeof XMLHttpRequest == 'undefined') {
		objects = Array(
			'Microsoft.XMLHTTP',
			'MSXML2.XMLHTTP',
			'MSXML2.XMLHTTP.3.0',
			'MSXML2.XMLHTTP.4.0',
			'MSXML2.XMLHTTP.5.0'
		);
		for (i in objects) {
			try {
				return new ActiveXObject(objects[i]);
			} catch (e) {}
		}
	} else {
		return new XMLHttpRequest();
	}
}
window.onload = load_handler;