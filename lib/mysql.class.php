<?php
/**
 * PHP-DB-class
 * provide an easy way to work with databases with PHP using PDO extension
 *
 * method names and some code are based on ezSQL by Justin Vincent
 *
 * @version		2.0.2
 * @author		Łukasz Szymkowiak
 * @link			http://www.lszymkowiak.pl/db
 * @license		This work is licensed under a Creative Commons Attribution 3.0 Unported License. 
 *						To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/
 */
class db {
	
	/**
	 * insert ID from last query
	 */
	public $insert_id = null;
	
	/**
	 * number of rows affected by insert,update,replace,delete query
	 */
	public $affected_rows = null;
	
	/**
	 * number of rows returned by select query
	 */
	public $num_rows = null;
	
	private static $db_instance;
	private static $db_dsn = null;
	private static $db_user = null;
	private static $db_password = null;
	private $pdo = null;
	private $error = true;
	private $debug = false;
	private $debug_once = false;
	private $method = null;
	private $query = null;
	private $result = null;
	private $start_time = null;
	private $exec_time = null;
	private $mode = array ( 'OBJECT' => PDO::FETCH_OBJ, 'ARRAY' => PDO::FETCH_ASSOC );
	// private $output = null;
	
	/**
	 * configure db class properities
	 *
	 * @access	public
	 * @param		string	$setting
	 * @param		string	$value
	 */
	public static function configure( $setting, $value=null ) {
		if ( property_exists( __CLASS__, $setting ) ) {
			self::$$setting = $value;
		}
	}
	
	
	/**
	 * singelton instance
	 *
	 * @access  public
	 */
	public static function get_instance() {
		if ( isset( self::$db_instance ) == false ) {
			self::$db_instance = new db();
		}
		return self::$db_instance;
	}
	
	
	/**
	 * create a connection to a database
	 *
	 * @access  private
	 */
	private function __construct() {
		try {
			$this->pdo = new PDO( self::$db_dsn, self::$db_user, self::$db_password );
		} catch( PDOException $e ) {
			$this->_show_error( $e->getMessage() );
		}
	}
	
	
	/**
	 * execute query and return the number of affected rows
	 *
	 * @access	public
	 * @param 	string	$query
	 * @return	int
	 */
	public function query( $query ) {
		$this->method = __METHOD__;
		$this->query = trim( $query );
		if ( $statement = $this->_query() ) {
			$this->result = $statement->rowCount();
		}
		$this->debug || $this->debug_once ? $this->_show_debug() : null;
		$this->debug_once = false;
		return $this->result;
	}
	
	
	/**
	 * execute query and returns single variable
	 *
	 * @access	public
	 * @param 	string	$query		query to execute
	 * @return	string
	 */
	public function get_var( $query ) {
		$this->method = __METHOD__;
		$this->query = trim( $query );
		if ( $statement = $this->_query() ) {
			$this->result = $statement->fetchColumn();
			$this->num_rows = $this->result === false ? 0 : count( $this->result );
		}
		$this->debug || $this->debug_once ? $this->_show_debug() : null;
		$this->debug_once = false;
		return $this->result;
	}
	
	
	/**
	 * execute query and returns single row in associative array or object (defined by $output)
	 *
	 * @access	public
	 * @param 	string	$query
	 * @param 	string	$output
	 * @return	object|array
	 */
	public function get_row( $query, $output='OBJECT' ) {
		$this->method = __METHOD__;
		$this->query = trim( $query );
 		if ( $statement = $this->_query() ) {
			$output = array_key_exists( $output, $this->mode ) ? $output : key( $this->mode );
			$this->result = $statement->fetch( $this->mode[$output] );
			$this->num_rows = count( $this->result );
 		}
		$this->debug || $this->debug_once ? $this->_show_debug() : null;
 		$this->debug_once = false;
 		return $this->result;
	}
	
	
	/**
	 * execute query and returns single column in indexed array
	 *
	 * @access	public
	 * @param 	string	$query
	 * @return	array
	 */
	public function get_col( $query ) {
		$this->method = __METHOD__;
		$this->query = trim( $query );
		if ( $statement = $this->_query() ) {
			while ( $row = $statement->fetchColumn() ) {
				$this->result[] = $row;
				$this->num_rows++;
			}
		}
		$this->debug || $this->debug_once ? $this->_show_debug() : null;
 		$this->debug_once = false;
		return $this->result;
 	}
	
	
	/**
	 * execute query and return rows as indexed array
	 * rows can be an associative array or object (defined by $output)
	 *
	 * @access	public
	 * @param 	string	$query
	 * @param 	string	$output
	 * @return	array
	 */
	public function get_results( $query, $output='OBJECT' ) {
		$this->method = __METHOD__;
		$this->query = trim( $query );
 		if ( $statement = $this->_query() ) {
			$output = array_key_exists( $output, $this->mode ) ? $output : key( $this->mode );
			$this->result = $statement->fetchAll( $this->mode[$output] );
			$this->num_rows = count( $this->result );
 		}
		$this->debug || $this->debug_once ? $this->_show_debug() : null;
		$this->debug_once = false;
		return $this->result;
	}
	
	
	/**
	 * execute query and return rows as associative array (key defined by $col)
	 * rows can be an associative array or object (defined by $output)
	 *
	 * @access	public
	 * @param 	string	$query
	 * @param 	string	$output
	 * @return	mixed
	 */
	public function get_assoc( $query, $col, $output='OBJECT' ) {
		$this->method = __METHOD__;
		$this->query = trim( $query );
 		if ( $statement = $this->_query() ) {
			$output = array_key_exists( $output, $this->mode ) ? $output : key( $this->mode );
			while ( $row = $statement->fetch( $this->mode[$output] ) ) {
 				if ( $output == 'OBJECT' ) {
 					$this->result[$row->$col] = $row;
 				} else {
 					$this->result[$row[$col]] = $row;
 				}
				$this->num_rows++;
 			}
 		}
		$this->debug || $this->debug_once ? $this->_show_debug() : null;
 		$this->debug_once = false;
		return $this->result;
	}
	
	
	/**
	 * return escaped string for safe query
	 *
	 * @access	public
	 * @param		string $string
	 * @return	string
	 */
	public function escape( $string ) {
		return addslashes( stripslashes( $string ) );
	}
	
	
	/**
	 * turn on or off errors displaying
	 *
	 * @access	public
	 * @param		bool		$state
	 */
	public function error( $state=true ) {
		$this->error = $state;
	}
	
	
	/**
	 * turn on or off debug displaying
	 *
	 * @access	public
	 * @param		bool		$state
	 */
	public function debug( $state=true ) {
		$this->debug = $state;
	}
	
	
	/**
	 * turn on or off errors displaying only for single query
	 *
	 * @access	public
	 */
	public function debug_once() {
		$this->debug_once = true;
	}
	
	
	/**
	 * prepare query to execute
	 *
	 * @access	private
	 * @param		string		$query
	 * @return	object
	 */
	private function _query() {
		$this->_reset();
		if ( $this->pdo ) {
			// var_dump($this->query);
			$statement = $this->pdo->query( $this->query );
			$error = $this->pdo->errorInfo();
			// var_dump($error);
			// var_dump($statement);
			// die();
			if ( $statement == false && isset( $error['1'] ) ) {
				$this->_show_error( $error['2'] );
			} else {
				if ( preg_match( "/^\s*(insert|update|replace|delete)\s+/i", strtolower( $this->query ) ) ) {
 					$this->affected_rows = $statement->rowCount();
 					// var_dump($statement);
 				}
				if ( preg_match( "/^\s*(insert|replace)\s+/i", strtolower( $this->query ) ) ) {
 					$this->insert_id = $this->pdo->lastInsertId();
 				// 	var_dump($this->insert_id);
 				// 	echo 'dasdsa';
					// var_dump($statement);

 				}
 				return $statement;
			}
		}
	}
	
	
	/**
	 * reset variables specific for each query
	 *
	 * @access	private
	 */
	private function _reset() {
		$this->start_time = microtime(true);
		$this->insert_id = null;
		$this->affected_rows = null;
		$this->num_rows = null;
		switch( $this->method ) { 
			case __CLASS__.'::query';
				$this->result = 0;
				break;
			case __CLASS__.'::get_var':
				$this->result = false;
				break;
			case __CLASS__.'::get_row':
				// $this->result = ( $this->output == 'ARRAY' ? array() : null );
				$this->result = null;
				break;
			default:
				$this->result = array();
				break;
		}
	}
		
	
	/**
	 * display query debug (method name, query string, execution time, number of rows in result, affected rows, insert ID, query result)
	 *
	 * @access	private
	 */
	private function _show_debug() {
		$this->exec_time = sprintf( "%01.10f", microtime(true) - $this->start_time );
		echo '<span style="color:#999;"><b>' . $this->method . '</b>( "' . $this->query . '" )</span>';
		echo '<br /><b>EXECUTION_TIME:</b> ' . $this->exec_time . 's';
		echo $this->num_rows !== null ? '<br /><b>NUM_ROWS:</b> ' . $this->num_rows : '';
		echo $this->affected_rows !== null ? '<br /><b>AFFECTED_ROWS:</b> ' . $this->affected_rows : '';
		echo $this->insert_id !== null ? '<br /><b>INSERT_ID:</b> ' . $this->insert_id : '';
		echo '<br /><b>DB_RESULT:</b>';
		echo '<pre>';
		print_r( $this->result );
		echo '</pre>';
		echo '<hr />';
	}
	
	
	/**
	 * display query errors
	 *
	 * @access	private
	 * @param		string		$txt
	 * @param		string		$query
	 * @return	null
	 */
	private function _show_error( $txt ) {
		if ( $this->error === true ) {
			throw new Exception($txt);
			// echo '<b>DB_ERROR:</b> ' . $txt . '<br />' . ( empty( $this->query ) == false ? '<b>QUERY:</b> ' . $this->query . '<br />' : '' );
		}
	}
	
}
?>