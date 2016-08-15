<?

namespace exceptions;

use db\Db;
use \Exception;

/**
 * Class ConnectException
 * @package exceptions
 */
class ConnectException extends Exception {

    /** @var Db */
    public $db = '';

    /**
     * @param string $error
     * @param int    $errno
     * @param Db     $db
     * @return ConnectException
     */
    public static function create($error, $errno, Db $db) {
        $e = new self($error, $errno);
        $e->$db = $db;
        return $e;
    }
}