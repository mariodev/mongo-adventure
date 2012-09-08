<?php
require_once 'mysql.php';
require_once 'dbconnection.php';

class Customer {
	private $_mysql;
	private $_mongodb;
	public $_collection;
	private $_table;
	private $_id;
	private $_first_name;
	private $_last_name;
	private $_email;
	private $_date_of_birth;
	private $_created_at;

	public function __construct($id = null) {
		$this->_mysql = db::get_instance();
		$this->_mongodb = DBConnection::init();
		$this->_collection = $this->_mongodb->getCollection('customer_metadata');
		$this->_table = 'customers';
		if(isset($id)) {
			$this->_id = $id;
			$this->_load();
		}
	}

	private function _load() {
		$q = sprintf("SELECT * FROM %s WHERE id = %d", $this->_table, $this->_id);

		try {
			$user = $this->_mysql->get_row($q);
		} catch (Exception $e) {
			die('<b>DB_ERROR:</b> ' . $e->getMessage() . '<br />' . ( empty( $q ) == false ? '<b>QUERY:</b> ' . $q . '<br />' : '' ));
		}

		if($user === false) {
			die('No user for id: ' . $this->_id);
		} else {
			$this->_first_name = $user->first_name;
			$this->_last_name = $user->last_name;
			$this->_email = $user->email_address;
			$this->_date_of_birth = $user->date_of_birth;
			$this->_created_at = $user->created_at;
		}
		return;
	}

	public function __get($name) {
		switch ($name) {
			case 'id':
				return $this->_id;
			case 'first_name':
				return $this->_first_name;
			case 'last_name':
				return $this->_last_name;
			case 'email':
				return $this->_email;
			case 'date_of_birth':
				return $this->_date_of_birth;
			case 'created_at':
				return $this->_created_at;
			default:
				throw new Exception('Trying to access undefined customer attribute ' . $name);
		}
	}

	public function __set($name, $value) {
		switch ($name) {
			case 'first_name':
				$this->_first_name = trim($value);
				break;
			case 'last_name':
				$this->_last_name = trim($value);
				break;
			case 'email':
				if(filter_var($value, FILTER_VALIDATE_EMAIL) === False) {
					throw new Exception('Trying to set invalid email');
					return;
				}
				$this->_email = $value;
				break;
			case 'date_of_birth':
				$timestamp = strtotime($value);
				if(is_numeric($timestamp) === False) {
					throw new Exception('Trying to set invalid date of birth. Expected format Y-m-d');
					return;
				} elseif($timestamp > time()) {
					throw new Exception('Trying to set future date as birth date.');
					return;
				} elseif(checkdate(date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)) === False) {
					throw new Exception('Trying to set invalid date of birth. Expected format Y-m-d');
					return;
				}
				$this->_date_of_birth = date('Y-m-d H:i:s', $timestamp);
				// $this->_date_of_birth = $value;
				break;
			default:
				throw new Exception('Trying to set undefined property ' . $name);
		}
	}

	public function save() {
		if(isset($this->_id)) {
			$q = sprintf("UPDATE %s SET first_name='%s', last_name='%s', email_address='%s', date_of_birth='%s' WHERE id = %d",
				$this->_table, $this->_first_name, $this->_last_name, $this->_email, $this->_date_of_birth, $this->_id);
		} else {
			$q = sprintf("INSERT INTO %s (first_name, last_name, email_address, date_of_birth) VALUES('%s', '%s', '%s', '%s')",
				$this->_table, $this->_first_name, $this->_last_name, $this->_email, $this->_date_of_birth);
		}

		try {
			$status = $this->_mysql->query($q);
		} catch (Exception $e) {
			die('<b>DB_ERROR:</b> ' . $e->getMessage() . '<br />' . ( empty( $q ) == false ? '<b>QUERY:</b> ' . $q . '<br />' : '' ));
		}

		if(!isset($this->_id)) {
			$this->_id = $this->_mysql->insert_id;
		}
		return $status;
	}

	public function delete() {
		if(!isset($this->_id)) {
			return;
		}

		$q = sprintf("DELETE FROM %s WHERE id = %d",
				$this->_table, $this->_id);

		try {
			$status = $this->_mysql->query($q);
		} catch (Exception $e) {
			die('<b>DB_ERROR:</b> ' . $e->getMessage() . '<br />' . ( empty( $q ) == false ? '<b>QUERY:</b> ' . $q . '<br />' : '' ));
		}

		unset($this->_id);
		return $status;
	}

	public function getMetaData() {
		if(!isset($this->_id)) {
			return;
		}

		$metadata = $this->_collection->findOne(array('customer_id' => $this->_id));
		if($metadata === NULL) {
			return array();
		}

		unset($metadata['_id']);
		unset($metadata['customer_id']);
		return $metadata;
	}

	public function setMetaData($metadata) {
		if(!isset($this->_id)) {
			throw new Exception('Cannot store metadata before saving object itself.');
		}

		$metadata['customer_id'] = $this->_id;
		foreach ($metadata as $key => $value) {
			if($key === '_id') {
				unset($metadata[$key]);
			} elseif((strpos($key, '$') !== FALSE) || (strpos($key, '.') !== FALSE)) {
				unset($metadata[$key]);
			}
		}

		$current_meta_data = $this->getMetaData();
		$metadata = array_merge($current_meta_data, $metadata);
		$this->_collection->update(
			array('customer_id' => $this->_id),
			$metadata,
			array('upsert' => True)
		);
	}

	public function __destruct() {
		// $this->_mysql->close();
		// $this->_mongodb->connection->close();
	}
}