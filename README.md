# Class used to connection and build query statement
Đây là một class mà được mình viết dựa trên nguồn này [https://github.com/ThingEngineer/PHP-MySQLi-Database-Class](https://github.com/ThingEngineer/PHP-MySQLi-Database-Class). Và chỉ làm mục đích học tập của mình
# Installation
### Installation nomal
Các bạn có thể include trực tiếp file MysqliQuery.php vào project của mình

```php
<?php
include_once 'MysqliQuery.php';
?>
```
### Installation with composer
Nếu bạn sử dụng composer thì có thể chạy lệnh để clone project này về
```
composer require luudoanh/class-db-mysqli
```
# Initialization
Khởi tạo class cơ bản bằng cách
```php
<?php
use App\Mysql;
$dbObject = new MysqliQuery('hostname', 'dbUser', 'password', 'dbName');
?>
```
Khởi tạo class với tất cả các tham số
```php
<?php
use App\Mysql;
$dbObject = new MysqliQuery('hostname', 'dbUser', 'password', 'dbName', 'port', 'charset', 'prefix');
?>
```
3 tham số port. charset và prefix là không bắt buộc nếu bạn không truyền vào thì giá trị mặc định của chúng lần lượt là 3306, utf8, null
# Use
### Get
```php
<?php
use App\Mysql;
$dbObject = new MysqliQuery('hostname', 'dbUser', 'password', 'dbName', 'port', 'charset', 'prefix');
$dbObject->get('tableDb');
// SELECT * FROM tableDb

$dbObject->getOne('tableDb');
// SELECT * FROM tableDb LIMIT 1

$dbObject->get('tableDb', ['col1', 'col2'], 10);
// SELECT col1, col2 FROM tableDb LIMIT 10

$dbObject->setPrefix('db_')->get('tableDb');
// SELECT * FROM db_tableDb

$dbObject->setFields(['col1', 'col2'])->get('tableDb');
// SELECT col1, col2 FROM tableDb
?>
```

### Where get
```php
<?php
$dbOject->where('col1', '>', 2)->get('tableDb');
// SELECT * FROM tableDb WHERE col1 > 2

$dbObject->where('col1', 'like', '%some_thing%')->get('tableDb');
// SELECT * FROM tableDb WHERE col1 LIKE '%some_thing%'

$dbObject->where('col1', 'between', [10, 100])->get('tableDb');
// SELECT * FROM tableDb WHERE col1 BETWEEN 10 AND 100

$dbObject->where('col1', 'in', [1,2,3,4])->get('tableDb');
// SELECT * FROM tableDb WHERE col1 IN (1,2,3,4)

$dbObject->where('col1', '>', 2)->orWhere('col2', '=', 3)->get('tableDb');
// SELECT * FROM tableDb WHERE col1 > 2 OR col2 = 3
?>
```

### Group By
```php
<?php
$dbObject->groupBy('col1')->get('tableDb');
// SELECT * FROM tableDb GROUP BY col1

$dbObject->groupBy(['col1', 'col2'])->get('tableDb');
// SELECT * FROM tableDb GROUP BY col1, col2
?>
```

### Having 
```php
<?php
$dbObject->groupBy('col1')->having('COUNT(col1)', '>', 10)->get('tableDb', 'COUNT(col1), col2', 10);
// SELECT COUNT(col1), col2 FROM tableDb HAVING COUNT(col1) > 10 GROUP BY col1 LIMIT 10
?>
```

### OrderBy
```php
<?php
$dbObject->orderBy('col1', 'asc')->get('tableDb');
// SELECT * FROM tableDb ORDER BY col1 ASC
?>
```

### Limit
```php
<?php
$dbObject->limit(10)->get('tableDb', 'col1');
// SELECT col1 FROM tableDb LIMIT 10

$dbObject->limit(10, 100)->get('tableDb', 'col1');
// SELECT col1 FROM tableDb LIMIT 10, 100
?>
```

### Join Get
```php
<?php
$dbObject->join('tableJoin', 'tableJoin.col1 = tableDb.col2', 'left')->get('tableDb', ['tableDb.col2', 'tableJoin.col1', 'tableJoin.col2'], 10);
// SELECT tableDb.col2, tableJoin.col1, tableJoin.col2 FROM tableDb LEFT JOIN tableJoin ON tableDb.col2 = tableJoin.col1 LIMIT 10
// Kiểu join trong tham số thứ 3 của function join() bạn có thể dử dụng INNER, LEFT, LEFT OUTER, RIGHT, RIGHT OUTER

$dbObject->join('tableJoin j', 'j.col1 = t.col1', 'right outer')->get('tableDb t', ['t.col1', 'j.col1', 't.col2', 'j.col2'], 10);
// SELECT t.col1, j.col1, t.col2, j.col2 FROM tableDb t RIGHT OUTER JOIN tableJoin ON j.col1 = t.col2 LIMIT 10
$dbObject->join('tableJoin j', 'j.col1 = t.col1', 'right outer')->addAndToJoin('j.col2', '>', 3)->get('tableDb t', ['t.col1', 'j.col1', 't.col2', 'j.col2'], 10);
// SELECT t.col1, j.col1, t.col2, j.col2 FROM tableDb t RIGHT OUTER JOIN tableJoin ON j.col1 = t.col2 AND j.col2 > 3 LIMIT 10
// Sử dụng tương tự với function addOrToJoin()
?>
```

### Union
```php
<?php
$dbObject->merge($dbObject->get('tableDb2', 'col1', 1), 'UNION')->where('col1', '=', 1)->get('tableDb1', 'col1, col2', 1);
// SELECT col1, col2 FROM tableDb1 WHERE col1 = 1 LIMIT 1 UNION ALL SELECT col1 FROM tableDb2 LIMIT 1
// Tham số thứ 2 trong hàm union() nếu là true sẽ là UNION ALL
?>
```

### Intersect
```php
<?php
$this->dbObject->merge($this->dbObject->setFields(['col1', 'col2'])->where('col1', '=', 2)->get('unionTable'), 'INTERSECT')->where('col1', '>', 3)->get('tableDb', 'col1, col2', 10);
//SELECT col1, col2 FROM tableDb WHERE col1 > 3 LIMIT 10 INTERSECT SELECT col1, col2 FROM unionTable WHERE col1 = 2
?>
```

### Subquery
```php
<?php
$subQuery = $dbObject->subQuery();
$subQuery->where('col1', '=', 2)->get('subTable', 'col2');
$dbObject->where('col1', 'in', $subQuery)->get('tableDb', 'col1, col2', 1);
// SELECT col1, col2 FROM tableDb WHERE col1 IN (SELECT col2 FROM subTable WHERE col1 = 2) LIMIT 1

$subQuery = $dbObject->subQuery();
$subQuery->where('col1', '=', 1)->get('subTable', 'col1', 1);
$dbObject->where('col1', '=', $subQuery)->get('tableDb', 'col1, col2', 1);
// SELECT col1, col2 FROM tableDb WHERE col1 = (SELECT col1 FROM subTable WHERE col1 = 1 LIMIT 1) LIMIT 1

$subQuery = $dbObject->subQuery('sub');
$subQuery->where('col1', '=', 2)->get('subTable', 'col2');
$dbObject->get($subQuery, 'col1, col2', 1);
// SELECT col1, col2 FROM (SELECT col2 FROM subTable WHERE COL1 = 2) AS sub LIMIT 1

$dbObject->setQueryOption('sql_no_cache');
$dbObject->get('tableDb', ['col1', 'col2'], 10);
// SELECT SQL_NO_CACHE col1, col2 FROM tableDb LIMIT 10
?>
```

### Insert
```php
<?php
// Simple insert
$this->dbObject->setQueryOption('DELAYED')->setFields(['firstName', 'lastName', 'email', 'website'])->insertValues(['luu', 'doanh', 'luudoanh26@gmail.com', 'https://github.com/luudoanh'])->insert('tableDb');
// INSERT DELAYED INTO tableDb(firstName, lastName, email, website) VALUES('luu','doanh','luudoanh26@gmail.com','https://github.com/luudoanh')

// Multi insert
$this->dbObject->insert('tableDb',['firstName', 'lastName', 'email', 'website'], [['luu', 'doanh', 'luudoanh26@gmail.com', 'https://github.com/luudoanh'], ['tran', 'tuan', 'trantuan12@gmail.com', 'https://trantuan.com']]);
// INSERT  INTO tableDb(firstName, lastName, email, website) VALUES('luu','doanh','luudoanh26@gmail.com','https://github.com/luudoanh'),('tran','tuan','trantuan12@gmail.com','https://trantuan.com')

// Insert with subquery values
$subQuery = $this->dbObject->subQuery();
$subQuery->get('subTable', ['firstName', 'lastName'], 10);
$this->dbObject->insert('tableDb', ['firstName', 'lastName'], $subQuery);
// INSERT  INTO tableDb(firstName, lastName) SELECT firstName, lastName FROM subTable LIMIT 10
?>
```

### Replace
```php
<?php
$this->dbObject->insert('tableDb',['firstName', 'lastName', 'email', 'website'], ['luu', 'doanh', 'luudoanh26@gmail.com', 'https://github.com/luudoanh'], 'replace');
// REPLACE  INTO tableDb(firstName, lastName, email, website) VALUES('luu','doanh','luudoanh26@gmail.com','https://github.com/luudoanh')
?>
```

### Delete
```php
<?php
$this->dbObject->where('id', '>', 10)->delete('tableDb');
// DELETE  FROM tableDb WHERE id > 10
?>
```

### Update
```php
<?php
$this->dbObject->setPrefix('db_')->where('col1', '>', 9)->update('tableDb', ['col1' => 10, 'col2' => 12, 'col3' => 14]);
// UPDATE  db_tableDb SET col1 = 10,col2 = 12,col3 = 14 WHERE col1 > 9

$this->dbObject->setPrefix()->where('tableDb1.col1', '>', 10)->update(['tableDb1', 'tableDb2'], ['tableDb1.col1' => 'tableDb2.col1']);
// UPDATE  tableDb1,tableDb2 SET tableDb1.col1 = tableDb2.col1 WHERE tableDb1.col1 > 10

// Nếu muốn truyền một column vào mạnh đề where hãy thêm vào dấu `` VD: `col2`
$subQuery = $this->dbObject->subQuery();
$subQuery->where('col1', '=', '`col2`')->get('subTable', 'col2');
$this->dbObject->update('tableDb', ['col1' => $subQuery]);
//UPDATE  tableDb SET col1 = (SELECT col2 FROM subTable WHERE col1 = `col2`)
?>

```

