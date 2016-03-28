<?php

class Database {
	
	private $database;
	private static $instance;
	
	private function __construct() {
		$host = Config::get('databaseHost');
		$database = Config::get('databaseName');
		$username = Config::get('databaseUsername');
		$password = Config::get('databasePassword');
		$this->database = new PDO(
			"mysql:host=$host;dbname=$database;charset=utf8", 
			$username, 
			$password
		);
        $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	private static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new static();
		}
		
		return self::$instance;
	}
	
	public static function insert($table, $params) {
        $columns = implode(', ', array_keys($params));
        $values = implode(', ', array_map(function($element){
            return self::getInstance()->database->quote($element);
        }, array_values($params)));
		$query = "INSERT INTO $table($columns) VALUES ($values);";
		$statement = self::getInstance()->database->prepare($query);
		$statement->execute();
        return self::getInstance()->database->lastInsertId();
	}
	
	public static function update($table, $values, $params) {
        $newval = array();
        $new_values = '';
        $wheres = array();
        $where = '';
        
        foreach ($values as $key => $val) {
            $newval[] = $key . '=' . $val;
        }
        
        if (count($newval) > 0) {
            $new_values = 'SET ' . implode(', ', $newval);
        }
        
        foreach ($params as $key => $val) {
            $wheres[] = $key . '=' . $val;
        }
        
        if (count($wheres) > 0) {
            $where = 'WHERE ' . implode(' AND ', $wheres);
        }
        
		$query = "UPDATE $table $new_values $where;";
		$statement = self::getInstance()->database->prepare($query);
		$statement->execute();
	}
	
	public static function delete($table, $params) {
        $wheres = array();
        $where = '';
        
        foreach ($params as $key => $val) {
            $wheres[] = $key . '=' . $val;
        }
        
        if (count($wheres) > 0) {
            $where = 'WHERE ' . implode(' AND ', $wheres);
        }
        
		$query = "DELETE FROM $table $where;";
		$statement = self::getInstance()->database->prepare($query);
		$statement->execute();
	}
    
	public static function select($table, $params) {
        $wheres = array();
        $where = '';
        
        foreach ($params as $key => $val) {
            $wheres[] = $key . '=' . $val;
        }
        
        if (count($wheres) > 0) {
            $where = 'WHERE ' . implode(' AND ', $wheres);
        }
        
		$query = "SELECT * FROM $table $where;";
		$statement = self::getInstance()->database->prepare($query);
		$statement->execute();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}
    
}