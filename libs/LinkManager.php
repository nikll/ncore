<?

/* @const string ACTION_NAME имя GET параметра с закодированной ссылкой */
if (!defined('ACTION_NAME')) define('ACTION_NAME', 'aid');

/**
 * Класс управления сылками. Используется для прямого вызова методов по http.
 * Создает ссылки в виде: aid=12c55daba3b17fdcbf3f1688a66d8e18, на основе класса, метода, параметров и соли.
 * Параметры сохраняются в сессии, которые потом считываются по требованию, если сессия не инициализированна то пихает в общий кеш сразу для всех анонимов
 * По aid, переданный как GET параметр, метод get возвращает имя класса метода и параметры
 *
 * Class LinkManager
 */

class LinkManager {
    /**
     * Создает и добавляет в сессию ссылку для этого класса с переданными параметрами, и возвращает URL ссылки
     *
     * @param string $class
     * @param string $method
     * @param array  $params
     * @return string
     */
    public static function add($class, $method, array $params = []) {
        if (!isset($_SESSION['_links'])) $_SESSION['_links'] = [];
        if (is_object($class)) $class = get_class($class);
        $link = '';
        if ($params) {
            if (!is_array($params)) $params = explode('/', $params);
            foreach ($params as $key => $val) $link .= (is_int($key) ? 'param_' : '').$key.'='.$val.';';
        }
        $link = md5($class.$method.$link);
        $_SESSION['_links'][$link] = compact('class', 'method', 'params');
        if (!session_id()) cache()->set('lm_'.$link, $_SESSION['_links'][$link], ['lm'], 86400);

        if (isset($_GET['XDEBUG_TRACE']))   $link .= '&XDEBUG_TRACE='.$_GET['XDEBUG_TRACE'].$class.$method;
        if (isset($_GET['XDEBUG_PROFILE'])) $link .= '&XDEBUG_PROFILE='.$_GET['XDEBUG_PROFILE'].$class.$method;
        return '/api/?'.ACTION_NAME.'='.$link;
    }

    /**
     * выдает по aid имя класса, кому принадлежит эта ссылка, и переданные параметры
     *
     * @param string $link
     * @return mixed|null
     */
    public static function get($link) {
        return (isset($_SESSION['_links'][$link]) ? $_SESSION['_links'][$link] : cache()->get('lm_'.$link));
    }

    /**
     * удаляет ссылку
     * @param string $link
     * @return bool
     */
    public static function delete_link($link) {
        cache()->delete('lm_'.$link);
        if (!isset($_SESSION['_links'][$link])) return false;
        unset($_SESSION['_links'][$link]);
        return true;
    }

    /**
     * возвращает массив из всех активных ссылок
     * @return array
     */
    public static function get_all() {
        return (!empty($_SESSION['_links']) ? $_SESSION['_links'] : []);
    }

    /**
     * Удаляет все ссылки
     * @return bool
     */
    public static function clear() {
        cache()->delete_tag('lm');
        $_SESSION['_links'] = [];
        return true;
    }
}

/**
 * Создает и добавляет в сессию ссылку для этого класса с переданными параметрами, и возвращает URL ссылки
 *
 * @param string $class
 * @param string $method
 * @param array  $params
 * @return string
 */
function create_link($class, $method, array $params = []) {
    global $locale;
    return (!empty($locale) ? '/'.$locale : '').LinkManager::add($class, $method, $params);
}

/**
 * выдает по  имя класса, кому принадлежит эта ссылка, и переданные параметры
 *
 * @param string $link
 * @return bool
 */
function get_link($link) {
    return LinkManager::get($link);
}

/**
 * выдает по aid имя класса, кому принадлежит эта ссылка, и переданные параметры
 *
 * @return bool|mixed|null
 */
function get_ajax_link() {
    if (!isset($_GET[ACTION_NAME])) return false;
    return LinkManager::get($_GET[ACTION_NAME]);
}

