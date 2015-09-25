<?

/**
 * Class ModelGenerator
 */
class ModelGenerator {
    /* @var string */
    protected $_db = 'db';

    /* @var db\Db */
    protected $db = null;

    /**
     * @param string $db имя функции хелпера которая вернет инстанс db\Db
     */
    public function __construct($db = '') {
        if ($db) $this->set_db($db);
    }

    /**
     * @param string $db имя функции хелпера которая вернет инстанс db\Db
     */
    public function set_db($db = '') {
        $this->_db = $db = ($db ? $db : $this->_db);
        $this->db  = $db();
    }

    /**
     * @param string $table           имя таблицы из которой генерировать модель
     * @param string $modelNamePrefix префикс имени класса модели
     * @param string $db              имя функции хелпера которая вернет инстанс db\Db
     * @param array  $options
     */
    public function generate($table, $modelNamePrefix, $db = '', $options = []) {
        if ($db) $this->set_db($db);
        $modelNamePrefix = capitalize($modelNamePrefix);
        $modelClass      = $modelNamePrefix.capitalize($table);
        $baseModelClass  = 'base'.ucfirst($modelClass);
        $data            = [
            'model_name_prefix' => $modelNamePrefix,
            'modelClass'        => $modelClass,
            'baseModelClass'    => $baseModelClass,
            'table'             => $table,
            'columns'           => $this->get_columns($table),
            'keys'              => $this->get_keys($table),
            'references'        => $this->get_references($table),
            'relations'         => $this->get_relations($table),
            'uses'              => [],
            'default_item'      => [],
            'pk_columns'        => [],
            'ai_column'         => '',
            '_db'               => $this->_db
        ];
        if (!empty($options['cols_override'])) {
            foreach ($options['cols_override'] as $col_name => $col) {
                $data['columns'][$col_name] = array_merge($data['columns'][$col_name], $col);
            }
        }

        if ($data['references']) foreach ($data['references'] as $row) $data['uses'][$modelNamePrefix.capitalize($row['tbl'])] = true;
        if ($data['relations']) foreach ($data['relations'] as $row) $data['uses'][$modelNamePrefix.capitalize($row['tbl'])] = true;

        foreach ($data['columns'] as $col_name => $col) {
            $data['default_item'][$col_name] = (isset($col['default']) ? $col['default'] : null);
            if (!empty($col['auto_increment'])) {
                $data['ai_column'] = $col_name;
                unset($col['auto_increment']);
            }
        }

        if (empty($data['keys']['PRIMARY'])) {
            foreach ($data['keys'] as $index) {
                if ($index['unique']) {
                    foreach ($index['cols'] as $key) $data['pk_columns'][$key] = $key;
                }
            }
        } else {
            foreach ($data['keys']['PRIMARY']['cols'] as $key) $data['pk_columns'][$key] = $key;
        }

        if (!is_dir(MODELS_PATH.'base/')) mkdir(MODELS_PATH.'base/');
        // генерим базовую модель
        file_put_contents(MODELS_PATH.'base/'.$baseModelClass.'.php', Templater::exec(__CLASS__.'/baseModel.tpl', $data));

        // если файл модели не найден то генерим и его
        if (!file_exists(MODELS_PATH.$modelClass.'.php')) file_put_contents(MODELS_PATH.$modelClass.'.php', Templater::exec(__CLASS__.'/model.tpl', $data));
        //echo '<pre style="padding:2px;">'.php_code2html($php_code).shell_exec('/usr/local/bin/php -l "'.MODELS_PATH.$model_name_prefix.ucfirst($table).'.php"').'</pre>';
    }

    /**
     * Возвращщает набор колонок с их параметрами по таблице $table
     *
     * @param string $table
     * @return array
     */
    public function get_columns($table) {
        $cols = $this->db->fetch_all("describe $table", 'Field');
        foreach ($cols as $col_name => $col) {
            preg_match('/(?P<type>.*?)(?:\((?:(?P<length>[\d,]*)|(?P<allow_values>.*))\).*)?$/', $col['Type'], $tmp);
            $tmp['type']     = str_replace(' unsigned', '', $tmp['type']);
            $cols[$col_name] = [
                'type'     => $tmp['type'],
                'php_type' => \models\Model::$map_types_mysql2php[$tmp['type']]
            ];

            if (!empty($tmp['length'])) $cols[$col_name]['length'] = $tmp['length'];
            if (!empty($tmp['allow_values'])) {
                $cols[$col_name]['allow_values'] = explode("','", str_replace(["''", "\\\\"], ["'", "\\"], preg_replace('/^\'(.*)\'$/is', '\\1', $tmp['allow_values'])));
            }

            if ($tmp['type'] == 'text' && $col_name == 'additional') {
                $cols[$col_name]['type'] = 'serialize';
                $cols[$col_name]['php_type'] = 'array';
                if(empty($cols[$col_name]['default'])) $cols[$col_name]['default'] = [];
            }
            if ($cols[$col_name]['is_null'] = ($col['Null'] == 'YES')) $cols[$col_name]['default'] = null;
            if (isset($col['Default'])) $cols[$col_name]['default'] = $col['Default'];
            if ($tmp['type'] == 'set') $cols[$col_name]['default'] = [(!is_null($col['Default']) ? $col['Default'] : '')];
            if ($col['Extra'] == 'auto_increment') $cols[$col_name]['auto_increment'] = true;
            if ($col['Extra'] == 'on update CURRENT_TIMESTAMP') $cols[$col_name]['on_update_CURRENT_TIMESTAMP'] = true;
            if ($tmp['type'] == 'timestamp' && $col['Default'] == 'CURRENT_TIMESTAMP') {
                $cols[$col_name]['default'] = null;
                $cols[$col_name]['is_null'] = true;
            }
            if ($tmp['type'] == 'enum' && empty($col['Default'])) unset($cols[$col_name]['default']);
        }
        return $cols;
    }

    /**
     * @param $table
     * @return array
     */
    public function get_references($table) {
        return $this->db->fetch_all(
            "
            SELECT
              COLUMN_NAME as id,
              REFERENCED_COLUMN_NAME as col,
              REFERENCED_TABLE_NAME as tbl
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = database()
              AND TABLE_NAME = '$table'
              AND REFERENCED_TABLE_NAME is not null
        "
        );
    }

    /**
     * @param $table
     * @return array
     */
    public function get_relations($table) {
        return $this->db->fetch_all(
            "
            SELECT
              REFERENCED_COLUMN_NAME as id,
              COLUMN_NAME as col,
              TABLE_NAME as tbl
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = database()
              AND REFERENCED_TABLE_NAME = '$table'
        "
        );
    }

    /**
     * Возвращщает набор индексов с их параметрами по таблице $table
     *
     * @param string $table
     * @return array
     */
    public function get_keys($table) {
        $ret = [];
        foreach ($this->db->query("show index from $table") as $row) {
            $ret[$row['Key_name']]['unique']                     = !$row['Non_unique'];
            $ret[$row['Key_name']]['cols'][$row['Seq_in_index']] = $row['Column_name'];
        }
        return $ret;
    }

    /**
     * Возвращщает список всех таблиц в текущей бд
     *
     * @return array
     */
    public function get_tables() {
        return $this->db->fetch_all("show tables");
    }
}

?>