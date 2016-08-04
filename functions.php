<?

/**
 * простейший таймер, $old_time это предыдущий результат вывода, если не ноль то функция вернет разницу во времени, пример использования:
 * $start = get_m();
 * sleep(1);
 * echo get_m($end); // 1.0
 *
 * @param float $old_time
 * @return float
 */
function get_m($old_time = 0.0) {
    return microtime(true) - $old_time;
}

/**
 * генератор паролей
 *
 * @param int    $length длинна
 * @param string $chars  набор символов для генерации
 * @return string
 */
function gen_pass($length = 6, $chars = '23456789qwertyupasdfghjkzxcvbnmWERTYUPASDFGHJKLZXCVBNM') {
    return mb_substr(str_shuffle($chars), 0, $length);
}

/**
 * вырезает всю верстку скрипты и комментари оставляя один текст
 *
 * @param string $html
 * @return string
 */
function html2txt($html) {
    return trim(preg_replace(
        [
            '@<script[^>]*?>.*?</script>@si', // Strip out javascript
            '@<[/!]*?[^<>]*?>@si', // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@' // Strip multi-line comments including CDATA
        ],
        '',
        $html
    ));
}

/**
 * проверка на мейл адрес
 *
 * @param string $mail
 * @return int
 */
function is_mail($mail) {
    return preg_match('|([a-z0-9_\.\-]{1,20})@([a-z0-9\.\-]{1,20})\.([a-z]{2,4})|is', $mail);
}

/**
 * Обертка над var_dump(), на вход получает любое количество параметров и передает их все в var_dump().
 * вывод результатов работы var_dump() перехватывается из выходного потока и возвращщается как результат работы функции _var_dump()
 *
 * @return string
 */
function _var_dump() {
    ob_start();
    call_user_func_array('var_dump', func_get_args());
    return ob_get_clean();
}

/**
 * обертка над var_export
 * производит корректную табуляцию и удаляет лишние запятые
 *
 * @param array $array
 * @param int   $pre_tabs
 * @return mixed
 */
function array_export(array $array, $pre_tabs = 1) {
    return preg_replace(
        [
            '/([\r\n\s\t]+)\)(,|$)/is',
            '/\ \ /is',
            '/(\r?\n)/is',
            '/[\r\n\s\t]*array \(/is',
            '/,([\r\n\s\t]+\])/is',
            '/(\t|    )\d+\ =\>\ /is',
            '/\ =\>\ NULL/is',
            '/^[\t\s]+/is'
        ],
        [
            '\\1]\\2',
            "    ",
            "\\1".str_repeat("    ", $pre_tabs),
            ' [',
            "\\1",
            "    ",
            ' => null',
            ''
        ],
        var_export($array, true)
    );
}

/**
 * обертка над var_export
 * убирает все лишние переносы строк
 *
 * @param array $array
 * @return mixed
 */
function array_export_inline(array $array) {
    return preg_replace(['/[\r\n]+[\s\t]+/', '/^\[\s+/is', '/\s+\]$/is', '/^array\(\ /is', '/\ \)$/is'], [' ', '[', ']', ''], array_export($array));
}

/**
 * рекурсивное удаление директории вкючая все вложенные файлы и директории
 *
 * @param string $dir
 * @return bool
 */
function rmdir_recursive($dir) {
    if (!file_exists($dir)) return false;
    foreach (scandir($dir) as $elem) {
        if ($elem != '.' && $elem != '..') {
            if (is_dir($dir.'/'.$elem)) {
                rmdir_recursive($dir.'/'.$elem);
            } else {
                unlink($dir.'/'.$elem);
            }
        }
    }

    return rmdir($dir);
}

/**
 * функция транслита из русских символов в английские
 *
 * @param string $str
 * @return string
 */
