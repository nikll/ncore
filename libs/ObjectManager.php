<?

/**
 * Class ObjectManager статический класс для управления коллекцией обьектов
 */
class ObjectManager {
    /**
     * хранилищще обьектов
     * @var array
     */
    public static $objects = [];

    /**
     * создает если обьект не созданн, сохранает в хранилищще и возвращщает указатель на созданный обьект
     * @param string $class_name имя класса из которого создать обьект
     * @param array  $params     переменные передаваемые в конструктор
     * @throws Exception
     * @return object
     */
    public static function get($class_name, array $params = []) {
        $key = $class_name.($params ? md5(serialize($params)) : '');
        if (!isset(self::$objects[$key])) switch (count($params)) {
            case 0: self::$objects[$key] = new $class_name(); break;
            case 1: self::$objects[$key] = new $class_name(reset($params)); break;
            case 2: self::$objects[$key] = new $class_name(reset($params), next($params)); break;
            case 3: self::$objects[$key] = new $class_name(reset($params), next($params), next($params)); break;
            case 4: self::$objects[$key] = new $class_name(reset($params), next($params), next($params), next($params)); break;
            case 5: self::$objects[$key] = new $class_name(reset($params), next($params), next($params), next($params), next($params)); break;
            case 6: self::$objects[$key] = new $class_name(reset($params), next($params), next($params), next($params), next($params), next($params)); break;
            case 7: self::$objects[$key] = new $class_name(reset($params), next($params), next($params), next($params), next($params), next($params), next($params)); break;
            case 8: self::$objects[$key] = new $class_name(reset($params), next($params), next($params), next($params), next($params), next($params), next($params), next($params)); break;
            default: throw new Exception('ты мудаг бугага :-D найди откуда вылезла эта надпись и подумай нахрена тебе столько параметров в конструкторе обьекта :-P');  break;
        }
        return self::$objects[$key];
    }

    /**
     * сохранает в хранилищще и возвращщает указатель на обьект
     * @param string $key
     * @param object $object
     * @return object
     */
    public static function set($key, $object) {
        return self::$objects[$key] = $object;
    }

    /**
     * удаляет обьект который созданн из класса $class_name
     * @param string $class_name имя класса обьект которого надо удалить
     * @param array  $params     переменные переданные в конструктор
     * @return object
     */
    public static function exists($class_name, array $params = []) {
        return isset(self::$objects[$class_name.($params ? md5(serialize($params)) : '')]);
    }

    /**
     * удаляет обьект который созданн из класса $class_name
     * @param string $class_name имя класса обьект которого надо удалить
     * @param array  $params
     */
    public static function delete($class_name, array $params = []) {
        $key = $class_name.($params ? md5(serialize($params)) : '');
        if (isset(self::$objects[$key])) unset(self::$objects[$key]);
    }
}

/**
 * хелпер-оберетка на метод get класса ObjectManager
 * создает если обьект не созданн, сохранает в хранилищще и возвращщает указатель на созданный обьект
 *
 * @param string $class_name имя класса из которого создать обьект
 * @param array  $params     переменные передаваемые в конструктор
 * @return object
 */
function get_obj($class_name, array $params = []) {
    return ObjectManager::get($class_name, $params);
}

/**
 * хелпер-оберетка на метод clear класса ObjectManager
 *
 * @param string $class_name имя класса обьект которого надо удалить
 * @param array  $params     переменные переданные в конструктор
 */
function clear_obj($class_name, array $params = []) {
    ObjectManager::delete($class_name, $params);
}

