<?
  require_once "../../sesion.php";
require_once "ServiceWorker.php";

$sw = new ServiceWorker(array(
  'template/estandar/js/mis-scripts.js',
'template/estandar/js/bootstrap.min.js',
'template/estandar/js/jquery.bxslider.min.js',
'template/estandar/js/jquery.swipebox.min.js',
'template/estandar/js/bootstrap-select.min.js',
'template/estandar/js/wow.min.js',
'template/estandar/js/sw-controller.js',
'template/estandar/style.css',
'template/estandar/style-base.css',
'template/estandar/css/bootstrap.min.css',
'template/estandar/css/swipebox.min.css',
'template/estandar/css/jquery.bxslider.css',
'template/estandar/css/animate.css',
'template/estandar/css/bootstrap-select.min.css',
));

header('Content-Type: application/javascript');
?>

  var staticCacheName = "<?=$sw->getCacheName();?>-<?=$sw->getVersion();?>";

self.addEventListener("install", function(event) {
  event.waitUntil(
    caches.open(staticCacheName).then(function(cache) {
      return cache.addAll([
        "/",
        "/index.php",
        "https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css",
        <? foreach ($sw->getFiles() as $file):?>
        "<?=$file;?>",
        <? endforeach;?>
      ]);
    })
  );
});

self.addEventListener("activate", function(event) {
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.filter(function(cacheName) {
          return cacheName.startsWith("<?=$sw->getCacheName();?>-") &&
            cacheName != staticCacheName;
        }).map(function(cacheName) {
          return caches.delete(cacheName);
        })
      );
    })
  );
});

self.addEventListener("fetch", function(event) {
  let url = new URL(event.request.url);
  event.respondWith(
    caches.match(url.pathname, { cacheName: staticCacheName }).then(function(response) {
      return response || fetch(event.request.clone());
    })
  );
});

self.addEventListener("message", function(event) {
  if (event.data.action === "skipWaiting") {
    self.skipWaiting();
  }
});