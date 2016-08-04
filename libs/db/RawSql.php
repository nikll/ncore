<?

namespace db;

/**
 * класс хелпер, применяется в экранировании sql запросов когда надо подставить часть sql в массиве с данными
 * к примеру передаем в метод Db::escape
 * массив вида ['id' => 1, 'name' => 'tes\'ts',       'params' => null,   'text' => 'NOW()',     'timestamp' => new db\RawSql('NOW()')]
 * и получаем  ['id' => 1, 'name' => '\'tes\\\'ts\'', 'params' => 'null', 'text' => '\'NOW()\'', 'timestamp' => 'NOW()']
 *
 * ВНИМАНИЕ!!! в конструктор следует передавать уже экранированную сроку в том виде в котором мы хотим получить ее в sql запросе, дополнительные проверки и экранирование не осуществляется!!!
 *
 * Class RawSql
 * @package db
 */
class RawSql {
    /* @var string */
    protected $part_sql;

    /**
     * @param string $part_sql
     */
    public function __construct($part_sql) {
        $this->part_sql = $part_sql;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->part_sql;
    }

    /**
     * @return string
     */
    public function __invoke() {
        return $this->__toString();
    }
}

