<?

namespace models;

use \db\Db;
use Generator;
use Validator;

/**
 * Class Model
 *
 * @package models
 */
abstract class Model implements \ArrayAccess {
    /* @var array таблица преобразования типов из mysql в php */
    public static $map_types_mysql2php = [
        'tinyint'    => 'int',
        'smallint'   => 'int',
        'mediumint'  => 'int',
        'bigint'     => 'int',
        'int'        => 'int',
        'year'       => 'int',

        'numeric'    => 'float',
        'decimal'    => 'float',
        'float'      => 'float',
        'double'     => 'float',
        'real'       => 'float',

        'set'        => 'array',

        'enum'       => 'string',
        'time'       => 'string',
        'date'       => 'string',
        'datetime'   => 'string',
        'timestamp'  => 'string',
        'varchar'    => 'string',
        'char'       => 'string',
        'binary'     => 'string',
        'varbinary'  => 'string',
        'tinyblob'   => 'string',
        'blob'       => 'string',
        'mediumblob' => 'string',
        'longblob'   => 'string',
        'tinytext'   => 'string',
        'text'       => 'string',
        'mediumtext' => 'string',
        'longtext'   => 'string'
    ];

    /* @var string $_table таблица модели */
    protected static $_table = '';

    /* @var array $_columns набор колонок */
    protected static $_columns = [];

    /* @var array $_keys ключи таблицы включая внешние связи */
    protected static $_keys = [];

    /**
     * внешние ключи на другие таблицы (поля которые привязанны к данным из других таблиц)
     * @var array $_references
     * например:
     *    [
     *        't2_id' => [        // имя колонки текущей таблицы
     *            'col' => 'id',    // имя колонки связанной таблицы
     *            'tbl' => 't2'    // имя связанной таблицы
     *        ],
     *         ...
     *     ]
     */
    protected static $_references = [];

    /**
     * внешние связи на текущую таблицу
     * @var array $_relations
     * например:
     *    [
     *        'id' => [            // имя колонки текущей таблицы
     *            'col' => 't2_id',    // имя колонки связанной таблицы
     *            'tbl' => 't1'        // имя связанной таблицы
     *        ],
     *         ...
     *     ]
     */
    protected static $_relations = [];

    /* @var array $_item текущая запись которой проинициализированна модель*/
    protected $_item = [];

    /* @var array $_pk первичный индекс либо все уникальные индексы либо [] если таковых нет */
    protected $_pk = [];

    /* @var array $_update_columns колонки по которым были изменены данные (используется в update) */
    protected $_update_columns = [];

    /* @var array $_pk_columns содежрит названия колонок по фильтру первичный индекс либо все уникальные индексы либо [] если таковых нет */
    protected static $_pk_columns = [];

    /* @var string $_ai_column колонка с автоинкриментом */
    protected static $_ai_column = '';

    /* @var string инстанс класса бд (наследованного от mysqli) */
    public static $_db = 'db';

    /* @var array костыль для автообновления нескалярных записей */
    private $_item_old_val;

    /**
     * возвращает имя таблицы
     * @return string
     */
    public static function _table() {
        return static::$_table;
    }

    /**
     * возвращает инстанс модели
     * @return object
     */
    public static function model() {
        $class = static::modelName();
        return new $class;
    }

    /**
     * возвращает класс модели
     * @return object
     */
    public static function modelName() {
        return get_called_class();
    }

    /**
     * @return Db
     */
    public static function db() {
        $db = static::$_db;
        return $db();
    }

    /**
     * @param array $condition
     * @param array $override_fields
     * @param array $order_by
     * @return Model|null
     */
    public static function find(array $condition = [], array $override_fields = [], array $order_by = []) {
        $sql = static::db()->implodeWhereSql($condition);
        $sql = "SELECT * FROM `".static::$_table."` ".($sql ? 'WHERE '.$sql : '');
        if (!empty($order_by)) {
            foreach ($order_by as $col => $order) $order_by[$col] = "`$col` $order";
            $sql .= " ORDER BY ".implode(', ', $order_by);
        }
        $sql .= " LIMIT 1";
        return static::findBySql($sql, $override_fields);
    }

