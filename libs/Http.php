<?

use exceptions\InvalidDataException;

/**
 * Class Http
 */
class Http {

    /** @var string */
    protected $request = null;

    /** @var array */
    protected $jsonRequest = null;

    /** @var string */
    protected $response = '';

    /** @var string */
    protected $jsonDebugResponse = '';

    /** @var float */
    protected $start;

    /** @var callable */
    protected $inputWrapper = null;

    /** @var callable */
    protected $outputWrapper = null;

    /**
     *
     */
    public function __construct(callable $inputWrapper = null, callable $outputWrapper = null) {
        $this->start         = microtime(true);
        $this->inputWrapper  = $inputWrapper;
        $this->outputWrapper = $outputWrapper;
        ob_start();
    }

    /**
     * @param $data
     * @return string
     */
    protected function inputWrapper($data) {
        $inputWrapper = $this->inputWrapper;
        return ($inputWrapper ? $inputWrapper($data) : $data);
    }

    /**
     * @param $data
     * @return string
     */
    protected function outputWrapper($data) {
        $outputWrapper = $this->outputWrapper;
        return ($outputWrapper ? $outputWrapper($data) : $data);
    }

    /**
     * @return array
     */
    public function getJsonRequest() {
        return (!is_null($this->jsonRequest) ? $this->jsonRequest : $this->jsonRequest = (array)json_decode($this->getRequest()));
    }

    /**
     * @return string
     */
    public function getRequest() {
        return (!is_null($this->request) ? $this->request : $this->request = $this->inputWrapper(@file_get_contents('php://input')));
    }

    /**
     * @return string
     */
    public function getLastResponse() {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getJsonDebugResponse() {
        return $this->jsonDebugResponse;
    }

    /**
     * @param array|string $data
     * @return string
     */
    public function jsonResponse($data = []) {
        if (!is_string($data)) $data = json_encode($data);
        $this->jsonDebugResponse .= ob_get_clean()."\n";
        return $this->response($data);
    }

    /**
     * @param $response
     * @return string
     */
    public function response($response) {
        $this->response = $response;
        return $this->outputWrapper($response.(defined('_DEBUG') && _DEBUG ? "\n\n".$this->jsonDebugResponse : ''));
    }

    public function getMicroTime() {
        return microtime(true) - $this->start;
    }
}