<?

namespace exceptions;

use \Exception;

/**
 * Class InvalidDataException
 * @package exceptions
 */
class InvalidDataException extends Exception {
    /** @var array */
    public $data;

    /**
     * @param string $message
     * @param array  $data
     * @return InvalidDataException
     */
    static function create($message, array $data = null) {
        $e = new self($message);
        $e->data = $data;
        return $e;
    }
}