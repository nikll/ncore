<?

namespace exceptions;

use \Exception;

/**
 * Class QueryException
 * @package exceptions
 */
class QueryException extends Exception {
    /** @var string */
    public $query = '';

    /**
     * @param string $error
     * @param int    $errno
     * @param string $query
     * @return QueryException
     */
    public static function create($error, $errno, $query='') {
        $e = new self($error, $errno);
        $e->query = $query;
        return $e;
    }
}