<?

namespace controllers;
use Templater;

/**
 * Class ConnectException
 * @package controllers
 */
class AccessDeniedException extends \Exception {

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
	protected $controller_class = '';

	/* @var array дефолтные разрешения */
	protected $acl = [];

	/**
	 * базовый конструктор
	 * инициализирует переменные шаблонизатора и определяет права юзера
	 */
	public function __construct() {
		$this->controller_class = preg_replace('/^controllers\\\\/', '', static::class);
		$this->templates_path = $this->controller_class.'/';

		if (!empty($_SESSION['user'])) {
			$user = $_SESSION['user'];
			$this->acl = array_merge($this->acl, $user->aclsByTarget($this->controller_class));
		}
	}

	/**
	 * обертка для вызова шаблонизатора, формирует путь к шаблону с учетом текущего класса
	 * @param string $template_name
	 * @param array $data
	 * @return string
	 */
	protected function templater($template_name, array $data = []) {
		return Templater::exec($this->templates_path.$template_name.'.tpl', $data);
	}

	/**
	 * обертка для вызова шаблонизатора, формирует путь к шаблону с учетом текущего класса, кроме шаблонизации документа задает все нужные заголовки для отдачи сгенерированного файла
	 * @param string $template_name
	 * @param array $data
	 * @param array $params
	 * @return string
	 */
	protected function templater_file_download($template_name, array $data = [], array $params = []) {
		$headers = [
			'Content-Description'	    => 'File Transfer',
			'Content-Transfer-Encoding' => 'binary',
			'Expires'		    => '0',
			'Cache-Control'		    => 'must-revalidate',
			'Pragma'		    => 'public',
			'Content-Type'		    => (!empty($params['type']) ? $params['type'] : 'application/vnd.ms-excel'),
			'Content-Disposition'	    => 'attachment; filename="'.(!empty($params['name']) ? $params['name'] : $template_name).'"'
		];
		while (ob_get_level()) ob_end_clean();
		foreach ($headers as $key => $param) header($key.': '.$param);
		return $this->templater($template_name, $data);
	}

	/**
	 * вызывает обработчик call_method класса $class
	 * @param string|Controller $class
	 * @param string $method
	 * @param null|array $params
	 * @return mixed
	 */
	protected function call($class, $method, array $params = []) {
		if (!is_object($class)) $class = get_obj('controllers\\'.preg_replace('/^controllers\\\\/', '', $class));
                return $class->call_method($method, $params);
        }

	/**
	 * вызывает метод $method текущего обьекта, проверяет наличие метода и права доступа, ведет логи, возвращщает результат работы вызываемого метода
	 * @param string     $method
	 * @param null|array $params
	 * @throws \Exception
	 * @return string
	 */
    protected function call_method($method, array $params = []) {
        if (!in_array($method, $this->acl)) {
            throw new AccessDeniedException($this->controller_class.'->'.$method.'()<br>Список текущих прав доступа:<br><pre>'.print_r($this->acl, true).'</pre>');
        }
        if (!method_exists($this, $method)) {
            throw new \Exception('Метод "'.$method.'" модуля "'.static::class.'" не найден. Скорее всего, этот метод находится на стадии разработки, попробуйте открыть этот метод позже. Просим прощение за доставленные неудобства.');
        }

        return $this->$method($params);
    }

	/**
	 * парсит входящий PUT|POST|DELETE json запрос в массив
	 * @return array
	 */
	public static function get_json_request() {
		return (array)json_decode(@file_get_contents('php://input'));
	}
}

?>