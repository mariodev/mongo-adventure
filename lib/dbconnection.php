<?php
class DBConnection {
	const HOST = 'localhost';
	const PORT = 27017;
	const DBNAME = 'acmeproducts_mongo';

	private static $instance;
	public $connection;
	public $database;
	
	private function __construct() {
		$conn = sprintf('mongodb://%s:%d', DBConnection::HOST, DBConnection::PORT);
		try {
			$this->connection = new Mongo($conn);
			$this->database = $this->connection->selectDB(DBConnection::DBNAME);
		} catch(MongoConnectionException $e) {
			throw $e;
		}
	}

	static public function init() {
		if(!isset(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class;
		}
		return self::$instance;
	}

	public function getCollection($name) {
		return $this->database->selectCollection($name);
	}
}