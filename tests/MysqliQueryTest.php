<?php 
namespace Tests\Mysql;

use PHPUnit\Framework\TestCase;
use App\Mysql\MysqliQuery;

class MysqliQueryTest extends TestCase
{
	private $dbObject;

	public function setUp()
	{
		$this->dbObject = new MysqliQuery('localhost', 'test', '', '');
	}
	public function testQueryBuilder()
	{	

		//	Test function get()
		$this->dbObject->get('tableDb', ['col1', 'col2'], 10);
		$this->assertEquals('SELECT col1, col2 FROM tableDb LIMIT 10', $this->dbObject->getLastQuery());

		//	Test setFields
		$this->dbObject->setFields(['col1', 'col2'])->limit(10)->get('tableDb');
		$this->assertEquals('SELECT col1, col2 FROM tableDb LIMIT 10', $this->dbObject->getLastQuery());
		$this->dbObject->setFields('col1')->get('tableDb', 'col2', 10);
		$this->assertEquals('SELECT col2 FROM tableDb LIMIT 10', $this->dbObject->getLastQuery());

		$this->dbObject->limit(1, 10)->get('tableDb', 'col1');
		$this->assertEquals('SELECT col1 FROM tableDb LIMIT 1, 10', $this->dbObject->getLastQuery());

		//	Test where and (default is AND)
		$this->dbObject->where('col1', '>', 10)->get('tableDb');
		$this->assertEquals('SELECT * FROM tableDb WHERE col1 > 10', $this->dbObject->getLastQuery());

		$this->dbObject->where('col1', 'between', [100, 1000])->get('tableDb', 'col1');
		$this->assertEquals('SELECT col1 FROM tableDb WHERE col1 BETWEEN 100 AND 1000', $this->dbObject->getLastQuery());

		$this->dbObject->where('col1', 'in', [1,2,3,4])->get('tableDb', 'col1', 10);
		$this->assertEquals('SELECT col1 FROM tableDb WHERE col1 IN (1,2,3,4) LIMIT 10', $this->dbObject->getLastQuery());

		$this->dbObject->where('col1', '=', 10)->orWhere('col1', '=', 2)->get('tableDb', '', 10);
		$this->assertEquals('SELECT * FROM tableDb WHERE col1 = 10 OR col1 = 2 LIMIT 10', $this->dbObject->getLastQuery());

		$this->dbObject->where('col1', 'like', '%some_thing%')->get('tableDb', 'col1, col2', 10);
		$this->assertEquals('SELECT col1, col2 FROM tableDb WHERE col1 LIKE \'%some_thing%\' LIMIT 10', $this->dbObject->getLastQuery());

		//	Test having vs group by
		$this->dbObject->having('COUNT(col1)', '>', 5)->groupBy(['col1', 'col2'])->get('tableDb', ['COUNT(col1)', 'col2']);
		$this->assertEquals('SELECT COUNT(col1), col2 FROM tableDb GROUP BY col1, col2 HAVING COUNT(col1) > 5', $this->dbObject->getLastQuery());

		//	Test orderBy
		$this->dbObject->orderBy('col1', 'asc')->get('tableDb');
		$this->assertEquals('SELECT * FROM tableDb ORDER BY col1 ASC', $this->dbObject->getLastQuery());

		//	Test join
		$this->dbObject->join('joinTable', 'joinTable.col1 = tableDb.col2', 'INNER')->get('tableDb', 'joinTable.col1, joinTable.col2, tableDb.col1');
		$this->assertEquals('SELECT joinTable.col1, joinTable.col2, tableDb.col1 FROM tableDb INNER JOIN joinTable ON joinTable.col1 = tableDb.col2', $this->dbObject->getLastQuery());

		//	Test join with set Fields
		$this->dbObject->setFields(['joinTable.col1', 'joinTable.col2', 'tableDb.col1'])->join('joinTable', 'joinTable.col1 = tableDb.col2', 'LEFT')->get('tableDb', '', 10);
		$this->assertEquals('SELECT joinTable.col1, joinTable.col2, tableDb.col1 FROM tableDb LEFT JOIN joinTable ON joinTable.col1 = tableDb.col2 LIMIT 10', $this->dbObject->getLastQuery());

		// Test join where
		$this->dbObject->join('joinTable', 'joinTable.col1 = tableDb.col2', 'RIGHT')->where('tableDb.col1', '>', 2)->get('tableDb', 'tableDb.col1, joinTable.col2');
		$this->assertEquals('SELECT tableDb.col1, joinTable.col2 FROM tableDb RIGHT JOIN joinTable ON joinTable.col1 = tableDb.col2 WHERE tableDb.col1 > 2', $this->dbObject->getLastQuery());

		$this->dbObject->join('joinTable', 'joinTable.col1 = tableDb.col2', 'LEFT')->addAndTojoin('joinTable.col2', '=', 'tableDb.col1')->get('tableDb', 'tableDb.col1, joinTable.col1');
		$this->assertEquals('SELECT tableDb.col1, joinTable.col1 FROM tableDb LEFT JOIN joinTable ON joinTable.col1 = tableDb.col2 AND joinTable.col2 = tableDb.col1', $this->dbObject->getLastQuery());

		// Test union
		$this->dbObject->merge($this->dbObject->setFields(['col1', 'col2'])->where('col1', '=', 2)->get('unionTable'))->where('col1', '>', 3)->get('tableDb', 'col1, col2', 10);
		$this->assertEquals('SELECT col1, col2 FROM tableDb WHERE col1 > 3 LIMIT 10 UNION SELECT col1, col2 FROM unionTable WHERE col1 = 2', $this->dbObject->getLastQuery());

		// Test intersect
		$this->dbObject->merge($this->dbObject->setFields(['col1', 'col2'])->where('col1', '=', 2)->get('unionTable'), 'INTERSECT')->where('col1', '>', 3)->get('tableDb', 'col1, col2', 10);
		$this->assertEquals('SELECT col1, col2 FROM tableDb WHERE col1 > 3 LIMIT 10 INTERSECT SELECT col1, col2 FROM unionTable WHERE col1 = 2', $this->dbObject->getLastQuery());

		//	Test subquery
		$subQuery = $this->dbObject->subQuery();
		$subQuery->setFields('col1')->where('col1', '>', 2)->get('subTable');
		$this->dbObject->where('col1', 'NOT EXISTS', $subQuery)->get('tableDb', 'col1', 1);
		$this->assertEquals('SELECT col1 FROM tableDb WHERE col1 NOT EXISTS (SELECT col1 FROM subTable WHERE col1 > 2) LIMIT 1', $this->dbObject->getLastQuery());

		$subQuery = $this->dbObject->subQuery();
		$subQuery->where('col1', '=', 1)->get('subTable', 'col1', 1);
		$this->dbObject->where('col1', '=', $subQuery)->get('tableDb', 'col1, col2', 1);
		$this->assertEquals('SELECT col1, col2 FROM tableDb WHERE col1 = (SELECT col1 FROM subTable WHERE col1 = 1 LIMIT 1) LIMIT 1', $this->dbObject->getLastQuery());

		$subQuery = $this->dbObject->subQuery('b');
		$subQuery->where('col1', '>', 2)->get('subTable', 'col1');
		$this->dbObject->where('col1', 'IN', ['1', '2', '3'])->get($subQuery, 'col1');
		$this->assertEquals('SELECT col1 FROM (SELECT col1 FROM subTable WHERE col1 > 2) AS b WHERE col1 IN (1,2,3)', $this->dbObject->getLastQuery());
		
		$this->dbObject->setQueryOption('sql_no_cache');
		$this->dbObject->get('tableDb', ['col1', 'col2'], 10);
		$this->assertEquals('SQL_NO_CACHE', $this->dbObject->getLastQueryOption());
		$this->assertEquals('SELECT SQL_NO_CACHE col1, col2 FROM tableDb LIMIT 10', $this->dbObject->getLastQuery());

		// Test insert
		$this->dbObject->insert('tableDb',['firstName', 'lastName', 'email', 'website'], [['luu', 'doanh', 'luudoanh26@gmail.com', 'https://github.com/luudoanh'], ['tran', 'tuan', 'trantuan12@gmail.com', 'https://trantuan.com']]);
		$this->assertEquals("INSERT  INTO tableDb(firstName, lastName, email, website) VALUES('luu','doanh','luudoanh26@gmail.com','https://github.com/luudoanh'),('tran','tuan','trantuan12@gmail.com','https://trantuan.com')", $this->dbObject->getLastQuery());

		$this->dbObject->setQueryOption('DELAYED')->setFields(['firstName', 'lastName', 'email', 'website'])->setInsertValues(['luu', 'doanh', 'luudoanh26@gmail.com', 'https://github.com/luudoanh'])->insert('tableDb');
		$this->assertEquals("INSERT DELAYED INTO tableDb(firstName, lastName, email, website) VALUES('luu','doanh','luudoanh26@gmail.com','https://github.com/luudoanh')", $this->dbObject->getLastQuery());

		$this->dbObject->setFields(['firstName', 'lastName', 'email', 'website'])->setInsertValues([['luu', 'doanh', 'luudoanh26@gmail.com', 'https://github.com/luudoanh'], ['tran', 'tuan','trantuan12@gmail.com','https://trantuan.com']])->insert('tableDb');
		$this->assertEquals("INSERT  INTO tableDb(firstName, lastName, email, website) VALUES('luu','doanh','luudoanh26@gmail.com','https://github.com/luudoanh'),('tran','tuan','trantuan12@gmail.com','https://trantuan.com')", $this->dbObject->getLastQuery());

		$subQuery = $this->dbObject->subQuery();
		$subQuery->get('subTable', ['firstName', 'lastName'], 10);
		$this->dbObject->insert('tableDb', ['firstName', 'lastName'], $subQuery);
		$this->assertEquals("INSERT  INTO tableDb(firstName, lastName) SELECT firstName, lastName FROM subTable LIMIT 10", $this->dbObject->getLastQuery());

		// Test delete
		$this->dbObject->where('id', '>', 10)->delete('tableDb');
		$this->assertEquals("DELETE  FROM tableDb WHERE id > 10", $this->dbObject->getLastQuery());

		// Test update
		$this->dbObject->setPrefix('db_')->where('col1', '>', 9)->update('tableDb', ['col1' => 10, 'col2' => 12, 'col3' => 14]);
		$this->assertEquals("UPDATE  db_tableDb SET col1 = 10,col2 = 12,col3 = 14 WHERE col1 > 9", $this->dbObject->getLastQuery());

		$this->dbObject->setPrefix()->where('tableDb1.col1', '>', 10)->update(['tableDb1', 'tableDb2'], ['tableDb1.col1' => 'tableDb2.col1']);
		$this->assertEquals("UPDATE  tableDb1,tableDb2 SET tableDb1.col1 = tableDb2.col1 WHERE tableDb1.col1 > 10", $this->dbObject->getLastQuery());

		$subQuery = $this->dbObject->subQuery();
		$subQuery->where('col1', '=', '`col2`')->get('subTable', 'col2');
		$this->dbObject->update('tableDb', ['col1' => $subQuery]);
		$this->assertEquals("UPDATE  tableDb SET col1 = (SELECT col2 FROM subTable WHERE col1 = `col2`)", $this->dbObject->getLastQuery());

	}
}

?>