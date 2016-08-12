<?

namespace db;

use exceptions\ConnectException;
use exceptions\QueryException;
use exceptions\InvalidDataException;

/**
 * драйвер для работы с mysql, наследован от mysqli
 * Class Db
 * @package db
 */
class Db extends \mysqli {
    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $db
     * @param int    $port
     * @throws ConnectException
     */
    public function __construct($host, $user, $pass, $db, $port=null) {
        if ($port) {
            @parent::__construct($host, $user, $pass, $db, $port);
        } else {
            @parent::__construct($host, $user, $pass, $db);
        }
        if ($this->connect_error) {
            if ($port) {
                @parent::__construct($host, $user, $pass, $db, $port);
            } else {
                @parent::__construct($host, $user, $pass, $db);
            }
            if ($this->connect_error) ConnectException($this->connect_error, $this->connect_errno, $this);
	}
        //$this->set_charset('utf8');
    }

    /**
     * Обертка над mysqli::real_query выбрасывает исключение в случае ошибки выполнения запроса
     * @param string $query
     * @return bool
     * @throws QueryException
     */
    public function real_query($query) {
        if (!parent::real_query($query)) throw QueryException::create($this->error, $this->errno, $query);
        return true;
    }

    /**
     * возвращает итератор записей
     * @param string $query
     * @return ResultIterator
     */
    public function query($query) {
        $this->real_query($query);
        return new ResultIterator($this);
    }

    /**
     * возвращает обьект класса $className, при создании передает в конструктор обьекта ассоциативный массив записи
     * @param string $query
     * @param string $className
     * @param array  $override_fields набор данных перегружающий данные из результатов запроса
     * @return object|null
     */
    public function fetch_object($query, $className, array $override_fields = null) {
        return $this->query($query)->fetch_object($className, $override_fields);
    }

    /**
     * возвращает итератор обьектов
     * @param string $query
     * @param string $className
     * @param array  $override_fields набор данных перегружающий данные из результатов запроса
     * @return \Generator
     */
    public function fetch_objects_iterator($query, $className, array $override_fields = []) {
        $result = $this->query($query);
        if ($result->num_rows) foreach ($result as $row) yield new $className(($override_fields ? array_merge($row, $override_fields) : $row));
    }

    /**
     * @param string $query
     * @return bool|array
     */
    public function fetch_line($query) {
        return $this->query($query)->fetch_line();
    }

    /**
     * @param string $query
     * @return bool|mixed
     */
    public function fetch_one($query) {
        return $this->query($query)->fetch_one();
    }

    /**
     * @param string $query
     * @param string $key столбец для использования в качестве ключа ассоциативного массива
     * @return array вернет массив с результатами, в зависимости от количества колонок результат будет отличатся.
     */
    public function fetch_all($query, $key = '') {
        return $this->query($query)->fetch_all($key);
    }

    /**
     * @param string $query
     * @param string $key столбец для использования в качестве ключа ассоциативного массива
     * @return \Generator вернет итератор с результатами, в зависимости от количества колонок результат будет отличатся.
     */
    public function fetch_iterator_all($query, $key = '') {
        return $this->query($query)->fetch_iterator_all($key);
    }

    /**
     * @param string $query
     * @return array вернет массив с результатами
     * в зависимости от количества колонок результат будет отличатся, если колонок в результате запроса две или более то первая будет использована в качестве ключа, а вторая в качестве значения
     */
    public function fetch_column($query) {
        return $this->query($query)->fetch_column();
    }

    /**
     * @param string $query
     * @return \Generator вернет массив с результатами
     * в зависимости от количества колонок результат будет отличатся, если колонок в результате запроса две или более то первая будет использована в качестве ключа, а вторая в качестве значения
     */
    public function fetch_iterator_column($query) {
        return $this->query($query)->fetch_iterator_column();
    }

    /**
     * экранирует данные для подстановки в sql запрос
     * @param RawSql|string|int|float|null|array $val переменная либо массив переменных для фильтрацией перед вставкой в sql запрос
     * @return string|int|float|array
     */
    public function escape($val) {
        if (is_array($val)) foreach ($val as $key => $value) $val[$key] = $this->escape($value);
        if (is_a($val, 'db\RawSql')) return $val();
        if (is_null($val)) return 'null';
        return "'".$this->escape_string($val)."'";
    }

