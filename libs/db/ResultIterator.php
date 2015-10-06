<?

namespace db;

/**
 * Обертка над mysqli_result добавляет более удобные способы работы с результатом запроса
 * Class ResultIterator
 * @package db
 */
class ResultIterator extends \mysqli_result {

    /* @var int */
    protected $_pointer = 0;

    /**
     * @param string $key
     * @param int    $result_type
     * @return array
     */
    public function fetch_all($key = '', $result_type = MYSQLI_ASSOC) {
        if ($this->field_count == 1) return $this->fetch_column();
        if (!$key) return parent::fetch_all($result_type);

        $result = [];
        while ($row = $this->fetch_assoc()) {
            $result[$row[$key]] = $row;
            unset($result[$row[$key]][$key]);
        }
        return $result;
    }

    /**
     * @param string $key
     * @return array
     */
    public function fetch_iterator_all($key = '') {
        if ($this->field_count == 1) {
            while ($row = $this->fetch_row()) yield $row[0];
        } elseif (!$key) {
            while ($row = $this->fetch_assoc()) yield $row;
        } else {
            while ($row = $this->fetch_assoc()) {
                $key = $row[$key];
                unset($row[$key]);
                yield $key => $row;
            }
        }
    }

    /**
     * возвращает колонку в виде массива
     * если в запросе было два столбца то первый столбец испльзуется в качестве ключа в ассоциативном массиве с результатами
     * @return array
     */
    public function fetch_column() {
        $result = [];
        if ($this->field_count == 1) {
            while ($row = $this->fetch_row()) $result[] = $row[0];
        } else {
            while ($row = $this->fetch_row()) $result[$row[0]] = $row[1];
        }
        return $result;
    }

    /**
     * возвращает колонку в виде итератора массива
     * если в запросе было два столбца то первый столбец испльзуется в качестве ключа в ассоциативном массиве с результатами
     * @return \Generator
     */
    public function fetch_iterator_column() {
        if ($this->field_count == 1) {
            while ($row = $this->fetch_row()) yield $row[0];
        } else {
            while ($row = $this->fetch_row()) yield $row[0] => $row[1];
        }
    }

    /**
     * возвращает одну ячейку, например для получения единственного числа из запроса "select count(*) from table"
     * @return string|null
     */
    public function fetch_one() {
        $row = $this->fetch_row();
        return (is_array($row) ? current($row) : null);
    }

    /**
     * обертка над mysqli_result::fetch_assoc - возвращает одну строку в виде ассоциативного массива
     * @return array
     */
    public function fetch_line() {
        return $this->fetch_assoc();
    }

    /**
     * возвращает обьект класса $class_name, при создании передает в конструктор обьекта ассоциативный массив записи
     * @param string $class_name      - класс создаваемого обьекта
     * @param array  $override_fields - дополнительные или перекрывающие поля записи
     * @return object|bool
     */
    public function fetch_object($class_name = '', array $override_fields = []) {
        $row = $this->fetch_assoc();
        if (!$row) return null;
        return new $class_name(($override_fields ? array_merge($row, $override_fields) : $row));
    }
}

?>