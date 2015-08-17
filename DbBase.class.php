<?php
/**
* 数据库类的基础类
*/
class DbBase{

	protected $mysqli;
	protected $table;
	protected $re;
	protected $sql;
	protected $_where;
	protected $_limit;
	protected $_order;
	protected $_field;

	function __construct($mysqli){
		$this->mysqli = $mysqli;
		$this->_field = '*';
	}

	public function get_re(){
		return $this->re;
	}

	public function get_sql(){
		return $this->sql;
	}

	public function get_conn(){
		return $this->mysqli;
	}

	public function set_conn($conn){
		$this->mysqli = $conn;
	}

	public function set_table($table){
		$this->table;
	}

	public function fields($field){
		if (is_string($field)){
			$this->_field = $field;
		} elseif (is_array($field)) {
			$this->_field = implode(',', $field);
		}
		return $this;
	}

	public function limit($limit){
		if (!$limit){
			$this->_limit = '';
			return $this;
		}
		$this->_limit = "LIMIT $limit";
		return $this;
	}

	public function page($page_num, $length){
		$start = ($page_num-1)*$length;
		$this->_limit = "LIMIT $start, $length";
		return $this;
	}

	public function order($order){
		$this->_order = "ORDER BY $order";
		return $this;
	}

	public function where($where){
		if (!$where){
			$this->_where = '';
			return $this;
		}
		$equal_string = $this->parse_dict_equal($where, 'AND');
		$this->_where = " WHERE $equal_string";
		return $this;
	}

	/**
	 * 过滤数组中所有字符串
	 * @param  array $arr
	 * @return array
	 */
	public function escape_all($arr){
		foreach ($arr as $key => $value) {
			if (is_string($value)){
				$arr[$key] = $this->mysqli->real_escape_string($value);
			}
		}
		return $arr;
	}

	/**
	 * 解析where，如果是数组，先过滤，然后组成等式where，如果是字符串，简单过滤即可
	 * @param  string|array $where
	 * @return string
	 */
	protected function parse_dict_equal($dict, $delimiter){
		if (is_string($dict)){
			$result = $dict;
		} else {
			$dict = $this->escape_all($dict);
			$equal_arr = array();
			foreach ($dict as $key => $value) {
				if (is_array($value)){
					if ($value[0] == 'function'){
						$equal_arr[] = "`$key`=".$value[1];
					} elseif ($value[0] == 'raw_string') {
						$equal_arr[] = "`$key`=".$value[1];
					}
				} else {
					$equal_arr[] = "`$key`='$value'";
				}
			}
			$result = implode(" $delimiter ", $equal_arr);
		}
		return $result;
	}

	protected function sort_sql_tail(){
		$tail = " $this->_where $this->_order $this->_limit";
		return $tail;
	}

	static public function create_conn($host, $user, $password, $database, $port=3306, $charset="utf8mb4"){
		$mysqli = new mysqli($host, $user, $password, $database, $port);
		if ($mysqli->connect_errno){
			echo "<!-- Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error.' -->';
			exit;
		}
		$mysqli->set_charset($charset);
		return $mysqli;
	}

	public function query($sql){
		$this->re = $this->mysqli->query($sql);

		// 把搜索条件清空
		$this->_field = '*';
		$this->_limit = '';
		$this->_order = '';
		$this->_where = '';

		return $this->re;
	}

	public function find($exec=true){
		$this->limit(1);
		$this->sql = "SELECT $this->_field FROM `$this->table`".$this->sort_sql_tail();
		if (!$exec){
			return $this->sql;
		}
		$this->query($this->sql);
		return $this->re ? $this->re->fetch_assoc() : $this->re;
	}

	public function select($exec=true, $index=false){
		$this->sql = "SELECT $this->_field FROM `$this->table`".$this->sort_sql_tail();
		if (!$exec){
			return $this->sql;
		}
		$this->query($this->sql);
		$result = array();
		if ($index == false){
			while ($row = $this->re->fetch_assoc()) {
				$result[] = $row;
			}
		} else {
			while ($row = $this->re->fetch_assoc()) {
				$result[$row[$index]] = $row;
			}
		}
		return $result;
	}

	public function insert($insert_dict, $exec=true){
		$set = $this->parse_dict_equal($insert_dict, ',');
		$this->sql = "INSERT INTO `$this->table` SET $set ";
		if (!$exec){
			return $this->sql;
		}
		$this->query($this->sql);
		return $this->re ? $this->mysqli->insert_id : $this->re;
	}

	public function count($exec=true){
		$this->sql = "SELECT COUNT(*) AS all_count FROM `$this->table` $this->_where ";
		if (!$exec){
			return $this->sql;
		}
		$this->query($this->sql);
		$row = $this->re->fetch_assoc();
		return $row['all_count'];
	}

	public function delete($exec=true){
		$this->sql = "DELETE FROM `$this->table` $this->_where ";
		if (!$exec){
			return $this->sql;
		}
		$this->query($this->sql);
		return $this->re ? $this->mysqli->affected_rows : $this->re;
	}

	public function update($update_dict, $exec=ture){
		$set = $this->parse_dict_equal($update_dict, ',');
		$this->sql = "UPDATE `$this->table` SET $set $this->_where ";
		if (!$exec){
			return $this->sql;
		}
		$this->query($this->sql);
		return $this->re ? $this->mysqli->affected_rows : $this->re;
	}

	public function execute($sql, $index=false){
		$this->query($sql);
		$result = array();
		if ($index == false){
			while ($row = $this->re->fetch_assoc()) {
				$result[] = $row;
			}
		} else {
			while ($row = $this->re->fetch_assoc()) {
				$result[$row[$index]] = $row;
			}
		}
		return $result;
	}
}

?>