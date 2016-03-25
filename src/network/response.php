<?php
namespace Quick\Network;


class Response {
    private $statusCode = 200,
            $headers = [],
            $body = '';
    private static $successCodes = [
        200,
        301,
        302
    ];
            
    public function __construct($statusCode, $headers, $body){
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }
    
    public function json(){
        return json_decode($this->body, true);
    }
    
    public function text() {
        return $this->body;
    }
    
    public function getStatusCode(){
        return $this->statusCode;
    }
    
    public function getHeaders() {
        return $this->headers;
    }
    
    public function isOk() {
        return in_array($this->statusCode, self::$successCodes);
    }
}