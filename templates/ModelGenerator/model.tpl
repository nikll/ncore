<?='<'?>?

namespace models;
use models\base\<?=$baseModelClass?>;

/**
 * Class <?=$modelClass."\n"?>
<?foreach ($columns as $col_name => $col):?>
 * @property <?=$col['php_type'].' '.$col_name."\n"?>
<?endforeach?>
 * @package models
 */
class <?=$modelClass?> extends <?=$baseModelClass?> {

}
