<?php
require_once('dbconnection.php');
require_once('session.php');

class User {
	const COLLECTION = 'users';
	private $_mongo;
	private $_collection;
	private $_user;
	private $_app;

	public function __construct($app) {
		$this->_mongo = DBConnection::init();
		$this->_collection = $this->_mongo->getCollection(User::COLLECTION);
		$this->_app = $app;
		if($this->isLoggedIn()) $this->_loadData();
	}

	public function isLoggedIn() {
		return isset($_SESSION['user_id']);
	}

	public function authenticate($username, $password) {
		$query = array(
			'username' => $username,
			'password' => md5($password)
		);

		$this->_user = $this->_collection->findOne($query);
		if(empty($this->_user)) {
			return False;
		}
		// die(var_dump($this->_app->request()->getUserAgent()));
		$_SESSION['user_id'] = (string) $this->_user['_id'];
		return True;
	}

	public function logout() {
		unset($_SESSION['user_id']);
	}

	public function getId() {
		// return (string) $this->_id;
		return $this->_user;

	}

	public function __get($attr) {
		if(empty($this->_user)) return null;
		switch($attr) {
			case 'address':
				$address = $this->_user['address'];
				return sprintf('Town: %s, Planet: %s', $address['town'], $address['planet']);
			case 'town':
				return $this->_user['address']['town'];
			case 'planet':
				return $this->_user['address']['planet'];
			case 'password':
				return NULL;
			case '_id':
				return (string) $this->_user['_id'];
			default:
				return (isset($this->_user[$attr])) ? $this->_user[$attr] : NULL;
		}
	}

	public function __isset($attr) {
        if ('_id' == $attr) {
            return true;
        }

        return false;
    }

	private function _loadData() {
		$id = new MongoId($_SESSION['user_id']);
		$this->_user = $this->_collection->findOne(array('_id' => $id));
	}

	public function allow($role, $params = array()) {
		switch($role) {
			case 'edit.article':
				$article = DBConnection::init()
					->getCollection('articles')
					->findOne(array('_id' => new MongoId($params['id']), 'author_id' => new MongoId($user->_id)));
				break;
		}
	}
}
