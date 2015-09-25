<?

namespace controllers;

/**
 * Class App
 *
 * @package controllers
 */
class App extends Controller {
    /* @var string */
    protected $method = '';

    /* @var mixed|string */
    protected $url = '';

    /* @var mixed|string */
    protected $layout = '';

    /* @var string */
    protected $prefix = '';

    /**
     * конструктор
     * инициализирует класс приложения, определяет урл и хттп метод с которыми было обращение к скрипту
     * проверяет не является ли это обращение аякс запросом через свой диспетчер, и если является то передает управление ему с последующим выходом.
     */
    public function __construct() {
        parent::__construct();
        $this->templates_path = '';
        $this->method         = strtolower(@$_SERVER['REQUEST_METHOD']);
        $this->url            = (isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '/');

        // обработчик аякс вызовов
        /* @var array $link
         * @var string|object $class
         * @var string        $method
         * @var array|null    $params
         */
        $link = get_ajax_link();
        if ($link && extract($link)) die($this->call($class, $method, $params));
    }

    /**
     * @param array $config
     */
    public function config(array $config) {
        $allow_options = ['layout', 'prefix'];
        foreach ($allow_options as $option) if (isset($config[$option])) $this->$option = $config[$option];
    }

//////////////////////////////////////////////////
    /**
     * @return bool
     */
    public function is_options() {
        return $this->method != 'options';
    }

    /**
     * @return bool
     */
    public function is_head() {
        return $this->method != 'head';
    }

    /**
     * @return bool
     */
    public function is_get() {
        return $this->method != 'get';
    }

    /**
     * @return bool
     */
    public function is_post() {
        return $this->method != 'post';
    }

    /**
     * @return bool
     */
    public function is_put() {
        return $this->method != 'put';
    }

    /**
     * @return bool
     */
    public function is_patch() {
        return $this->method != 'patch';
    }

    /**
     * @return bool
     */
    public function is_delete() {
        return $this->method != 'delete';
    }
//////////////////////////////////////////////////

    /**
     * @param string   $pattern
     * @param callback $callback
     */
    public function head($pattern, $callback) {
        $this->route(__FUNCTION__, $pattern, $callback);
    }

    /**
     * @param string   $pattern
     * @param callback $callback
     */
    public function options($pattern, $callback) {
        $this->route(__FUNCTION__, $pattern, $callback);
    }

    /**
     * @param string   $pattern
     * @param callback $callback
     */
    public function get($pattern, $callback) {
        $this->route(__FUNCTION__, $pattern, $callback);
    }

    /**
     * @param string   $pattern
     * @param callback $callback
     */
    public function post($pattern, $callback) {
        $this->route(__FUNCTION__, $pattern, $callback);
    }

    /**
     * @param string         $pattern
     * @param array|callback $callback
     */
    public function put($pattern, $callback) {
        $this->route(__FUNCTION__, $pattern, $callback);
    }

    /**
     * @param string   $pattern
     * @param callback $callback
     */
    public function patch($pattern, $callback) {
        $this->route(__FUNCTION__, $pattern, $callback);
    }

    /**
     * @param string   $pattern
     * @param callback $callback
     */
    public function delete($pattern, $callback) {
        $this->route(__FUNCTION__, $pattern, $callback);
    }

    /**
     * обертка для CRUD интерфейса backbone.js
     * @param string        $prefix
     * @param string|object $object
     */
    public function crud($prefix, $object) {
        $app = $this;
        $this->chroot(
            $prefix,
            function () use ($app, $object) {
                $this->post('', [$object, 'create']);
                $this->get(['url' => '(:p)', 'id' => 'int'], [$object, 'read']);
                $this->put(['url' => ':p', 'id' => 'int'], [$object, 'update']);
                $this->delete(['url' => ':p', 'id' => 'int'], [$object, 'delete']);
            }
        );
    }

    /**
     * @param $prefix
     * @param $callback
     * @return bool
     */
    public function chroot($prefix, $callback) {
        $new_prefix = str_replace('//', '/', $this->prefix.$prefix);
        if (!preg_match('#^'.$new_prefix.'#', $this->url)) return false;
        $back_prefix  = $this->prefix;
        $this->prefix = $new_prefix;
        $result       = $callback();
        $this->prefix = $back_prefix;
        return $result;
    }

    /**
     * Роутер.
     *
     * @param string   $method   HTTP метод (GET POST PUT HEAD итд итп)
     * @param string   $pattern  Шаблон урла, допустимы маски: ':p', :p+, '*', '()'.
     * @param callback $callback функция обработчик
     * @return mixed
     */
    public function route($method, $pattern, $callback) {
        if ($method && $this->method != $method) return false;

        // Если есть описание переменных которые надо выпарсить из урла, с автоматической фильтрацией, задается в виде [ ..., 'key' => 'filter_type', ...]
        // где key это имя переменной в GET и filter_type тип фильтрации из функции filter
        if (is_array($pattern)) {
            $params_config = $pattern;
            $pattern       = array_shift($params_config);
        }
        // convert URL parameters (':p', :p+, '*', '()') to regular expression
        $regexp = str_replace(
            ['*',     '*+', '(',   ')',  ':p+',     ':p'],
            ['[^/]+', '.+', '(?:', ')?', '(.+?/?)', '([^/]+)'],
            str_replace('//', '/', $this->prefix.$pattern)
        );

        // extract parameter values from URL if route matches the current request
        if (!preg_match('#^'.$regexp.'/?$#', $this->url, $url_values)) return false;

        if (empty($params_config)) return $this->_exec($callback, []);

        $params = [];
        $cnt    = 0;
        foreach ($params_config as $key => $type) {
            if (isset($url_values[++$cnt])) {
                $url_values[$cnt] = urldecode($url_values[$cnt]);
                if (strpos($url_values[$cnt], '/') !== false) {
                    $params[$key] = explode('/', rtrim($url_values[$cnt], '/'));
                    foreach ($params[$key] as &$val) $val = filter($val, $type);
                } else {
                    $params[$key] = filter($url_values[$cnt], $type);
                }
            }
        }
        return $this->_exec($callback, $params);
    }

    /**
     * @param callback|array $callbacks
     * @param array          $params
     * @return mixed
     */
    protected function _exec($callbacks, $params) {
        // если единственная функция то ее результаты передаем сразу в шаблонизатор
        if ($callbacks instanceof \Closure) $this->_app_render($callbacks($params));

        $is_called = false;
        foreach ($callbacks as $c) $is_called |= ($c instanceof \Closure || count($c) == 2);
        if (!$is_called) $this->_app_render($this->_caller($callbacks, $params));

        // если много функций или экшенов от контроллеров то ее результаты передаем сразу в шаблонизатор
        $data = [];
        foreach ((array)$callbacks as $key => $callback) $data[$key] = $this->_caller($callback, $params);
        $this->_app_render($data);
    }

    /**
     * @param callback $callback
     * @param array    $params
     * @return mixed
     * @throws \Exception
     */
    protected function _caller($callback, $params) {
        // если функция-замыкание то вызываем ее
        if ($callback instanceof \Closure) return $callback($params);

        // если экшен контроллера
        if (is_array($callback)) return $this->call($callback[0], $callback[1], $params);
        if (is_scalar($callback)) return $callback;
        throw new \Exception('Ошибочное описание контроллера в роутинге!');
    }

    /**
     * рендерит главный шаблон
     * @param array $data данные для шаблонизатора
     */
    protected function _app_render($data) {
        if ($this->layout) exit($this->templater($this->layout, $data));
        exit($data);
    }
}

?>