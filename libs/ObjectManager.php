<?

/**
 * Class ObjectManager статический класс для управления коллекцией обьектов
 */
class ObjectManager {
    /**
     * хранилищще обьектов
     * @var array
     */
    protected static $objects = [];

    /**
     * создает если обьект не созданн, сохранает в хранилищще и возвращщает указатель на созданный обьект
     * @param string $className имя класса из которого создать обьект
     * @param array  $params     переменные передаваемые в конструктор
     * @throws Exception
     * @return object
     */
    public static function get($className, array $params = []) {
        $key = $className.($params ? md5(serialize($params)) : '');
        if (!isset(self::$objects[$key])) switch (count($params)) {
            case 0: self::$objects[$key] = new $className(); break;
            case 1: self::$objects[$key] = new $className(reset($params)); break;
            case 2: self::$objects[$key] = new $className(reset($params), next($params)); break;
            case 3: self::$objects[$key] = new $className(reset($params), next($params), next($params)); break;
            case 4: self::$objects[$key] = new $className(reset($params), next($params), next($params), next($params)); break;
            case 5: self::$objects[$key] = new $className(reset($params), next($params), next($params), next($params), next($params)); break;
            case 6: self::$objects[$key] = new $className(reset($params), next($params), next($params), next($params), next($params), next($params)); break;
            case 7: self::$objects[$key] = new $className(reset($params), next($params), next($params), next($params), next($params), next($params), next($params)); break;
            case 8: self::$objects[$key] = new $className(reset($params), next($params), next($params), next($params), next($params), next($params), next($params), next($params)); break;
            case 9: self::$objects[$key] = new $className(reset($params), next($params), next($params), next($params), next($params), next($params), next($params), next($params), next($params)); break;
            default: throw new LogicException('ты мудаг бугага :-D найди откуда вылезла эта надпись и подумай нахрена тебе столько параметров в конструкторе обьекта :-P');  break;
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
     * удаляет обьект который созданн из класса $className
     * @param string $className имя класса обьект которого надо удалить
     * @param array  $params     переменные переданные в конструктор
     * @return object
     */
    public static function exists($className, array $params = []) {
        return isset(self::$objects[$className.($params ? md5(serialize($params)) : '')]);
    }

    /**
     * удаляет обьект который созданн из класса $className
     * @param string $className имя класса обьект которого надо удалить
     * @param array  $params
     */
    public static function delete($className, array $params = []) {
        $key = $className.($params ? md5(serialize($params)) : '');
        if (isset(self::$objects[$key])) unset(self::$objects[$key]);
    }
}

/**
 * хелпер-оберетка на метод get класса ObjectManager
 * создает если обьект не созданн, сохранает в хранилищще и возвращщает указатель на созданный обьект
 *
 * @param string $className имя класса из которого создать обьект
 * @param array  $params     переменные передаваемые в конструктор
 * @return object
 */
function getObj($className, array $params = []) {
    return ObjectManager::get($className, $params);
}

/**
 * хелпер-оберетка на метод get класса ObjectManager
 * создает если обьект не созданн, сохранает в хранилищще и возвращщает указатель на созданный обьект
 *
 * @param string $className ключ, имя класса обьекта
 * @param object $object     переменные передаваемые в конструктор
 * @return object
 */
function setObj($className, $object) {
    return ObjectManager::set($className, $object);
}

/**
 * хелпер-оберетка на метод clear класса ObjectManager
 *
 * @param string $className имя класса обьект которого надо удалить
 * @param array  $params     переменные переданные в конструктор
 */
function clearObj($className, array $params = []) {
    ObjectManager::delete($className, $params);
}