    /**
     * генерирует часть insert|replace запроса для из ассоциативного массива
     * @param array $data двухмерный массив типа [['column' => 'value', 'column2' => 'value2'], ...], либо одномерный ['column' => 'value', 'column2' => 'value2']
     * @return string кусок sql запроса для вставки или замены по ассоциативному массиву
     */
    public function implodeInsertSql(array $data) {
        if (!is_array(current($data))) $data = [$data];
        $values = [];
        $keys   = array_keys(current($data));
        foreach ($data as $data_line) {
            $value = [];
            foreach ($data_line as $val) $value[] = $this->escape($val);
            $values[] = '('.implode(', ', $value).')';
        }
        return '(`'.implode('`, `', $keys)."`) values\n".implode(",\n", $values);
    }

    /**
     * генерирует часть запроса с условиями для where из ассоциативного массива
     * @param array $condition массив типа ['column' => 'value', 'column2' => 'value2'] который преобразуется в условия выборки WHERE через AND
     * @return string
     */
    public function implodeWhereSql(array $condition, $prefix = '') {
        $where = [];
        foreach ($condition as $key => $val) $where[] = ($prefix ? '`'.$prefix.'`.' : ''). '`'.$key.'` '.(is_a($val, 'db\RawSqlStmt') ? $val() : '= '.$this->escape($val));
        return implode("\n AND ", $where);
    }

    /**
     * @param string $table таблица куда будет инсертится
     * @param array  $data  двухмерный массив типа [['column' => 'value', 'column2' => 'value2'], ...], либо одномерный ['column' => 'value', 'column2' => 'value2']
     * @return bool
     */
    public function insert($table, array $data) {
        if (!is_array($data)) return false;
        return $this->real_query("INSERT INTO `".$table."` \n".$this->implodeInsertSql($data));
    }

    /**
     * @param string $table таблица куда будет реплейсится
     * @param array  $data       двухмерный массив типа [['column' => 'value', 'column2' => 'value2'], ...], либо одномерный ['column' => 'value', 'column2' => 'value2']
     * @return bool
     */
    public function replace($table, array $data) {
        if (!is_array($data)) return false;
        return $this->real_query("REPLACE INTO `".$table."` \n".$this->implodeInsertSql($data));
    }

    /**
     * @param string $table      таблица где будет апдейтится
     * @param array  $data       массив типа ['column' => 'value', 'column2' => 'value2']
     * @param array  $condition  массив типа ['column' => 'value', 'column2' => 'value2'] который преобразуется в условия выборки через AND
     * @param string $where      условие типа 'user_id = 12'
     * @return bool
     */
    public function update($table, array $data, array $condition = [], $where = '') {
        if (!is_array($data)) return false;
        $set = [];
        foreach ($data as $name => $val) $set[] = '`'.$name.'` '.(is_a($val, 'db\RawSqlStmt') ? $val() : '= '.$this->escape($val));
        $where .= ($where ? "\n AND " : '').$this->implodeWhereSql($condition);
        return $this->real_query("UPDATE `".$table."`\nSET ".implode("\n, ", $set).($where ? "\nWHERE ".$where : ''));
    }

    /**
     * @param string $table      таблица откуда будет удалятся
     * @param array  $condition  массив типа ['column' => 'value', 'column2' => 'value2'] который преобразуется в условия выборки через AND
     * @param string $where      условие типа 'user_id = 12'
     * @return bool
     */
    public function delete($table, array $condition = [], $where = '') {
        $where .= ($where ? "\n AND " : '').$this->implodeWhereSql($condition);
        return $this->real_query("DELETE FROM `".$table."` ".($where ? "\nWHERE ".$where : ''));
    }

    /**
     * @param string $table      таблица откуда селектить
     * @param array  $condition  массив типа ['column' => 'value', 'column2' => 'value2'] который преобразуется в условия выборки через AND
     * @param string $where      дополнтильеные условия типа 'user_id IN (12, 13, 14)'
     * @return bool
     */
    public function find($table, array $condition = [], $where = '') {
        $where .= ($where ? "\n AND " : '').$this->implodeWhereSql($condition);
        return $this->query("SELECT * FROM `".$table."` ".($where ? 'WHERE '.$where : ''));
    }

    /**
     * Возвращщает список таблиц текущей бд
     * @return array
     */
    public function getTables() {
        return $this->fetch_all("show tables");
    }

    /**
     * @param callable $callback
     * @return bool
     */
    public function transactions(callable $callback) {
        $result = true;
        $this->autocommit(false);
        try {
            if ($callback()) {
                $this->commit();
            } else {
                $this->rollback();
                $result = false;
            }
        } catch (QueryException $e) {
            $this->rollback();
            $result = false;
        }
        $this->autocommit(true);
        return $result;
    }
}