function translit($str) {
    return str_replace(
        ['а', 'б', 'в', 'г', 'д', 'е', 'ё', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ъ', 'ы', 'э', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ъ', 'Ы', 'Э',  'ж',  'ц',  'ч',  'ш',    'щ', 'ь',  'ю',  'я',  'Ж',  'Ц',  'Ч',  'Ш',    'Щ', 'Ь',  'Ю',  'Я'],
        ['a', 'b', 'v', 'g', 'd', 'e', 'e', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', "'", 'i', 'e', 'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', "'", 'I', 'E', 'zh', 'ts', 'ch', 'sh', 'shch',  '', 'yu', 'ya', 'ZH', 'TS', 'CH', 'SH', 'SHCH',  '', 'YU', 'YA'],
        $str
    );
}

/**
 * преобразует строку в урл согласно rfc
 *
 * @param string $str
 * @return string
 */
function to_url($str) {
    $str = mb_strtolower(
        str_replace(
            [';', ':', '.', ',', '<', '>', '?', '!', '@', '#', '$', '%', '&', '*', '(', ')', '+', '=', '~', '`', '"', "'", '[', ']', '{', '}', '\\', '/', '|', '№', '«', '»'],
            '',
            str_replace(' ', '_', translit($str))
        )
    );
    return preg_replace('/\%[A-Fa-f0-9][A-Fa-f0-9]/', '', rawurlencode($str));
}

/**
 * Формирование ключевых слов из текста
 *
 * @param string $text       исходный текст
 * @param int    $count      число слов желаемых в результате
 * @param int    $min_length минимальная длина слова считающего ключевым
 * @param array  $ignore     массив слов для игнорирования
 * @param bool   $as_array   возвращать сгенерированный слова результаты в виде массива, иначе черезе запятую в формате html meta keywords
 * @return array|string результат возвращается в виде массива ил исразу сформированной строки, зависит от параметра $as_array родительского класса
 */
function meta_generator($text, $count = 25, $min_length = 4, $ignore = [], $as_array = false) {
    $stop_words  = [' авле ', ' без ', ' больше ', ' был ', ' была ', ' были ', ' было ', ' быть ', ' вам ', ' вас ', ' вверх ', ' видно ', ' вот ', ' все ', ' всегда ', ' всех ', ' где ', ' говорила ', ' говорим ', ' говорит ', ' даже ', ' два ', ' для ', ' его ', ' ему ', ' если ', ' есть ', ' еще ', ' затем ', ' здесь ', ' знала ', ' знаю ', ' иду ', ' или ', ' каждый ', ' кажется ', ' казалось ', ' как ', ' какие ', ' когда ', ' которое ', ' которые ', ' кто ', ' меня ', ' мне ', ' мог ', ' могла ', ' могу ', ' мое ', ' моей ', ' может ', ' можно ', ' мои ', ' мой ', ' мол ', ' моя ', ' надо ', ' нас ', ' начал ', ' начала ', ' него ', ' нее ', ' ней ', ' немного ', ' немножко ', ' нему ', ' несколько ', ' нет ', ' никогда ', ' них ', ' ничего ', ' однако ', ' она ', ' они ', ' оно ', ' опять ', ' очень ', ' под ', ' пока ', ' после ', ' потом ', ' почти ', ' при ', ' про ', ' раз ', ' своей ', ' свой ', ' свою ', ' себе ', ' себя ', ' сейчас ', ' сказал ', ' сказала ', ' слегка ', ' слишком ', ' словно ', ' снова ', ' стал ', ' стала ', ' стали ', ' так ', ' там ', ' твои ', ' твоя ', ' тебе ', ' тебя ', ' теперь ', ' тогда ', ' того ', ' тоже ', ' только ', ' три ', ' тут ', ' уже ', ' хотя ', ' чем ', ' через ', ' что ', ' чтобы ', ' чуть ', ' эта ', ' эти ', ' этих ', ' это ', ' этого ', ' этой ', ' этом ', ' эту '];
    $clean_words = ['mosimage', 'nbsp', 'rdquo', 'laquo', 'raquo', 'quota', 'quot', 'ndash', 'mdash', '«', '»', "\t", '\n', '\r', "\n", "\r", '\\', "'", ',', '.', '/', '¬', '#', ';', ':', '@', '~', '[', ']', '{', '}', '=', '-', '+', ')', '(', '*', '&', '^', '%', '$', '<', '>', '?', '!', '"'];;

    $text = mb_strtolower(html2txt(strip_tags($text))); // чистим от тегов и переводим в нижний регистр
    $text = str_replace($clean_words, ' ', $text); // чистим от спецсимволов
    $text = str_replace((!$ignore ? $stop_words : array_merge($stop_words, $ignore)), ' ', $text); // чистим от языковых стоп-слов
    $text = explode(' ', $text); // делим текст на массив из слов

    $ret = [];
    foreach ($text as $sl) {
        if (mb_strlen($sl) >= $min_length) { // собираем в массив тока слова не меньше указанной длины
            if (!isset($ret[$sl])) $ret[$sl] = 0;
            $ret[$sl]++;
        }
    }

    arsort($ret); // сортируем массив, чем чаще встречается слово - тем выше его ставим
    $ret = array_slice(array_keys($ret), 0, $count); // берём первые значения массива
    return ($as_array ? $ret : implode(', ', $ret)); // собираем итог
}

/**
 * упаковывает кучку php файлов в один с обрезанием комментов отступов и прочей лишней фигни
 *
 * @param array  $files
 * @param string $output_file_name
 */
function php_packer(array $files, $output_file_name) {
    $new_code = [];

    foreach ($files as $file) {
        $tokens = token_get_all(file_get_contents($file));
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $new_code[] = $token;
            } elseif (!in_array(token_name($token[0]), ['T_DOC_COMMENT', 'T_COMMENT', 'T_WHITESPACE', 'T_OPEN_TAG', 'T_CLOSE_TAG'])) $new_code[] = ' '.$token[1];
        }
        $new_code[] = "\n";
    }

    file_put_contents($output_file_name, '<? '.str_replace('; ', ';', implode('', $new_code)).' ?>');
}

