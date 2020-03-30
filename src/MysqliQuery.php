<?php

namespace App\Mysql;

/**
 * This is a class that allows you to connect to your database system using mysqli object
 * @author Luu Doanh
 * @copyright Luu Doanh (C) 2019
 * @link https://github.com/luudoanh/class-db-mysli
 * @version 1.0
 */

class MysqliQuery
{
	/**
	 * 	Static instance
	 * @var Object Db
	 */
	protected static $_instance;

	/**
	 * 	Variable connection
	 * @var Object mysqli
	 */
	protected $_connection;

	/**
	 * 	Table prefix
	 * @var string
	 */
	protected static $_prefix = '';

	/**
	 * 	Setting for debug
	 * @var boolean
	 */
	private $_showError = true;

	/**
	 * Sql query statement
	 */
	private $_query = '';
	
	/**
	 * Table query
	 */
	private $_tableName = '';

	private $_isSubQuery = false;

	private $_subQueryAlias = '';

	/**
	 * Optional Query
	 * @var string
	 */
	
	private $_queryOption = '';
	private $_lockInShareMode = '';
	private $_forUpdate = '';
	private $_procedure = '';
	private $_into = [];

	/**
	 * The structure of the query
	 */
	private $_fields = '*';
	private $_where = [];
	private $_groupBy = [];
	private $_having = [];
	private $_orderBy = [];
	private $_limit = [];
	private $_join = [];
	private $_addAndToJoin = [];
	private $_union = [];


	/**
	 * The complete sql query
	 */
	protected $lastQuery = '';
	protected $lastQueryOption = '';

	/**
	 * Connect to server sql
	 */
	public function __construct($host = null, $db_name = null, $password = null, $db_user = null, $port = null, $charset = 'utf8', $prefix = null)
	{
		$_isSubQuery = false;
		if (is_array($host)) {
			foreach ($host as $key => $value) {
				$$key = $value;
			}
		}
		if ($_isSubQuery) {
			$this->_isSubQuery = $_isSubQuery;
			$this->_subQueryAlias = $_subQueryAlias;
			return false;
		}

		$this->addConnection($host, $db_name, $password, $db_user, $port, $charset);
		if (isset($prefix)) {
			$this->setPrefix($prefix);
		}

		self::$_instance = $this;
	}

