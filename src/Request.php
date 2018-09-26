<?php

namespace elanpl\L3;

class Request{
    public $Path;
    public $Method;
    public $Accept;
    public $AcceptTypes;
    public $AcceptLanguage;
    public $UserAgent;
    public $Headers;
    
    private $RequestData;
    
    public function __construct(){
        if(isset($_GET['path']))
            $this->Path = $_GET['path'];
        $this->Method = $_SERVER['REQUEST_METHOD'];
        $this->Accept = $_SERVER['HTTP_ACCEPT'];
        $this->AcceptTypes = $this->ParseAccept($this->Accept);
        $this->AcceptLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])?$_SERVER['HTTP_ACCEPT_LANGUAGE']:"";
        $this->UserAgent = $_SERVER['HTTP_USER_AGENT'];
        if(function_exists('apache_request_headers')){
            $this->Headers = apache_request_headers();
        }
        else{
            $this->Headers = array();
            foreach($_SERVER as $key => $value) {
                if (substr($key, 0, 5) <> 'HTTP_') {
                    continue;
                }
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $this->Headers[$header] = $value;
            }
        }
        
        $this->getRequestData();
        $this->getJsonPost();
    }

    public function ParseAccept($accept){
        //cut out the spaces
        $accept = str_replace(" ", "", $accept);

        $result = array();
        $quality_factors = array();

        // find content type and corresponding quality factor value
        foreach(explode(",", $accept) as $AcceptParts){
            $type = explode(";", $AcceptParts);
            $result[] = $type[0];
            if(count($type)>1){
                if($type[1][0].$type[1][1] == "q="){
                    $quality_factors[] = substr($type[1],2);
                }
                else if($type[2][0].$type[2][1] == "q="){
                    $quality_factors[] = substr($type[2],2);
                }
                else{
                    $quality_factors[] = 1;
                }
            }
            else{
                $quality_factors[] = 1;
            }
        }

        // sort the types according to quality factors
        array_multisort($quality_factors, SORT_DESC, SORT_NUMERIC, $result);

        return $result;
    }
    
    public function getRequestData(){
        if(\in_array('application/json', $this->AcceptTypes)){
            $this->getJsonPost();
        }
    }


    public function getAll(){
        return $this->RequestData;
    }
    
    public function get( $fields, $default = null, $erroIfNotExist = false ){
        if ( \is_array($fields) ) {
            $result = array();
            foreach( $fields as $field ) {
                $result[$field] = getRequestValue( $field, $default, $erroIfNotExist );
            }
            return $result;
        } else {
            return $this->getRequestValue( $fields, $default, $erroIfNotExist );
        }
    }

    private function getRequestValue( $field, $default, $erroIfNotExist ) {
        if (isset($this->RequestData[ $field ] ))
            return $this->RequestData[ $field ];
        elseif ( $erroIfNotExist  )
            throw new \Exception( $field." not exist!" );
        else
            return $default;
    }       
    
    
    private function getJsonPost(){
        $request_body = file_get_contents('php://input');
        $this->RequestData = \json_decode($request_body, true);
    }
    
}