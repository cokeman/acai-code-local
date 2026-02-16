<?php
class Db {
    private $host; // Db Host
    private $user; // Db User
    private $password; // Db Password
    private $db; // Db Name
    private $link; // MySQLi Link
    
    private static $instance = false; // Db Class Instance
    
    /**
     * @param host: DB Host
     * @param user: DB user
     * @param password: DB Password
     * @param db: DB Name
     */
    public function __construct($host, $user, $password, $db) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->db = $db;
        $this->link = $this->connect();
    }

    /**
     * @param host: DB Host
     * @param user: DB user
     * @param password: DB Password
     * @param db: DB Name
     */
    public static function getInstance($host='', $user='', $password='', $db='') {
        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = new Db($host, $user, $password, $db);
        return self::$instance;
    }

    public function escape($str) {
        return $this->link->quote($str);
    }

    /**
     * Connects to the database. Returns HTTP Error Code 500 in case of error
     */
    private function connect() {
        $dsn = "mysql:host=".$this->host.";dbname=".$this->db.";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"'
        ];
        try {
            $link = new PDO($dsn, $this->user, $this->password, $options);
            return $link;
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
        return false;
    }

    /**
     * Executes a SQL Query
     * 
     * @param sql: SQL Query
     * @param params: List of variables to be binded
     * 
     * @return bool
     */
    public function execute($sql, $params = null) {
        if ($params) {
            $stmt = $this->link->prepare($sql);
            try {
                $stmt->execute($params);
            }
            catch(Exception $e) {
                return false;
            }
        }
        else {
            $stmt = $this->link->query($sql);
        }
        return $stmt->rowCount();
    }

    /**
     * Executes a SQL Select Query
     * 
     * @param sql: SQL Select Query
     * @param params: List of variables to be binded
     * @param indexed: If set to true, returns the indexed array instead of associative
     * 
     * @return array
     */
    public function executeS($sql, $params = null, $indexed = false) {
        if ($params) {
            $stmt = $this->link->prepare($sql);
            $stmt->execute($params);
        }
        else {
            $stmt = $this->link->query($sql);
        }
        if ($indexed) {
            $result = $stmt->fetchAll(PDO::FETCH_ARRAY);
        }
        else {
            $result = $stmt->fetchAll();
        }
        
        return $result;
    }
    /**
     * Executes a SQL Select Query of one element
     * 
     * @param sql: SQL Select Query
     * @param params: List of variables to be binded
     * @param indexed: If set to true, returns the indexed array instead of associative
     * 
     * @return array
     */
    public function fetch($sql, $params = null, $indexed = false) {
        $result = $this->executeS($sql, $params, $indexed);
        return empty($result) ? null : $result[0];
    }

    public function foundRows() {
        $foundRows = $this->link->query('SELECT FOUND_ROWS()')->fetchColumn();
        return $foundRows;
    }
}