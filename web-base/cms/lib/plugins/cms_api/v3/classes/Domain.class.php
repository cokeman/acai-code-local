<?
class Domain {
    static function parse($domain) {
        $domain = self::trim($domain);
        $domain = preg_replace('/([\w-_]+)\.([\w-_]+)\.([\w-_]+)/', '$2.$3', $domain);
        return strtolower(explode('/', $domain)[0]);
    }

    static function subdomain($domain) {
        $domain = self::trim($domain);
        $subdomain = preg_replace('/([\w-_]+)\.([\w-_]+)\.([\w-_]+)/', '$1', $domain);
        if ($subdomain === $domain) {
            $subfolder = array_filter(explode('/', $domain));
            if (count($subfolder) === 2) {
                return self::parse($subfolder[1]);
            }
            return 'www';
        }
        return self::parse($subdomain);
    }

    static function trim($domain) {
        $domain = str_replace("http://", "", $domain);
        $domain = str_replace("https://", "", $domain);
        $domain = str_replace("www.", "", $domain);
        $domain = trim(trim($domain), '/');
        return $domain;
    }
}