    /**
     * @param string $sql
     * @param array  $override_fields
     * @return Model|null
     */
    public static function findBySql($sql, array $override_fields = []) {
        return static::db()->fetch_object($sql, static::modelName(), $override_fields);
    }

    /**
     * @param array $condition
     * @param array $override_fields
     * @return Generator
     */
    public static function findAll(array $condition = [], array $override_fields = []) {
        $sql = static::db()->implodeWhereSql($condition);
        $sql = "SELECT * FROM `".static::$_table."` ".($sql ? 'WHERE '.$sql : '');
        return static::findAllBySql($sql, $override_fields);
    }

    /**
     * @param array $cols
     * @param array $condition
     * @return array
     */
    public static function fetchCols(array $cols = ['id'], array $condition = []) {
        $sql = static::db()->implodeWhereSql($condition);
        $sql = "SELECT ".implode(',', $cols)." FROM `".static::$_table."` ".($sql ? 'WHERE '.$sql : '');
        return static::db()->fetch_column($sql);
    }

    /**
     * @param string $sql
     * @param array  $override_fields
     * @return Generator
     */
    public static function findAllBySql($sql, array $override_fields = []) {
        return static::db()->fetch_objects_iterator($sql, static::modelName(), $override_fields);
    }

    /**
     * @param array $condition
     * @return bool
     */
    public static function deleteAll(array $condition = []) {
        return static::db()->delete(static::$_table, $condition);
    }

    /**
     * @param array $data
     * @param array $condition
     * @return bool
     */
    public static function updateAll(array $data, array $condition = []) {
        return static::db()->update(static::$_table, $data, $condition);
    }

    /**
     * @param array $data - двухмерный массив
     * @return bool
     */
    public static function insertAll(array $data) {
        return static::db()->insert(static::$_table, $data);
    }

    /**
     * @param array $data - двухмерный массив
     * @return bool
     */
    public static function replaceAll(array $data) {
        return static::db()->replace(static::$_table, $data);
    }

    /**
     * @param string $col
     * @throws \Exception
     */
    protected static function _checkCol($col) {
        if (!isset(static::$_columns[$col])) throw new \Exception('Неизвестная колонка '.__CLASS__.'::'.$col);
    }

    /**
     * @param array $row - набор данных для инициалзиации обьекта
     * @throws \Exception
     */
    public function __construct(array $row = []) {
        if ($row) {
            $this->pkSet($row);
            $row = $this->unpack($row);
            foreach ($row as $key => $val) {
                static::_checkCol($key);
                $method = 'set'.capitalize($key);
                if (method_exists($this, $method)) {
                    $this->$method($val);
                } else {
                    $this->_item[$key] = $val;
                }
            }
        }
    }

	/**
	 */
	public function pkGet() {
		return $this->_pk;
	}

    /**
     * находит и сохраняет первичный ключ текущей записи
     * @param array $row - набор данных для инициалзиации обьекта
     */
    protected function pkSet(array $row) {
        $this->_pk = [];
        if (static::$_pk_columns) foreach (static::$_pk_columns as $key) if (isset($row[$key])) $this->_pk[$key] = $row[$key];
    }

    /**
     * @param array $data массив с данными для подготовки к сохранению
     * @return array
     */
    protected function pack(array $data) {
        return $data;
    }

    /**
     * @param array $data массив с данными для подготовки к инициализации
     * @return array
     */
    protected function unpack(array $data) {
        foreach (static::$_columns as $col_name => $col) if (isset(static::$map_types_mysql2php[$col['type']], $data[$col_name])) {
            if (static::$map_types_mysql2php[$col['type']] == 'int')   $data[$col_name] = intval(  $data[$col_name]);
            if (static::$map_types_mysql2php[$col['type']] == 'float') $data[$col_name] = floatval($data[$col_name]);
        }
        return $data;
    }

