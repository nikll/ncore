<?

namespace controllers;

use \Templater;
use \Exception;

/**
 * Class ConnectException
 * @package controllers
 */
class AccessDeniedException extends Exception {

}

/**
 * Class Controller
 * базовый контроллер
 * @package controllers
 */
abstract class Controller {

    /* @var string */
    protected $templates_path = '';

    /* @var string */
    protected $controllerClass = '';

    /* @var array дефолтные разрешения */
    protected $acl = [];

    /**
     * базовый конструктор
     * инициализирует переменные шаблонизатора и определяет права юзера
     */
    public function __construct() {
        $this->controllerClass = preg_replace('/^controllers\\\\/', '', static::class);
        $this->templates_path   = $this->controllerClass.'/';

        if (!empty($_SESSION['user'])) {
            $user      = $_SESSION['user'];
            $this->acl = array_merge($this->acl, $user->aclsByTarget($this->controllerClass));
        }
    }

    /**
     * обертка для вызова шаблонизатора, формирует путь к шаблону с учетом текущего класса
     * @param string $template_name
     * @param array  $data
     * @return string
     */
    protected function templater($template_name, array $data = []) {
        return Templater::exec($this->templates_path.$template_name.'.tpl', $data);
    }

    /**
     * обертка для вызова шаблонизатора, формирует путь к шаблону с учетом текущего класса, кроме шаблонизации документа задает все нужные заголовки для отдачи сгенерированного файла
     * @param string $template_name
     * @param array  $data
     * @param array  $params
     * @return string
     */
    protected function templaterFileDownload($template_name, array $data = [], array $params = []) {
        $headers = [
            'Content-Description'       => 'File Transfer',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => '0',
            'Cache-Control'             => 'must-revalidate',
            'Pragma'                    => 'public',
            'Content-Type'              => (!empty($params['type']) ? $params['type'] : 'application/vnd.ms-excel'),
            'Content-Disposition'       => 'attachment; filename="'.(!empty($params['name']) ? $params['name'] : $template_name).'"'
        ];
        while (ob_get_level()) ob_end_clean();
        foreach ($headers as $key => $param) header($key.': '.$param);
        return $this->templater($template_name, $data);
    }

    /**
     * вызывает обработчик callMethod класса $class
     * @param string|Controller $class
     * @param string            $method
     * @param null|array        $params
     * @return mixed
     */
    protected function call($class, $method, array $params = []) {
        if (!is_object($class)) {
            $class = 'controllers\\'.preg_replace('/^controllers\\\\/', '', $class);
            $class = new $class();
        }
        return $class->callMethod($method, $params);
    }

    /**
     * вызывает метод $method текущего обьекта, проверяет наличие метода и права доступа, ведет логи, возвращщает результат работы вызываемого метода
     * @param string     $method
     * @param null|array $params
     * @throws Exception
     * @return string
     */
    protected function callMethod($method, array $params = []) {
        if (!in_array($method, $this->acl)) {
            throw new AccessDeniedException($this->controllerClass.'->'.$method.'()<br>Список текущих прав доступа:<br><pre>'.print_r($this->acl, true).'</pre>');
        }

        return $this->$method($params);
    }

    /**
     * @param array  $data
     * @return string
     */
    public static function jsonResponse(array $data = []) {
        return output_wrapper(json_encode($data));
    }


    /**
     * парсит входящий PUT|POST|DELETE json запрос в массив
     * @return array
     */
    protected static function jsonRequestWrapper() {
        return (array)json_decode(input_wrapper(@file_get_contents('php://input')), true);
    }


    /**
     * парсит входящий PUT|POST|DELETE запрос в строку
     * @return string
     */
    protected static function requestWrapper() {
        return input_wrapper(@file_get_contents('php://input'));
    }


    protected static $request = '';

    protected static $jsonRequest = [];

    /**
     * парсит входящий PUT|POST|DELETE json запрос в массив и хранит его после первого вызова функции дабы много раз не парсить
     * @return array
     */
    public static function getJsonRequest() {
        return (static::$jsonRequest ? static::$jsonRequest : static::$jsonRequest = static::jsonRequestWrapper());
    }
}

