<?php
class Config {
    static function get() {
        $db = Db::getInstance();
        return $db->executeS('SELECT * FROM configuracion')[0];
    }
}