/**
 * отрендерит в html хтмл php код
 *
 * @param string $code
 * @return string
 */
function php_code2html($code) {
    $code = str_replace("\r\n", "\n", $code);
    $code = str_replace("\r", "\n", $code);
    $code = str_replace("\n", "\r\n", $code);
    $code = highlight_string($code, true);
    $code = str_replace(['<code>', '</code>', '<br />'], '', $code);
    $code = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;', "    ", $code);
    $code = str_replace('&nbsp;', " ", $code);
    return $code;
}

/**
 * Меняет формат строки с aaa_bbb_vvv на AaaBbbVvv
 *
 * @param string $str
 * @return string
 */
function capitalize($str) {
    return str_replace([' ', '_'], '', implode(array_map('ucfirst', preg_split('[\s+_]', $str))));
    //return str_replace(' ', '', mb_convert_case(str_replace('_', ' ', $str), MB_CASE_TITLE));
}

/**
 * редиректит с учетом аякса
 *
 * @param $url
 */
function redirect($url) {
    if (isset($_GET[ACTION_NAME])) die('{result: 1, callback: function() { window.location.replace(\''.$url.'\'); return; }}');
    header('Location: '.$url);
    exit;
}

/**
 * эскейпит хтмл (фильтр от XSS)
 *
 * @param $str
 * @return string
 */
function trunc($str) {
    return htmlspecialchars($str, ENT_QUOTES);
}

/**
 * фильтр
 *
 * @param string|int|float $val  переменная для фильтрации
 * @param string           $type тип фильтрации
 * @return bool|float|int|mixed|string
 */
function filter($val, $type = '') {
    switch ($type) {
        case 'int':
            return intval($val);

        case 'float':
            return floatval($val);

        case 'bool':
            return !!$val;

        case 'date':
            if ($val = strtotime($val)) return date('Y-m-d', $val);
            return false;

        case 'time':
            if ($val = strtotime($val)) return date('H:i:s', $val);
            return false;

        case 'timestamp':
        case 'datetime':
            if ($val = strtotime($val)) return date('Y-m-d H:i:s', $val);
            return false;

        case 'email':
        case 'mail':
            return preg_filter(
                '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+ \\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/',
                "\\0",
                $val
            );

        case 'nohtml':
            return html2txt($val);

        case 'url_name':
            return to_url($val);

        case 'html':
        case 'none':
        case 'unescaped':
            return $val;

        case 'str':
        case 'string':
        default:
            return trunc($val);
    }
}

/**
 * запуск сессии
 *
 * @return bool
 */
function startSession() {
    if (session_id()) return false;

    session_set_cookie_params(2592000, '/'); // куку столбим на месяц
    return session_start();
}

/**
 * уничтожение сессии
 */
function destroySession() {
    if (!session_id()) return false;

    // Если есть активная сессия, удаляем куки сессии, и уничтожаем сессию
    $_SESSION = [];
    setcookie(session_name(), session_id(), time() - 3600 * 24 * 7);
    session_unset();
    session_destroy();

    return true;
}

/**
 * возвращает полный урл запроса
 *
 * @return string
 */
function get_full_url() {
    $https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;
    $port  = $_SERVER['SERVER_PORT'];
    return ($https ? 'https://' : 'http://')
           .(!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '')
           .(isset($_SERVER['HTTP_HOST'])    ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'].($https && $port == 443 || $port == 80 ? '' : ':'.$port))
           .mb_substr($_SERVER['SCRIPT_NAME'], 0, mb_strrpos($_SERVER['SCRIPT_NAME'], '/'));
}

/**
 * исправляет переполнение int32 переведя в число в float
 * работает с размерами до 2^32-1 bytes (4 GiB - 1):
 *
 * @param int $size
 * @return float
 */
function fix_integer_overflow($size) {
    return $size + ($size < 0 ? 2.0 * (PHP_INT_MAX + 1) : 0);
}

function from_human_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $this->fix_integer_overflow($val);
}

/**
 * возвращает размер фала
 *
 * @param string $file_path        путь к файлу
 * @param bool   $clear_stat_cache очищать ли файловый кеш перед определением размера файла
 * @return float
 */
function get_file_size($file_path, $clear_stat_cache = false) {
    if ($clear_stat_cache) clearstatcache(true, $file_path);

    return fix_integer_overflow(filesize($file_path));
}

