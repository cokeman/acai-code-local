<?
class Module {
	public static $css = array();
	public static $cssHash = array();
	public static $js = array();
	public static $jsHash = array();
	public static $loaded = array();
	public static $path = './'.PLANTILLA.'/modulos/';
	
	public static function load($folder, $params) {
		$folderAbs = Module::$path.$folder;
		if (!isset(Module::$loaded[$folder])) {
			$files = scandir($folderAbs);

			// Los dos primeros elementos son ../ y ./
			array_splice($files, 0, 2);

			foreach ($files as $file):
			$ext = pathinfo($folderAbs.'/'.$file, PATHINFO_EXTENSION);
			switch ($ext) {
				case 'css':
					$md5File = md5_file($folderAbs.'/'.$file);
					
					if (!in_array($md5File,Module::$cssHash)) {
						Module::$css[] = '/modulos/'.$folder.'/'.$file;
						Module::$cssHash[] = $md5File;
					}
					break;
				case 'js':
					$md5File = md5_file($folderAbs.'/'.$file);
					if (!in_array($md5File,Module::$jsHash)) {
						Module::$js[] = '/modulos/'.$folder.'/'.$file;
						Module::$jsHash[] = $md5File;
					}
					
					break;
				default:
					break;
			}
			endforeach;
			Module::$loaded[$folder] = true;
		}
		
		extract($params);
		ob_start();
		require($folderAbs.'/index.tpl');
		$resultado = ob_get_clean();
		return $resultado;
	}
	
	
	/**
	 * Devuelve una string con todos los link que ha sacado de los módulos
	 */
	public static function links() {
		Module::$css = array_unique(Module::$css);
		$links=''; if (defined('USE_MIN_TAILWIND') && USE_MIN_TAILWIND) return '';
		foreach (Module::$css as $c):
		$links .= '<link rel="stylesheet" href="'.h($c).'">';
		endforeach;
		return $links;
	}
	
	/**
	 * Devuelve una string con todos los scripts que ha sacado de los módulos
	 */
	public static function scripts() {
		Module::$js = array_unique(Module::$js);
		$scripts=''; if (defined('USE_MIN_JS_TAILWIND') && USE_MIN_JS_TAILWIND) return '';
		foreach (Module::$js as $s):
		$scripts .= '<script src="'.h($s).'"></script>';
		endforeach;
		return $scripts;
	}
}
?>