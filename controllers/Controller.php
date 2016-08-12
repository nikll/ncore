<?

namespace controllers;

use exceptions\AccessDeniedException;
use \Templater;


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
            $user = $_SESSION['user'];
            if (method_exists($user, 'aclsByTarget')) {
                $this->acl = array_merge($this->acl, $user->aclsByTarget($this->controllerClass));
            }
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
     * @param array             $params
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
     * @param array      $params
     * @throws AccessDeniedException
     * @return string
     */
    protected function callMethod($method, array $params = []) {
        if (!in_array($method, $this->acl)) {
            throw new AccessDeniedException($this->controllerClass.'->'.$method.'()>Список текущих прав доступа: '.print_r($this->acl, true));
        }

        return $this->$method($params);
    }
}

