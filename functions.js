function parseString(str) {
	var pairs = str.split(';');

	var obj = {};

	for (index in pairs) {
		var pair = pairs[index].trim().split('=');

		if (pair[0] != '')
			obj[pair[0]] = pair[1];
	}

	return obj;
}

function buildString(obj) {
	var pairs = [];

	for (key in obj)
		pairs.push([key, obj[key]].join('='));

	return pairs.join(';');
}

function getCookie(key, defValue) {
	if (typeof getCookie.cookies == 'undefined') {
		console.log(parseString(document.cookie));
		getCookie.cookies = parseString(document.cookie);
	}

	var value = getCookie.cookies[key];

	return typeof value != 'undefined' ? value : defValue;
}

function setCookie(key, value, expire) {
	var cookie = {};

	cookie[key] = value;

	if (expire)
		cookie.expires = new Date(Date.now() + expire).toGMTString();

	cookie.path = '/';

	console.log(buildString(cookie));
	document.cookie = buildString(cookie);
}

function delCookie(key) {
	setCookie(key, '', -3600000);
}

function selectTab(container, key) {
	setCookie('tab', key);
	activateElement(container, key, 'tab');
	activateElement(document.getElementById(container).parentElement, key, 'panel');
}

function activateElement(container, key, type) {
	if (typeof container === 'string')
		container = document.getElementById(container);

	var elements = container.children;

	for (var i = 0, n = elements.length; i < n; i++) {
		if (elements[i].id.endsWith('-' + type)) {
			if (elements[i].id == [key, type].join('-'))
				elements[i].classList.add('active');
			else
				elements[i].classList.remove('active');
		}
	}
}

if (typeof String.prototype.endsWith !== 'function') {
	String.prototype.endsWith = function(suffix) {
		return this.indexOf(suffix, this.length - suffix.length) !== -1;
	};
}