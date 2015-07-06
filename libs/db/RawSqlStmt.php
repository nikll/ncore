<?

namespace db;


/**
 * класс хелпер, применяется в генерации WHERE условий sql запросов
 * например нам вместо прострого равинства строк требуется поиск по маске, соответсвенно вместо простой переменной в массиве фильтра помещаем ее обернутую new db\RawSqlStmt("LIKE '%".db\Db::escape($val)."%'")
 * ВНИМАНИЕ!!! в конструктор следует передавать уже экранированную сроку в том виде в котором мы хотим получить ее в sql запросе, дополнительные проверки и экранирование не осуществляется!!!

 * Class RawSqlStmt
 *
*@package db
 */
class RawSqlStmt extends RawSql {
	/**
	 * генерирует часть условия sql запроса, например если передать массив вида [1, 3, 10, 'test_fail', '\'test\'', 'null'] на выходе получим строку IN(1, 3, 10, test_fail, 'test', null)
	 * ВНИМАНИЕ!!! экранирование не осуществляется! для экранирования, перед передачей в метод IN(), обработать массив при помощи Db::escape
	 * @param array $values
	 * @return mixed
	 */
	public static function IN(array $values) {
		$class = get_called_class();
		return new $class('IN ('.implode(', ', $values).')');
	}

	/**
	 * генерирует часть условия sql запроса, например если передать массив вида [1, 3, 10, 'test_fail', '\'test\'', 'null'] на выходе получим строку NOT IN(1, 3, 10, test_fail, 'test', null)
	 * ВНИМАНИЕ!!! экранирование не осуществляется! для экранирования, перед передачей в метод NOT_IN(), обработать массив при помощи Db::escape
	 * @param array $values
	 * @return mixed
	 */
	public static function NOT_IN(array $values) {
		$class = get_called_class();
		return new $class('NOT IN ('.implode(', ', $values).')');
	}
}

?>