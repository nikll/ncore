<?

// настраиваем кодировку на utf8
ini_set('default_charset',	          'UTF-8');
ini_set('mbstring.language',	      'Russian');
ini_set('mbstring.http_input',	      'pass');
ini_set('mbstring.http_output',       'pass');
ini_set('mbstring.internal_encoding', 'UTF-8');
mb_internal_encoding('UTF-8');

// пути поиска файлов фреймворка
define('CORE_ROOT_PATH', 	    dirname(__FILE__).'/');
define('CORE_LIBS_PATH',	    CORE_ROOT_PATH.'libs/');
define('CORE_MODELS_PATH',	    CORE_ROOT_PATH.'models/');
define('CORE_CONTROLLERS_PATH',	CORE_ROOT_PATH.'controllers/');
define('CORE_TEMPLATES_PATH',	CORE_ROOT_PATH.'templates/');

// пути поиска файлов приложения
define('LIBS_PATH',        ROOT_PATH.'libs/');
define('MODELS_PATH',      ROOT_PATH.'models/');
define('CONTROLLERS_PATH', ROOT_PATH.'controllers/');
define('TEMPLATES_PATH',   ROOT_PATH.'templates/');

// пути для инклюдов, сначала из приложения потом из фрейворка для возможности перекрывания файлов фреймворка
set_include_path(
	get_include_path()
	.PATH_SEPARATOR.LIBS_PATH
	.PATH_SEPARATOR.CORE_LIBS_PATH
	.PATH_SEPARATOR.TEMPLATES_PATH
	.PATH_SEPARATOR.CORE_TEMPLATES_PATH
	.PATH_SEPARATOR.ROOT_PATH
	.PATH_SEPARATOR.CORE_ROOT_PATH
);

/**
 * Автоматическое подключение файла класса
 * @param string $class_name
 */
function __autoload($class_name) {
	// убиваем слеши и двоеточия для того чтобы избежать попыток подключения левых файлов, бэкслешы namespace заменяем на слешы (подкаталоги)
	$filename = str_replace(['/', '..', '\\'], ['', '', '/'], $class_name).'.php';
	if ($filename{1} != '/') require_once($filename);
}

require_once('config.php');
require_once('functions.php');
require_once('ObjectManager.php');
require_once('LinkManager.php');

?>