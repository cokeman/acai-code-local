var FunctionList = function() {
	this._always = [];
	this._stack = [];
}

FunctionList.prototype.add = function(f) {
	if (!f || !(f instanceof Function)) return;
	this._stack.push(f);
}
FunctionList.prototype.call = function() {
	for (var i = 0; i < this._always.length; i++) {
		this._always[i]();
	}
	for (var i = 0; i < this._stack.length; i++) {
		this._stack[i]();
	}
}
FunctionList.prototype.always = function(f) {
	if (!f || !(f instanceof Function)) return;
	this._always.push(f);
}
FunctionList.prototype.clear = function() {
	this._stack.length = 0;
}

window.__main = new FunctionList();

/******/

function removeEventListeners(el) {
    elClone = el.cloneNode(true);
	el.parentNode.replaceChild(elClone, el);
	return elClone;
}
var _blacklist = ['tel', '#', 'mailto', 'javascript'];
var firstTime = true;
var TRANSITION_DURATION = 0.4;

function bindLinks() {
	if (!firstTime) {
		$(window).off('scroll');
		$(document).unbind('click');
	}
	var _links = document.querySelectorAll('a');
	Array.from(_links).forEach(function(link) {
		if (!firstTime) {
			link = removeEventListeners(link);
		}
		if (validLink(link)) {
			link.removeEventListener('click', bindLink);
			link.addEventListener('click', bindLink);
		}
	});
	if (firstTime) {
		firstTime = false;
	}
}

bindLinks();

function validLink(link) {
	var href = link.hash || link.getAttribute('href');
	if (!href) return false;
	var target = link.target || link.getAttribute('target');

	for (var i = 0; i < _blacklist.length; i++) {
		if (href.startsWith(_blacklist[i])) {
			return false;
		}
	}

	if (href.startsWith('http') && !href.includes(window.location.hostname) || target || link.getAttribute('data-rel')) {
		return false;
	}
	return true;
}

function bindLink(e) {
	var menuObject = this;
	var href = this.hash || this.getAttribute('href');
	e.preventDefault();
	sendRequest(menuObject, href);
}

function sendRequest(menuObject, href, fromPop) {
	
	var mainTagAux = document.querySelector('main');
	if (mainTagAux) {
		mainTagAux.style.transition = 'all ' + TRANSITION_DURATION + 's';
		mainTagAux.style.opacity = 0;
		mainTagAux.style.transform = 'scale(0.9)';
		mainTagAux.style.transformOrigin = 'top center';
	}
	
	var request = new XMLHttpRequest();
	request.open('GET', href, true);
	
	if (typeof window.__main !== 'undefined') {
		window.__main.clear();
	}

	request.onload = function() {
		if (request.status >= 200 && request.status < 400) {
			var data = request.responseText;
			var parser = new DOMParser();
			var doc = parser.parseFromString(data, 'text/html');
			var mainTag = doc.querySelector('main');
			var bodyTag = doc.querySelector('body');
			var titleTag = doc.querySelector('title');

			if (titleTag) {
				document.title = titleTag.innerHTML;
			}

			if (!mainTag) {
				window.location.href = href;
				return;
			}

			var navbar = _checkIfLinkBelongsToMenu(menuObject);

			if (navbar) {
				var active = navbar.querySelector('.active');
				if (active) active.classList.remove('active');
				menuObject.parentNode.classList.add('active');
			}
			
			if (!fromPop) history.pushState({id: Date.now(), link: href}, null, href);
			
			if (bodyTag) {
				document.body.classList = bodyTag.classList;
			}
			
			var mainContainer = document.querySelector('main');
			if (mainContainer) {
				mainContainer.parentNode.replaceChild(mainTag, mainContainer);
				mainTag.setAttribute('style', mainContainer.getAttribute('style'));
				setTimeout(function() {
					mainTag.style.opacity = 1;
					mainTag.style.transform = 'scale(1)';
					setTimeout(function() {
						mainTag.removeAttribute('style');
					}, TRANSITION_DURATION*1000);
				}, 100);
				bindLinks();
			}
			
			
			if (typeof window.__main !== 'undefined') {
				var scripts = mainTag.querySelectorAll('script');
				if (scripts) {
					scripts.forEach(function(script) {
						if (!script.src) {
							window.__main.add(function() {
								eval(script.innerHTML);
							});
						}
					});
				}
				window.__main.call();
			}
			
			var scripts = document.querySelectorAll('script[src]');
			if (scripts) {
				scripts.forEach(function(script) {
					var cloned = document.createElement('script');
					cloned.defer = script.defer;
					cloned.async = script.async;
					var cache = script.getAttribute('data-cache');
					if (cache !== null && cache !== undefined) {
						return true;
					}
					cloned.src = script.src.replace(/([?&]?)(t=)([\d]+)/, '');
					if (cloned.src.includes('?')) {
						cloned.src += '&t=' + Date.now();
					}else{
						cloned.src += '?t=' + Date.now();
					}
					var parent = script.parentNode;
					script.parentNode.removeChild(script);
					parent.appendChild(cloned);
				});
			}
			
			window.scrollTo(0,0);
			var hamburger = document.querySelector('.navbar-toggle[aria-expanded="true"]');
			if (hamburger) hamburger.click();
		} else {
			window.location.href = href;
		}

	};

	request.onerror = function() {
		window.location.href = href;
	};

	request.send();
}

function _checkIfLinkBelongsToMenu(link) {
	if (!link) return false;
	var tag = link;
	while (tag && tag.tagName.toLowerCase() !== 'body') {
		tag = tag.parentNode;
	}
	return tag;
}

window.addEventListener('popstate', function(e) {
	console.log(e);
	//window.location.reload();
	if (!e.state || !e.state.link){
		window.location.reload();
		return;
	}
	sendRequest(null, e.state.link, true);
});