<?php
require_once('dbconnection.php');

class SessionManager {
	const COLLECTION = 'sessions';
	const SESSION_TIMEOUT = 600;
	const SESSION_LIFESPAN = 3600;
	const SESSION_NAME = 'mongosessid';
	const SESSION_COOKIE_PATH = '/';
	const SESSION_COOKIE_DOMAIN = '';

	private $_mongo;
	private $_collection;
	private $_currentSession;

	public function __construct() {
		$this->_mongo = DBConnection::init();
		$this->_collection = $this->_mongo->getCollection(
			SessionManager::COLLECTION
		);
		session_set_save_handler(
			array(&$this, 'open'),
			array(&$this, 'close'),
			array(&$this, 'read'),
			array(&$this, 'write'),
			array(&$this, 'destroy'),
			array(&$this, 'gc')
		);

		ini_set('session.gc_maxlifetime', SessionManager::SESSION_LIFESPAN);
		session_set_cookie_params(
			SessionManager::SESSION_LIFESPAN,
			SessionManager::SESSION_COOKIE_PATH,
			SessionManager::SESSION_COOKIE_DOMAIN
		);
		session_name(SessionManager::SESSION_NAME);
		session_cache_limiter('nocache');
		session_start();
	}

	public function open($path, $name) {
		return true;
	}

	public function close() {
		return true;
	}

	public function read($session_id) {
		$query = array(
			'session_id' => $session_id,
			'timeout_at' => array('$gte' => time()),
			'expired_at' => array(
				'$gte' => time() - SessionManager::SESSION_LIFESPAN
			)
		);
		$result = $this->_collection->findOne($query);
		$this->_currentSession = $result;
		if(!isset($result['data'])) {
			return '';
		}
		return $result['data'];
	}

	public function write($session_id, $data) {
		$expired_at = time() + self::SESSION_TIMEOUT;
		$new_obj = array(
			'data' => $data,
			'timeout_at' => time() + self::SESSION_TIMEOUT,
			'expired_at' => (empty($this->_currentSession)) ?
				time() + SessionManager::SESSION_LIFESPAN : $this->_currentSession['expired_at']
		);

		$query = array('session_id' => $session_id);
		$this->_collection->update(
			$query,
			array('$set' => $new_obj),
			array('upsert' => true)
		);
		return True;
	}

	public function destroy($session_id) {
		$this->_collection->remove(array('session_id' => $session_id));
		return True;
	}

	public function gc() {
		$query = array('expired_at' => array('$lt' => time()));
		$this->_collection->remove($query);
		return True;
	}

	public function __destruct() {
		session_write_close();
	}
}

$session = new SessionManager();