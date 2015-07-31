<?

/**
 * Class Validator
 */
class Validator {
	/* @var string */
	public static $int_preg = '/^\s*[+-]?\d+\s*$/';

	/* @var string */
	public static $float_preg = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';

	/* @var string */
	public static $date_preg = '/^(
		([0-9][0-9][0-9][0-9]-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-2]))
		|
		((0[1-9]|[1-2][0-9]|3[0-2])[.-](0[1-9]|1[0-2])[.-][0-9][0-9]([0-9][0-9])?)
	)$/';

	/* @var string */
	public static $time_preg = '/^([0-1][0-9]|2[0-3]):([0-5][0-9]|60)(:([0-5][0-9]|60))?$/';

	/* @var string */
	public static $datetime_preg = '/^(([0-9][0-9][0-9][0-9]-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-2]) ([0-1][0-9]|2[0-3]):([0-5][0-9]|60)(:([0-5][0-9]|60))?)|((0[1-9]|[1-2][0-9]|3[0-2])[.-](0[1-9]|1[0-2])[.-][0-9][0-9]([0-9][0-9])? ([0-1][0-9]|2[0-3]):([0-5][0-9]|60)(:([0-5][0-9]|60))?))$/';

	/* @var string */
//	public static $email_preg	= '|([a-z0-9_\.\-]{1,20})@([a-z0-9\.\-]{1,20})\.([a-z]{2,4})|is';
	public static $email_preg = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';

	/* @var string */
	public static $fill_email_preg = '/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';

	/**
	 * @param string $var
	 * @param int    $length
	 * @return bool
	 */
	public static function string($var, $length = 0) {
		return (is_scalar($var) && (!$length || mb_strlen(strval($var)) <= $length));
	}

	/**
	 * @param string $var
	 * @param array  $allow_values
	 * @return bool
	 */
	public static function enum($var, array $allow_values) {
		return (is_scalar($var) && (array_search($var, $allow_values) !== false || $var == ''));
	}

	/**
	 * @param array $var
	 * @param array $allow_values
	 * @return bool
	 */
	public static function set(array $var, array $allow_values) {
		foreach ($var as $val) if (!Validator::enum($val, $allow_values)) return false;
		return true;
	}

	/**
	 * @param string $preg
	 * @param string $var
	 * @return bool
	 */
	public static function preg($preg, $var) {
		$preg .= '_preg';
		if (!isset(static::$$preg)) return false;
		return !!preg_match(static::$$preg, $var);
	}

	/**
	 * @param string $type
	 * @param array  $args
	 * @return bool
	 */
	public static function __callStatic($type, array $args) {
		return static::preg($type, $args[0]);
	}
}

?>