    /**
     * @param $col
     * @return array
     * @throws \Exception
     */
    public static function getAllowValues($col) {
        static::_checkCol($col);
        if (!isset(static::$_columns[$col]['allow_values'])) throw new \Exception('Не соответствует тип колонки '.__CLASS__.'::'.$col);
        return static::$_columns[$col]['allow_values'];
    }

    /**
     * обновить из базы
     * @param array $override_fields
     * @return bool
     */
    public function refresh(array $override_fields = []) {
        $sql = static::db()->implodeWhereSql($this->_pk);
        $sql = "SELECT * FROM `".static::$_table."` ".($sql ? 'WHERE '.$sql : '')." LIMIT 1";
        $row = static::db()->fetch_line($sql);
        if (!$row) return null;
        $this->__construct(($override_fields ? array_merge($row, $override_fields) : $row));
        return $this;
    }

    /**
     * @return bool
     */
    public function delete() {
        if (!$this->_pk || !static::deleteAll($this->_pk)) return false;
        $this->_pk = [];
        return true;
    }

    /**
     * @param array $row
     * @return bool|array
     */
    public function update(array $row = []) {
        if ($row) foreach ($row as $key => $val) $this->__set($key, $val);
        if (!$this->_pk || !min($this->validate($this->_item))) return false;
        if (!empty($this->_item_old_val)) {
            foreach ($this->_item_old_val as $key => $val) {
                if ($this->_item[$key] != $val) $this->_update_columns[$key] = true;
            }
        }
        if (!$this->_update_columns) return []; // если изменений полей небыло то и в базу ломится незачем.
        $raw_row = array_intersect_key($this->_item, $this->_update_columns);
        $row = $this->pack($raw_row);
        if (!$raw_row || !$row) return []; // если полей для сохранения нет то и в базу ломится незачем.
        static::updateAll($row, $this->_pk);
        $this->pkSet($row + $this->_pk);
        return $raw_row;
    }

    /**
     * @param array $row
     * @return bool|array
     */
    public function insert(array $row = []) {
        if ($row) foreach ($row as $key => $val) $this->__set($key, $val);
        if (!min($this->validate($this->_item))) return false;
        $row = $this->pack($this->_item);
        if (!static::insertAll($row, $this->_pk)) return false;
        if (static::$_ai_column) $row[static::$_ai_column] = $this->_item[static::$_ai_column] = static::db()->insert_id;
        $this->pkSet($row);
        return $this->_item;
    }

    /**
     * @return bool
     */
    public function replace() {
        if (!min($this->validate($this->_item))) return false;
        $row = $this->pack($this->_item);
        if (!static::replaceAll($row, $this->_pk)) return false;
        if (static::$_ai_column) $row[static::$_ai_column] = $this->_item[static::$_ai_column] = static::db()->insert_id;
        $this->pkSet($row);
        return true;
    }

    /**
     * @return bool
     */
    public function save() {
        if ($this->_pk) return $this->update();
        return $this->insert();
    }

    /**
     * @param array $row
     * @return array
     */
    public function validate(array $row = []) {
        if (!$row) $row = $this->_item;
        $return = [];
        foreach (static::$_columns as $col_name => $col) {
            if (method_exists($this, 'validate_'.$col_name)) {
                $return[$col_name] = $this->{'validate_'.$col_name}($row[$col_name]);
            } elseif (is_null($row[$col_name])) {
                $return[$col_name] = ($col['is_null'] || !empty($col['auto_increment']));
            } elseif ($col['type'] == 'serialize') {
                $return[$col_name] = true;
            } elseif ($col['type'] == 'json') {
                $return[$col_name] = true;
            } elseif ($col['type'] == 'timestamp') {
                $return[$col_name] = ((!empty($col['on_update_CURRENT_TIMESTAMP']) && is_null($row[$col_name])) || Validator::preg('datetime', $row[$col_name]));
            } elseif ($col['type'] == 'date' || $col['type'] == 'time' || $col['type'] == 'datetime') {
                $return[$col_name] = Validator::preg($col['type'], $row[$col_name]);
            } elseif ($col['type'] == 'set') {
                $return[$col_name] = Validator::set($row[$col_name], $col['allow_values']);
            } elseif ($col['type'] == 'enum') {
                $return[$col_name] = Validator::enum($row[$col_name], $col['allow_values']);
            } elseif ($col['php_type'] == 'string') {
                $return[$col_name] = Validator::string($row[$col_name], (isset($col['length']) ? $col['length'] : 0));
            } else {
                $return[$col_name] = Validator::preg($col['php_type'], $row[$col_name]);
            }
        }
        return $return;
    }

