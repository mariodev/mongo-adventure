<?php
require_once 'dbconnection.php';

class Logger {
	const COLLECTION = 'access_log';
	private $_collection;
	private $_db;
	private $_app;

	public function __construct($app) {
		$this->_app = $app;
		$this->_db = DBConnection::init();
		$this->_collection = $this->_db->getCollection(Logger::COLLECTION);
	}

	public function logRequest($data = array()) {
		$route = $this->_app->router()->getCurrentRoute();
		$req = $this->_app->request();

		$request = $this->insertParams($route);
		$request['page'] = $route->getPattern();
		$request['method'] = $req->getMethod();
		$request['viewed_at'] = new MongoDate($_SERVER['REQUEST_TIME']);
		$request['ip_address'] = $req->getIp();
		$request['user_agent'] = $req->getUserAgent();

		if (!empty($data)) {
			$request = array_merge($request, $data);
		}
		$this->_collection->insert($request);
	}

	public function insertParams($route) {
		$params = $route->getParams();
		$params = array_merge($params, $this->_app->request()->params());
		if($params) {
			return array('query_params' => $params);
		}
		return array();
	}
}