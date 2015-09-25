<?='<'?>?

namespace models\base;
use models\Model;
<?if ($uses) foreach ($uses as $use_model => $tmp):?>
use models\<?=$use_model?>;
<?endforeach?>

/**
 * Class <?=$baseModelClass."\n"?>
<?foreach ($columns as $col_name => $col):?>
 * @property <?=$col['php_type'].' '.$col_name."\n"?>
<?endforeach?>
 * @package models
 */
class <?=$baseModelClass?> extends Model {
    /* @var string $_table таблица модели */
    protected static $_table = '<?=$table?>';

    /* @var array $_columns набор колонок */
    protected static $_columns = <?=array_export($columns, 1)?>;

    /* @var array $_item дефолтный набор данных */
    protected $_item = <?=array_export($default_item, 1)?>;

    /* @var array $_pk_columns содежрит названия колонок по фильтру первичный индекс либо все уникальные индексы либо [] если таковых нет */
    protected static $_pk_columns = <?=array_export($pk_columns, 1)?>;

    /* @var string $_ai_column колонка с автоинкриментом */
    protected static $_ai_column = '<?=$ai_column?>';

    /* @var string инстанс класса бд (наследованного от mysqli) */
    public static $_db = '<?=$_db?>';

<?if ($keys):?>
    /* @var array $_keys ключи таблицы включая внешние связи */
    protected static $_keys = <?=array_export($keys, 1)?>;
<?endif?>

<?if ($references):?>
    /* @var array $_references внешние ключи на другие таблицы (поля которые привязанны к данным из других таблиц) */
    protected static $_references = <?=array_export($references, 1)?>;
<?endif?>

<?if ($relations):?>
    /* @var array $_relations внешние связи на текущую таблицу */
    protected static $_relations = <?=array_export($relations, 1)?>;
<?endif?>

<?if (!empty($keys)):?>
<?foreach ($keys as $index_name => $index) if ($index['unique']):?>

    /**
<?foreach ($index['cols'] as $col_name): $col = $columns[$col_name]?>
     * @param <?=$col['php_type']?> $<?=$col_name?> -> <?=$col['type'].(!empty($col['length']) ? '('.$col['length'].')' : (!empty($col['allow_values']) ? '('.array_export_inline($col['allow_values']).')' : ''))."\n"?>
<?endforeach?>
     * @return \models\<?=$modelClass."\n"?>
     */
    public static function findBy<?=capitalize(str_replace('PRIMARY', 'pk', $index_name))?>($<?=implode(', $', $index['cols'])?>) {
        return static::find(compact('<?=implode("', '", $index['cols'])?>'));
    }
<?endif?>
<?foreach ($keys as $index_name => $index) if (!$index['unique']):?>

    /**
<?foreach ($index['cols'] as $col_name): $col = $columns[$col_name]?>
     * @param <?=$col['php_type']?> $<?=$col_name?> -> <?=$col['type'].(!empty($col['length']) ? '('.$col['length'].')' : (!empty($col['allow_values']) ? '('.array_export_inline($col['allow_values']).')' : ''))."\n"?>
<?endforeach?>
     * @return \Generator
     */
    public static function findAllBy<?=capitalize($index_name)?>($<?=implode(', $', $index['cols'])?>) {
        return static::findAll(compact('<?=implode("', '", $index['cols'])?>'));
    }
<?endif?>
<?foreach ($keys as $index_name => $index):?>

    /**
<?foreach ($index['cols'] as $col_name): $col = $columns[$col_name]?>
     * @param <?=$col['php_type']?> $<?=$col_name?> -> <?=$col['type'].(!empty($col['length']) ? '('.$col['length'].')' : (!empty($col['allow_values']) ? '('.array_export_inline($col['allow_values']).')' : ''))."\n"?>
<?endforeach?>
     * @return bool
     */
    public static function deleteBy<?=capitalize(str_replace('PRIMARY', 'pk', $index_name))?>($<?=implode(', $', $index['cols'])?>) {
        return static::deleteAll(compact('<?=implode("', '", $index['cols'])?>'));
    }
<?endforeach?>
<?endif?>
<?

foreach ($columns as $col) if (in_array($col['type'], ['set', 'serialize', 'json'])) {
    $is_required_packing = true;
    break;
}
?>
<?if (!empty($is_required_packing)):?>

    /**
     * @param array $data массив с данными для подготовки к сохранению
     * @return array
     */
    protected function pack(array $data) {
        $data = parent::pack($data);
<?foreach ($columns as $col_name => $col):?>
<?if ($col['type'] == 'set'):?>
        if (is_array($data['<?=$col_name?>'])) $data['<?=$col_name?>'] = implode(',', $data['<?=$col_name?>']);
<?elseif ($col['type'] == 'serialize'):?>
        if ($data['<?=$col_name?>']) $data['<?=$col_name?>'] = serialize($data['<?=$col_name?>']);
<?elseif ($col['type'] == 'json'):?>
        if ($data['<?=$col_name?>']) $data['<?=$col_name?>'] = json_encode($data['<?=$col_name?>']);
<?endif?>
<?endforeach?>
        return $data;
    }

    /**
     * @param array $data массив с данными для подготовки к инициализации
     * @return array
     */
    protected function unpack(array $data) {
        $data = parent::unpack($data);
<?foreach ($columns as $col_name => $col):?>
<?if ($col['type'] == 'set'):?>
        $data['<?=$col_name?>'] = ($data['<?=$col_name?>'] ? explode(',', $data['<?=$col_name?>']) : []);
<?elseif ($col['type'] == 'serialize'):?>
        $data['<?=$col_name?>'] = ($data['<?=$col_name?>'] ? unserialize($data['<?=$col_name?>']) : []);
<?elseif ($col['type'] == 'json'):?>
        $data['<?=$col_name?>'] = ($data['<?=$col_name?>'] ? json_decode($data['<?=$col_name?>'], true) : []);
<?endif?>
<?endforeach?>
        return $data;
    }
<?endif?>
<?if ($references) foreach ($references as $row):?>

    /**
     * @return <?=$model_name_prefix.capitalize($row['tbl'])?> связанный обьект
     */
    public function refBy<?=capitalize($row['id'])?>() {
        return <?=$model_name_prefix.capitalize($row['tbl'])?>::find(['<?=$row['col']?>' => $this-><?=$row['id']?>]);
    }
<?endforeach?>
<?if ($relations) foreach ($relations as $row):?>

    /**
     * @return \Generator - итератор связанных обьектов класса <?=$model_name_prefix.capitalize($row['tbl'])."\n"?>
     */
    public function rel<?=$model_name_prefix.capitalize($row['tbl'])?>By<?=capitalize($row['id'])?>() {
        return <?=$model_name_prefix.capitalize($row['tbl'])?>::findAll(['<?=$row['col']?>' => $this-><?=$row['id']?>]);
    }
<?endforeach?>
}
<?='?'?>>