    /** Сериализует список обьектов в JSON
     * @param array $condition
     * @param array $override_fields
     * @return string JSON
     */
    public static function jsonList(array $condition = [], array $override_fields = []) {
        return json_encode(static::iterator2Array(static::findAll($condition, $override_fields)));
    }

    /**
     * @param Generator $iterator
     * @return string JSON
     */
    public static function iterator2Array(Generator $iterator) {
        $data = [];
        /* @var Model $model */
        foreach ($iterator as $model) $data[] = $model->row();
        return $data;
    }

    /** Сериализует текущий обьект в JSON
     *
     * @param array $with
     * @return string JSON
     */
    public function toJson(array $with = []) {
        return json_encode($this->row($with));
    }

    /**
     * @param string $key
     * @throws \Exception
     * @return mixed|null
     */
    public function &__get($key) {
        $method = 'get'.capitalize($key);
        if (method_exists($this, $method)) {
            $tmp = $this->$method();
            return $tmp;
        }

        static::_checkCol($key);
        $ret_val = (!isset($this->_item[$key]) ? null : $this->_item[$key]);
        if (is_array($ret_val)) {
            $this->_item_old_val[$key] = $this->_item[$key];
            $ret_val = &$this->_item[$key];
        }
        return $ret_val;
    }

    /**
     * @param string $key
     * @param mixed  $val
     * @throws \Exception
     * @return mixed|null
     */
    public function __set($key, $val) {
        static::_checkCol($key);
        $old_val = $this->_item[$key];
        $method = 'set'.capitalize($key);
        if (method_exists($this, $method)) {
            $this->$method($val);
        } else {
            $this->_item[$key] = $val;
        }
        if ($old_val != $this->_item[$key]) $this->_update_columns[$key] = true;
    }

    /**
     * Возвращает текущую запись в виде ассоциативного массива (например для последующей передачи в шаблонизатор)
     * @param array $with
     * @return array
     */
    public function row(array $with = []) {
        $data = [];
        foreach (array_keys(static::$_columns) as $key) {
            $method = 'get'.capitalize($key);
            if (method_exists($this, $method)) {
                $data[$key] = $this->$method();
            } else {
                $data[$key] = (isset($this->_item[$key]) ? $this->_item[$key] : null);
            }
        }

        foreach ($with as $key => $getter) {
            $data[$key] = $this->$getter();
            if ($data[$key] instanceof Generator) $data[$key] = static::iterator2Array($data[$key]);
        }
        return $data;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset($key) {
        return isset(static::$_columns[$key], $this->_item[$key]);
    }

    /**
     * @param string $key
     */
    public function __unset($key) {
        unset($this->_item[$key]);
    }

    /**
     * @param string $key
     * @return bool|void
     */
    public function offsetExists($key) {
        return $this->__isset($key);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function offsetGet($key) {
        return $this->__get($key);
    }

    /**
     * @param mixed $key
     * @param mixed $val
     */
    public function offsetSet($key, $val) {
        $this->__set($key, $val);
    }

    /**
     * @param string $key
     */
    public function offsetUnset($key) {
        $this->__unset($key);
    }
}
?>