	/**
	 * Trả về một đối tượng tĩnh được khởi tạo từ Mysqli connection
	 * @user $db = Db::getInstance();
	 * @return Object Mysqli
	 */
	public static function getInstance()
	{
		return self::$_instance;
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
	 * Function set prefix
	 * @param string $prefix
	 * @return Mysqli object
	 */
	public function setPrefix($prefix = null)
	{
		self::$_prefix = $prefix;
		return $this;
	}

	/**
	 * Set các giá trường cần tìm kiếm
	 * @param string $fields [description]
	 * @return Mysqli object
	 */
	public function setFields($fields = '*')
	{
		if (is_array($fields)) {
			$this->_fields = implode(', ', $fields);
		} else {
			$this->_fields = $fields;
		}
		return $this;
	}
	/**
	 * Tạo những điều kiện cho câu truy vấn
	 * @param  string $whereColumns cột được so sánh
	 * @param  string $operator     phép toán
	 * @param  [type] $whereValues  giá trị so sánh
	 * @param  string $more         thêm nhiều điều kiện nữa
	 * @return Mysqli object
	 */
	public function where($whereColumns, $operator = '=', $whereValues = null, $more = 'AND')
	{
		if (empty($this->_where)) {
			$more = '';
		}
		$this->_where[] = array($whereColumns, $operator, $whereValues, $more);
		return $this;
	}

	/**
	 * Tạo nhanh điều kiện với mệnh đề hoặc (or)
	*/
	public function orWhere($whereColumns, $operator = '=', $whereValues, $more = 'OR')
	{
		$this->where($whereColumns, $operator, $whereValues, $more);
		return $this;
	}

	/**
	 * Nhóm theo các cột
	 * @param  [string|array] $columns tên của các cột cần nhóm, nhăn cách nhau bằng dấu , hoặc truyền vào một mảng chứa các cột cần nhóm
	 * @return Mysqli object
	 */
	public function groupBy($columns)
	{
		if (is_array($columns)) {
			$this->_groupBy[] = implode(', ', $columns);
			return $this;
		}
		$this->_groupBy[] = $columns;
		return $this;
	}

	/**
	 * Tạo câu điều kiện lọc các giá trị khi tìm kiếm, sử dụng chung với mệnh đề Group By
	 * @param  string $havingColumns tên các cột cần đưa vào điều kiện
	 * @param  string $operator      phép tử so sánh
	 * @param  string $havingValues  giá trị so sánh
	 * @param  string $more          thêm các toán tử logic mặc định là and
	 * @return Mysqli object         
	 */
	public function having($havingColumns, $operator, $havingValues, $more = 'AND')
	{
		if (empty($this->_having)) {
			$more = '';
		}
		$this->_having[] = array($havingColumns, $operator, $havingValues, $more);
		return $this;
	}

	/** Sử dụng having với toán tử logic or */
	public function orHaving($havingColumns, $operator, $havingValues, $more = 'OR')
	{
		$this->having($havingColumns, $operator, $havingValues, $more);
		return $this;
	}

	/**
	 * Tạo một sắp xếp các giá trị trả về cho câu lệnh query
	 * @param  [string|array] $orderByColumns   tên các cột cần sắp xếp theo. Nếu muốn sắp xếp theo nhiều cột hãy truyền vào một mảng
	 * @param  string $orderByDirection Sắp xếp tăng dần hoặc giảm dần tương đương là ASC hoặc DESC
	 * @return Mysqli object
	 */
	public function orderBy(string $orderByColumns, string $orderByDirection = 'DESC')
	{
		$allowedDirection = ['ASC', 'DESC'];
		$orderByDirection = strtoupper($orderByDirection);
		if(!in_array($orderByDirection, $allowedDirection)) {
			throw new Exception("Wrong order by direction: ". $direction);
		}
		$this->_orderBy[$orderByColumns] = $orderByDirection;
		return $this;
	}

	/**
	 * Tạo một giới hạn trong câu lệnh query
	 * @param  int      $firstLimit  Lấy từ vị trí thứ nhất
	 * @param  int|null $secondLimit Lấy đến vị trị này. Nếu giá trị thứ 2 không được truyền vào thì giá trị đầu tiên sẽ được hiểu như số lượng record đc lấy
	 * @return Mysqli object
	 */
	public function limit(int $firstLimit, ?int $secondLimit = null)
	{
		if ($secondLimit !== null) {
			$this->_limit = [$firstLimit, $secondLimit];
		} else {
			$this->_limit = [$firstLimit];
		}
		return $this; 
	}

	/**
	 * Thực hiện việc gộp hai hoặc nhiều bảng vào với nhau
	 * @param  string $joinTable    Tên bảng sẽ được gộp
	 * @param  string $joinOperator Toán tử gộp
	 * @param  string $joinType     Các kiểu gộp ['INNER', 'LEFT', 'LEFT OUTER', 'RIGHT', 'RIGHT OUTER']
	 * @return Mysqli object
	 */
	public function join(string $joinTable, $joinOperator, $joinType = '')
	{
		$joinType = strtoupper($joinType);
		$joinAllowed = ['INNER', 'LEFT', 'LEFT OUTER', 'RIGHT', 'RIGHT OUTER'];
		if (!in_array($joinType, $joinAllowed)) {
			throw new Exception("Join type doesn't match: ".implode(', ', $joinAllowed));	
		}
		$joinTable = self::$_prefix.$joinTable;
		$this->_join[] = [$joinTable, $joinOperator, $joinType];
		return $this;
	}

	/** Gộp nhanh một bảng nữa với toán tử logic AND */
	public function addAndToJoin($joinColumns, $operator, $joinValues, $more = 'AND')
	{
		$this->_addAndToJoin[] = [$joinColumns, $joinValues, $operator, $more];
		return $this;
	}

	/** Gộp nhanh một bảng nữa với toán tử logic OR */
	public function addOrToJoin($joinColumns, $operator, $joinValues, $more = 'OR')
	{
		$this->addAndToJoin($joinColumns, $joinValues, $operator, $more);
		return $this;
	}

	/**
	 * Gộp thêm một câu truy vấn nữa để kết quả được trả về cùng nhau
	 * @param  object|string  $unionQuery đối tượng chứa câu lệnh query trước đó
	 * hoặc một câu query hoàn chỉnh
	 * @param  boolean $isUnionAll true and false với UNION ALL hoặc chỉ UNION
	 * @return Mysqli object
	 */
	public function union($unionQuery, $isUnionAll = false)
	{
		if ($unionQuery instanceof MysqliQuery) {
			$this->_union[] = [$unionQuery->getLastQuery(), $isUnionAll];
		} else {
			$this->_union[] = [$unionQuery, $isUnionAll];
		}
		return $this;
	}

	/**
	 * thêm vào các tùy chọn cho câu lệnh query
	 * @param string|null $queryOption các tùy chọn của mysql
	 */
	public function setQueryOption(string $queryOption = null)
	{
		$optionAllowed = ['ALL', 'DISTINCT', 'DISTINCTROW', 'HIGH_PRIORITY', 'STRAIGHT_JOIN', 'SQL_SMALL_RESULT', 'SQL_BIG_RESULT', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_NO_CACHE', 'SQL_CALC_FOUND_ROWS'];
		if (!in_array(strtoupper($queryOption), $optionAllowed)) {
			throw new Exception("Query option doesn't match: ".implode(', ', $optionAllowed));	
		}
		$this->_queryOption = strtoupper($queryOption);
		return $this;
	}

	/**
	 * Set bảng thủ tục
	 * @param string $procedureName tên bảng
	 */
	public function setProcedure(string $procedureName)
	{
		$this->_procedure = $procedureName;
		return $this;
	}

	/**
	 * Tạo một câu lệnh để sao chép dữ liệu vào một bảng khác hoặc in ra một file
	 * @param  string|null $intoType lựa chọn các kiểu OUTFILE or DUMPFILE or VARIABLE
	 * @param  string|null $fileName Tên file cần đổ dữ liệu vào
	 * @param  array|null  $data     dữ liệu là các biến hoặc các lĩnh vực tùy theo kiểu bạn chọn
	 * @return Mysqli object
	 */
	public function into(string $intoType = null, string $fileName = null, ?array $data = null)
	{
		$intoType = strtoupper(trim($intoType));
		if ($intoType == 'OUTFILE') {
			$this->_into[] = Array(
				'type' => 'OUTFILE',
				'fileName' => $fileName,
				'data' => $data,
			);
		} elseif ($intoType == 'DUMPFILE') {
			$this->_into[] = Array(
				'type' => 'DUMPFILE',
				'fileName' => $fileName
			);
		} else {
			$this->_into[] = Array(
				'type' => 'VARIABLE',
				'data' => $data
			);
		}
		return $this;
	}

	/**
	 * Phương thức cuối cùng để tạo một câu lệnh query hoàn chỉnh
	 * @param  string $tableName  tên của bảng cần get, nó sẽ là bảng cuối cùng
	 * @param  string $columns    tên các cột cần lấy dữ liệu ra
	 * @param  [type] $numberRows số lượng lấy ra, nếu nó được set thì hàm limit sẽ không được chấp nhận
	 * @return Mysqli object
	 */
	public function get($tableName, $columns = null, $numberRows = null)
	{
		$this->_tableName = self::$_prefix.$tableName;
		if ($columns) {
			if (is_array($columns)) {
				$columns = implode(', ', $columns);
			} else {
				$columns = $columns;
			}
		} else {
			$columns = $this->_fields;
		}
		if ($numberRows) {
			if (is_array($numberRows)) {
				$this->_limit = $numberRows;
			} else {
				$this->_limit = [$numberRows];
			}
		}
		$this->_query = 'SELECT'.($this->_queryOption ? ' '.$this->_queryOption : '').' '.$columns.' FROM '.$this->_tableName;
		$this->_buildQuery();
		$this->reset();
		return $this;
	}
	/**
	 * Tạo nhanh một câu lệnh truy vấn với limit là 1
	 * @param  string $tableName tên bảng
	 * @param  string $columns   các cột cần lấy giá trị
	 * @return Mysqli object
	 */
	public function getOne($tableName, $columns = '*')
	{
		return $this->get($tableName, $columns, 1);
	}

	/**
	 * Lấy các giá trị chứa câu lệnh query phía trước và bí danh cho câu lệnh truy vấn con
	 * @return array
	 */
	public function getSubQuery()
	{
		if (!$this->_isSubQuery) {
			return false;
		}
		$data = Array(
			'query' => $this->lastQuery,
			'alias' => $this->_subQueryAlias
		);
		$this->reset();
		return $data;
	}

	/**
	 * Phương thức tĩnh để tạo một câu truy vấn con
	 * @param  string|null $_subQueryAlias bí danh cho câu truy vấn con
	 * @return Mysqli object
	 */
	public static function subQuery(string $_subQueryAlias = null)
	{
		return new self(array('_subQueryAlias' => $_subQueryAlias, '_isSubQuery' => true));
	}

	/**
	 * Phương thức nội bộ để hoàn thiện các điều kiện truy vấn trong mệnh đề where
	 * @return none
	 */
	private function _buildWhere()
	{
		if (empty($this->_where)) {
			return false;
		}
		$this->_query .= ' WHERE';
		foreach ($this->_where as $key => $value) {
			list($whereColumns, $operator, $whereValues, $more) = $value;
			$this->_query .= ($more ? ' '.$more.' ' : ' ').$whereColumns;
			$operator = strtoupper($operator);
			switch (strtolower(trim($operator))) {
				case 'not in':
				case 'in':
				$this->_query .= ' '.$operator. ' ';
				if (is_object($whereValues)) {
					$data = $this->_buildSubQuery($whereValues);
				} else {
					$data = $this->_buildValues($whereValues);
				}
				$this->_query .= $data;
				break;
				case 'not between':
				case 'between':
				$this->_query .= ' '.$operator.' '.$whereValues[0].' AND '.$whereValues[1];
				break;
				case 'not exists':
				case 'exists':
				$this->_query .= ' '.$operator. ' ';
				$this->_query .= $this->_buildSubQuery($whereValues);
				break;
				default:
				if ($whereValues === null) {
					$this->_query .= ' '.$operator.' NULL';
				} else {
					$this->_query .= ' '.$operator.' '.$whereValues;
				}
				break;
			}
		}
	}

	/**
	 * xây dựng câu lệnh truy vấn con
	 * @param  string|object $value đối tượng chứa câu truy vấn con
	 * @return raw query
	 */
	private function _buildSubQuery($value)
	{
		if (!is_object($value)) {
			return '('.$value.')';
		}
		$subQuery = $value->getSubQuery();
		return '('.$subQuery['query'].')'.($subQuery['alias'] ? ' AS '.$subQuery['alias'] : '');
	}
	/**
	 * Xây dựng mệnh đề group by
	 * @return none
	 */
	private function _buildGroupBy()
	{
		if (empty($this->_groupBy)) {
			return false;
		}
		$this->_query .= ' GROUP BY '.$this->_groupBy[0];
	}

	/** Xâu dựng mệnh đề having */
	private function _buildHaving()
	{
		if (empty($this->_having)) {
			return false;
		}
		$this->_query .= ' HAVING';
		foreach ($this->_having as $key => $value) {
			list($havingColumns, $operator, $havingValues, $more) = $value;
			$this->_query .= ($more ? ' '.$more.' ' : ' ').$havingColumns;
			switch (strtolower(trim($operator))) {
				case 'not between':
				case 'between':
				$this->_query .= ' '.$operator.' '.$havingValues[0].' AND '.$havingValues[1].' ';
				break;
				// Future updates
					// case '':
					// 	break;
				default:
				if ($havingValues === null) {
					$this->_query .= ' '.$operator.' NULL';
				} else {
					$this->_query .= ' '.$operator.' '.$havingValues;
				}
				break;
			}
		}
	}
	
	/** Xây dựng mệnh đề order by */
	private function _buildOrderBy()
	{
		if (empty($this->_orderBy)) {
			return false;
		}
		$this->_query .= ' ORDER BY';
		foreach ($this->_orderBy as $orderByColumns => $orderByDirection) {
			$this->_query .= ' '.$orderByColumns.' '.$orderByDirection.',';
		}
		$this->_query = rtrim($this->_query, ',');
	}

	/** Xây dựng mệnh đề limit */
	private function _buildLimit()
	{
		if (empty($this->_limit)) {
			return false;
		}
		$this->_query .= ' LIMIT ';
		if (is_array($this->_limit)) {
			$this->_query .= implode(', ', $this->_limit);
		} else {
			$this->_query .= $this->_limit;
		}
		
	}

	/** Xây dựng câu lệnh join */
	private function _buildJoin()
	{
		if (empty($this->_join)) {
			return false;
		}
		foreach ($this->_join as $key => $value) {
			list($joinTable, $joinOperator, $joinType) = $value;
			$this->_query .= ' '.$joinType.' JOIN '.$joinTable.(count($joinOperator) !== 0 ? ' ON ' : ' ').$joinOperator;
		}
		if (!empty($this->_addAndToJoin)) {
			foreach ($this->_addAndToJoin as $key => $value) {
				list($joinColumns, $joinValues, $operator, $more) = $value;
				$this->_query .= ' '.$more.' '.$joinColumns.' '.$operator.' '.$joinValues;
			}
		}
	}

	/** Xây dựng câu lệnh union */
	private function _buildUnion()
	{
		if (empty($this->_union)) {
			return false;
		}
		foreach ($this->_union as $key => $value) {
			list($unionQuery, $isUnionAll) = $value;
			$this->_query .= ' UNION '.($isUnionAll ? 'ALL ' : '').$unionQuery;
		}
		return $this;
	}

	/** Xây dựng bảng thủ tục */
	private function _buildProcedure()
	{
		if (empty($this->_procedure)) {
			return false;
		}
		$this->_query .= ' PROCEDURE ';
		foreach ($this->_procedure as $key => $value) {
			$this->_query .= $value;
		}
	}

	/** Xây dựng hoàn thiện câu lệnh với into  */
	private function _buildInto()
	{
		if (empty($this->_into)) {
			return false;
		}
		$this->_query .= ' INTO ';
		foreach ($this->_into as $key => $value) {
			if ($value['type'] == 'OUTFILE') {
				list($fieldsTerminated, $optionallyEnclosed, $linesTerminated) = $value['data'];
				$data = "FIELDS TERMINATED BY '$fieldsTerminated' OPTIONALLY ENCLOSED BY '$optionallyEnclosed' LINES TERMINATED BY '$linesTerminated' ";
				$this->_query .= 'OUTFILE '.$value['fileName'].' '.$data;
			} elseif ($value['type'] == 'DUMPFILE') {
				$this->_query .= 'DUMPFILE '.$fileName.' ';
			} else {
				$this->_query .= implode(', ', $value['data']).' ';
			}
		}
	}

	/**
	 * Phương thức nội bộ để sử lý các giá trị trong các toán tử
	 * @param  string|array $values giá trị được sử lý
	 * @return raw query
	 */
	private function _buildValues($values)
	{
		$data = null;
		if (is_array($values)) {
			foreach ($values as $val) {
				$data .= (is_numeric($val) ? $val : "'".$val."'").',';
			}
		} else {
			if (strpos($values, ',')) {
				$newArray = explode(',', rtrim($values, ','));
				return call_user_func(array($this, __FUNCTION__), $newArray);
			}
			$data = "'".$values."'";
		}
		$data = '('.rtrim($data, ',').')';
		return $data;
	}

	/** Kết hợp các phương thức build nhỏ lẻ để tạo một câu lệnh query cuối cùng */
	private function _buildQuery()
	{
		$this->_buildJoin();
		$this->_buildWhere();
		$this->_buildInto();
		$this->_buildGroupBy();
		$this->_buildHaving();
		$this->_buildOrderBy();
		$this->_buildLimit();
		$this->_buildProcedure();
		$this->_buildUnion();
		if ($this->_lockInShareMode) {
			$this->_query .= ' LOCK IN SHARE MODE ';
		} elseif ($this->_forUpdate) {
			$this->_query .= ' FOR UPDATE ';
		}
		$this->lastQuery = $this->_query;
	}
	/**
	 * Làm mới tất cả các biến thể sẵn sàng tạo một câu lệnh mới
	 * @return Mysqli object
	 */
	public function reset()
	{
		$this->lastQuery = $this->_query;
		$this->lastQueryOption = $this->_queryOption;
		$this->_where = [];
		$this->_groupBy = [];
		$this->_having = [];
		$this->_orderBy = [];
		$this->_limit = [];
		$this->_join = [];
		$this->_addAndToJoin = [];
		$this->_union = [];
		$this->_queryOption = '';
		$this->_lockInShareMode = '';
		$this->_forUpdate = '';
		$this->_union = [];
		$this->_into = [];
		$this->_procedure = '';
		$this->_tableName = '';
		$this->_fields = '*';
		$this->_query = '';
		return $this;
	}

	/** Lấy ra câu truy vấn cuối cùng */
	public function getLastQuery()
	{
		return $this->lastQuery;
	}
	
	/** Lấy ra tùy chọn cuối cùng được sử dụng */
	public function getLastQueryOption()
	{
		return $this->lastQueryOption;
	}

	public function getLimit()
	{
		return $this->_limit;
	}
	/** Nhắt kết nối đến csdl */
	public function disconnect()
	{
		$this->reset();
		$this->_connection->close();
		unset($this->_connection);
	}
	
}



//END CLASS

?>