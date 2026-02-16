<?
require_once "../../sesion.php";
require_once "../../funciones.php";
header('Content-Type: application/javascript');

function modulob64($mod, $d = array()) {
	extract($d);
	ob_start();
	require($_SERVER['DOCUMENT_ROOT']."/template/estandar/modulos/".$mod.".tpl");
	$resultado = ob_get_clean();
	return base64_encode($resultado);
}
?>
function _registerServiceWorker() {
	var params = getParams();
	if (Boolean(params.swu)) {
		document.body.insertAdjacentHTML( 'beforeend', window.atob('<?=modulob64("sw");?>'));
	}
	if (!navigator.serviceWorker) return;
	navigator.serviceWorker.register('/sw.js', {scope: '/'}).then(function(reg) {
		if (!navigator.serviceWorker.controller) {
			return;
		}

		if (reg.waiting) {
			_updateReady(reg.waiting);
			return;
		}

		if (reg.installing) {
			_trackInstalling(reg.installing);
			return;
		}

		reg.addEventListener('updatefound', function() {
			_trackInstalling(reg.installing);
		});
	});

	// Ensure refresh is only called once.
	// This works around a bug in "force update on reload".
	var refreshing;
	navigator.serviceWorker.addEventListener('controllerchange', function() {
		if (refreshing) return;
		var url = window.location.href;
		if (url.indexOf('swu=1') < 0) {
			if (url.indexOf('?') > -1){
			   url += '&swu=1'
			}else{
			   url += '?swu=1'
			}
		}
		window.location.href = url;
		refreshing = true;
	});
};

_registerServiceWorker();

function _trackInstalling(worker) {
	worker.addEventListener('statechange', function() {
		if (worker.state == 'installed') {
			_updateReady(worker);
		}
	});
};

function _updateReady(worker) {
	worker.postMessage({action: 'skipWaiting'});
};

function getParams() {
	var index = window.location.href.indexOf('?');
	if (index < 0) return {};
	paramStr = window.location.href.substring(index+1, window.location.href.length);
	paramArray = paramStr.split('&');
	params = {};
	for(var i in paramArray){
		var p = paramArray[i].split('=');
		params[p[0]] = p[1];
	}
	return params;
}