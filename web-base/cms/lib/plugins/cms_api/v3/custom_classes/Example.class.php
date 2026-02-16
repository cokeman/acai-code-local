<?
class AppConfig {
    static function getTime($request){
        $now = time();
        $app_time = @strtotime($request['iso_datetime'])?:$now;
        return ['timediff' => $now - $app_time];
    }
    static function getLangs() {
        $files = [
            __DIR__ . '/../../../../../data/settings.dat.php',
        ];
        foreach ($files as $file) {
            if(realpath($file)) {
                $file = realpath($file);
                $__SETTINGS = parse_ini_file($file, true);
                $langs = array_filter($__SETTINGS['idiomas'], function($each) {return $each !== '';});
                return ['langs' => $langs];
            } else {
                throw new ApiError($file . " no existe.");
            }
        }
    }
    static function test() {
        API::success(ApiJsonBuilder::generateBaseConfig());
    }
}