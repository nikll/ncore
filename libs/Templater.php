<?
/**
 * Class Templater
 */
class Templater {
    /* @var array  */
    protected $_vars = [];

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->_vars[$key];
    }

    /**
     * @param string|array $key
     * @param string|bool  $value
     * @return bool
     */
    public function set($key, $value = false) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if (!is_string($k))
                    trigger_error('A string keys expected in array on first level, '.gettype($k).' given!', E_USER_WARNING);
                elseif (is_resource($v))
                    trigger_error('Unexpected "resource" type in element of array with "'.$k.'" key', E_USER_WARNING);
                else    $this->_vars[$k] = $v;
            }
            return true;
        }

        if (is_string($key)) {
            $this->_vars[$key] = $value;
            return true;
        }

        trigger_error('A string|array type expected in 1-st parameter, '.gettype($key).' given', E_USER_WARNING);
        return false;
    }

    /**
     * @param string|array $key
     * @param string|bool  $value
     * @return bool
     */
    public function assign($key, $value = false) {
        return $this->set($key, $value);
    }

    /**
     * @param string $__template_file_name
     * @param array  $__vars
     * @return string
     */
    public function fetch($__template_file_name, array $__vars = []) {
        if ($__vars) $this->assign($__vars);
        return static::exec($__template_file_name, $this->_vars);
    }

    /**
     * @return bool
     */
    public function clear_all() {
        $this->_vars = [];
        return true;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function clear($name) {
        unset($this->_vars[$name]);
        return true;
    }

    /**
     * @param string $__template_file_name
     * @param array  $__vars
     * @return string
     */
    public static function exec($__template_file_name, array $__vars = []) {
        if (is_array($__vars) && $__vars) {
            if (!isset($__vars['__vars'])) {
                extract($__vars);
                unset($__vars);
            } else    extract($__vars);
        }
        ob_start();
        include($__template_file_name);
        return ob_get_clean();
    }
}
?>