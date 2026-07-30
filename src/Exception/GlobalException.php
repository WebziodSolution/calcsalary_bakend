<?php
namespace Common\Exception;

use Exception;

class GlobalException extends Exception {
    protected $message;

    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->message = $message;
    }

    public function getCustomMessage() {
        return $this->message;
    }
}
