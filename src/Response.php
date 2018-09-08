<?php

namespace elanpl\L3;

class Response{

    protected $headers;
    protected $code;
    protected $reasonPhrase;
    protected $body;

    public function __construct()
    {
        $this->headers = array();
    }

    public function getStatusCode(){
        return $this->code;
    }

    public function getReasonPhrase(){
        return $this->reasonPhrase; 
    }

    public function getBody(){
        return $this->body;
    }

    public function withStatus($code, $reasonPhrase = ''){
        $this->code = $code;
        $this->reasonPhrase = $reasonPhrase;
        return $this;
    }

    public function withHeader($name, $value){
        $this->headers[$name] = $value;
        return $this;
    }

    public function withBody($body){
        if(is_string($body)){
            $this->body = $body;
        }
        else{
            throw new \Exception('Not implemented!');
        }
        return $this;
    }

    public function send(){
        if(!headers_sent()){
            if(isset($this->reasonPhrase)&&$this->reasonPhrase!=""){
                header($_SERVER['SERVER_PROTOCOL']." ".$this->code." ".$this->reasonPhrase);
            }
            else if(isset($this->code)) http_response_code($this->code);
            foreach($this->headers as $header => $value){
                header($header.": ".$value);
            }
        }
        if(isset($this->body)) echo $this->body;
    }

    public function redirect($url, $code=null){
        if(isset($code)){
            http_response_code($code);
        }
        else if(isset($this->code) && $this->code!=''){
            http_response_code($this->code);
        }
        header("Location: ".$url);
    }

}