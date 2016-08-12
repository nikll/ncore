<?

use exceptions\ConnectException;

/**
 * Class MemcacheTags — драйвер для Memcache с тегами и блокировками
 * описание логики http://www.smira.ru/2008/10/28/web-caching-memcached-4/ и http://www.smira.ru/2008/10/29/web-caching-memcached-5/
 */
class MemcacheTags extends Memcache {
    /* @var string - префикс для изоляции записей */
    protected $prefix = 'c';

    /* @var int - флаги для мемкеша */
    protected $flags = false; // MEMCACHE_COMPRESSED

    /**
     * Конструктор
     * @param string $host
     * @param int    $port
     * @param bool   $persistent
     * @param string $prefix
     * @throws ConnectException
     */
    public function __construct($host = 'localhost', $port = 11211, $persistent = false, $prefix = '') {
        if (!$this->addServer($host, $port, $persistent)) throw new ConnectException('Cache: Connection failed: '.$host);
        $this->prefix = $prefix;
    }

    /**
     * Устанавливает общий для всех ключей префикс
     * @param string $prefix
     */
    public function set_prefix($prefix) {
        $this->prefix = $prefix;
    }

    /**
     * Если ключ устарел и блокировка удалась то вернет false (в этом случае нам надо генерировать и сохранять данные)
     * Если ключ не найден но блокировка от другого процесса с таким же запросом уже существует то вернет null -
     *   в этом случае надо ждать когда другой процесс сгенерирует и запнет в кеш свежие данные, например проверять в цикле пока не вернет данные либо false)
     * Во всех остальных случаях вернет данные сохраненные по этому ключу (даже если они уже устарели но при этом в другом потоке запущенно но еще не завершено обновление этих данных)

     *  данные можно получить (если они в мемкеше вообще есть) если:
     *     1. данные валидны
     *     2. не требуется валидация данные (свежесть не критична)
     *
     *  вернет false если:
     *    1. данных нет вообще и блокировка не удалась (или $lock_time установлен в 0 - блокировка вообще не будет происходить)
     *     2. данные не валидны но $valid_required = true (возвращать только валидные данные)

     *  вернет null если:
     *    данные отстуствуют или требуются только валидные данные но экземляр в мемкеше уже устарел и при этом удалось поставить блокировку
     *
     * @param array|string $key
     * @param bool         $valid_required  если true то возвращает только валидные данные
     * @param int          $lock_time       время блокировки в секундах, если 0 то блокировка не производится
     * @return null|bool|mixed
     */
    public function get($key, $valid_required = true, $lock_time = 0) {
        if (is_array($key)) {
            $result = [];
            foreach ($key as $k) $result[$k] = $this->get($k, $valid_required, $lock_time);
            return $result;
        }
        $data = parent::get($this->prefix.'_'.$key);
        $is_valid = ($data !== false && (!isset($data['expire']) || $data['expire'] > time())); // Если есть данные и их срок жизни не истек

        // Если у записи есть тэги - обрабатываем им и проверяем, не изменилось ли их значение
        if ($is_valid && !empty($data['tags'])) {
            // Сравниваем значения тегов записи с глобальными значениями тегов, если хоть один тег устарел либо отсутсвует то данные считаются невалидными
            $is_valid = (!(bool)array_udiff_assoc($data['tags'], $this->get_tags(array_keys($data['tags'])), function ($a, $b) {
                return intval($a < $b);
            }));
        }

        // если данные валидны или валидность данных не требуется но данные есть то вернем их
        // иначе если есть время блокировки то пробуем заблокироваться
        // в случае успешной блокировки вернем null иначе если блокировка уже есть вернем false
        return ($is_valid || (!$valid_required && $data !== false) ? $data['data'] :
            ($lock_time && $this->lock($key, $lock_time) ? null : false)
        );
    }

    /**
     * Создает ключ $key со значением $data, метками $tags.
     * @param string   $key      ключ
     * @param mixed    $data     данные
     * @param array    $tags     метки
     * @param int|bool $expire   время жизни в секундах
     * @return bool
     */
    public function set($key, $data, array $tags = [], $expire = 0) {
        $tags[] = $this->prefix;
        $data = ['data' => $data, 'tags' => $this->get_tags($tags)];
        if ($expire) $data['expire'] = time() + $expire;
        $expire = (($expire *= 1.5) <= 2592000 ? $expire : 0); // задаем время жизни для мемкеша в 1,5 раза больше, если получается больше месяца то задаем 0 - бесконечное время
        return parent::set($this->prefix.'_'.$key, $data, $this->flags, $expire);
    }

    /**
     * Удаляет ключ $key
     * @param string|array $key ключ
     * @return bool
     */
    public function delete($key) {
        if (is_array($key)) {
            $result = true;
            foreach ($key as $k) $result &= $this->delete($k);
            return $result;
        }
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return parent::delete($this->prefix.'_'.$key);
    }

    /**
     * Удаляет тэг. А именно, увеличивает значение тега в следствии чего все записи принадлежащие данному тегу сразу устареют
     * Используется для сброса всех ключей с тэгом $tag.
     * @param string|array $tag
     * @return bool
     */
    public function delete_tag($tag) {
        if (is_array($tag)) {
            foreach ($tag as $k) $this->delete_tag($k);
            return true;
        }
        parent::set($this->prefix.'_tag_'.$tag, microtime(true));
        return true;
    }

    /**
     * Инкрементирует ключ $key
     * @param string|array $key ключ
     * @param int   $data
     * @return bool
     */
    public function increment($key, $data = 1) {
        if (is_array($key)) {
            $result = true;
            foreach ($key as $k) $result &= $this->increment($k, $data);
            return $result;
        }
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return parent::increment($this->prefix.'_'.$key, $data);
    }

    /**
     * Декрементирует ключ $key
     * @param string|array $key ключ
     * @param int   $data
     * @return bool
     */
    public function decrement($key, $data = 1) {
        if (is_array($key)) {
            $result = true;
            foreach ($key as $k) $result &= $this->decrement($k, $data);
            return $result;
        }
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return parent::decrement($this->prefix.'_'.$key, $data);
    }

    /**
     * Возвращает массив тэгов в виде ['tag_name1' => microtime, ...]. В случае, если какой либо тэг не найден, то создает его инициализируя текущее время.
     * @param array $tags
     * @return array
     */
    protected function get_tags(array $tags) {
        $keys = [];
        foreach ($tags as $tag) $keys[$tag] = $this->prefix.'_tag_'.$tag;
        $tmp = parent::get($keys);

        $new_tag_value = microtime(true);
        foreach ($keys as $tag => $key) {
            if (empty($tmp[$key])) {
                if (!parent::add($key, $new_tag_value) && $new_tag_value2 = parent::get($key)) {
                    $keys[$tag] = $new_tag_value2;
                } else {
                    $keys[$tag] = $new_tag_value;
                }
            } else {
                $keys[$tag] = $tmp[$key];
            }
        }

        return $keys;
    }

    /**
     * Блокирует добавление id со значением data на время $expire
     *
     * @param string   $key       ключ
     * @param int|bool $lock_time время блокировки в секундах
     * @return bool
     */
    protected function lock($key, $lock_time = 5) {
        return parent::add($this->prefix.'_'.$key.'_lock', 1, false, $lock_time);
    }
}