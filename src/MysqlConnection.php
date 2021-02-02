<?php 
namespace App\Connection;

use App\Mysql;


class MysqlConnection extends MysqliQuery
{
	/**
	 * [$_connection object mysql]
	 * @var [object]
	 */
	protected $_connection;

	/**
	 * [$_showError setting for debug]
	 * @var boolean
	 */
	protected $_showError = true;

	/**
	 * [$_instance instance object mysql]
	 * @var [object]
	 */
	protected static $_instance;

	/**
	 * [$_host name host local]
	 * @var [type]
	 */
	private $_host;

	/**
	 * [$_dbName database name]
	 * @var [type]
	 */
	private $_dbName;

	/**
	 * [$_password password database ]
	 * @var [type]
	 */
	private $_password;

	/**
	 * [$_dbUser user name database]
	 * @var [type]
	 */
	private $_dbUser;

	/**
	 * [$_port port server mysql 3306]
	 * @var [type]
	 */
	private $_port = 3306;

	/**
	 * [$_charset set character encoding]
	 * @var [type]
	 */
	private $_charset = 'utf8';

	/**
	 * [$_mysqliQuery Object query builder]
	 * @var [type]
	 */
	protected $_mysqliQuery;

	/**
	 * [__construct]
	 * @param [type] $host     [server host]
	 * @param [type] $db_name  [database name]
	 * @param [type] $password [database password]
	 * @param [type] $db_user  [databas user name]
	 * @param [type] $port     [port server mysql]
	 * @param string $charset  [character encoding]
	 * @param [type] $prefix   [prefix]
	 */
	public function __construct($host = null, $db_name = null, $password = null, $db_user = null, $port = null, $charset = null, $prefix = null)
	{
		$this->_host = $host;
		$this->_dbName = $db_name;
		$this->_password = $password;
		$this->_dbUser = $db_name;
		$this->_port = $port;
		$this->_charset = $chatset;

		$this->addConnection($host, $db_name, $password, $db_user, $port, $charset);

		$this->_mysqliQuery = parent::_construct(null, $prefix);

		self::$_instance = $this;
	}

		/**
	 * Khởi tạo một kết nối đến database
	 * @param [ip|localhost] $host     ip host sevrer
	 * @param string $db_name  database name server
	 * @param password $password user password
	 * @param string $db_user  user name
	 * @param int $port     server database port
	 * @param string $charset  set chatrset
	 * @return Mysqli object
	 */
		public function addConnection($host, $db_name, $password, $db_user, $port = null, $charset)
		{
			$mysqli = false;
			if (!is_object($this->_connection)) {
				$mysqli = new \Mysqli($host, $db_name, $password, $db_user, $port);
				if ($mysqli->connect_errno) {
					if ($this->_showError == true) {
						throw new Exception("Failed to connect to Mysql: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
					} else {
						throw new Exception("Failed to connect to Mysql");
					}
				}
				if (!empty($charset)) {
					$mysqli->set_charset($charset);
				}
				$this->_connection = $mysqli;
			} else {
				$mysqli = $this->_connection;
			}
			return $mysqli;
		}

	/**
	 * Trả về một đối tượng tĩnh được khởi tạo từ Mysql connection
	 * @user $db = Db::getInstance();
	 * @return Object Mysqli
	 */
	public static function getInstance()
	{
		return self::$_instance;
	}

	public function rawQuery(string $query, string $queryMode = null)
	{
		// TO DO SOMETHING
		$queryMode = strtoupper($queryMode);
		$result = $this->_connection->query($query, $queryMode);
		if (!$result || $this->_connection->errno) {
			throw new Exception($this->_connection->errno);
		}
		return $result;
	}

	private function close()
	{
		if ($this->_connection instanceof MYSQLI) {
			$this->_connection->close();
			$this->_connection = null;
		}
	}

	public function __destruct()
	{
		$this->close();
	}
}
