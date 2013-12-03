<?php
/*
 * Php - proxy (html only)
 * Author - László Dudás <ld@airndsoft.com> 03.dec.2013
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 03.dec.2013
 */

class osonproxy{
    public $config = array();
    private $cs;
    private $urlinar;
    public $postout;
    public $response;
    public $cookie=array();
    public $cookies;
    public $httpheader=array();
    private $httpheaders;
    public $httpbody;
    public $outcookies;
    public $inputar= array();

    function  __destruct() {
    	  print($this->httpbody);
        curl_close($this->cs);
    }
    
    function  __construct() {
       $this->cs = curl_init();
    }

	 function base64urlout($input){
        return ($input[1]."='".$this->config['targeturl']."?".$this->config['linkval']."=".strtr(base64_encode($input[2]), '+/=', '-_,')."'");
	}

   function base64de($input) {
       return base64_decode(strtr($input, '-_,', '+/='));
   }
   
   function urlmaker() {
       if (isset($this->inputar[$this->config['linkval']])){
           return $this->config['originalurl'].$this->base64de($this->inputar[$this->config['linkval']]);
       }else{
           return $this->config['originalurl'];
       }   
   }

   function responseprocessor(){
        $this->response = str_replace("HTTP/1.1 100 Continue\r\n\r\n","",$this->response);
        $this->response = str_replace($this->config['originalurl'],$this->config['targeturl'],$this->response);
	     $splitted = explode("\r\n\r\n", $this->response, 3);
        $this->httpbody = $splitted[count($splitted)-1];
        $this->httpheaders = $splitted[1];
   }
   
   function headprocessor(){
        $data=explode("\n",$this->httpheaders);
        $this->httpheader['status']=$data[0];
        array_shift($data);
        foreach($data as $each){
            $middle=explode(":",$each);
            if(count($middle)>1){
                $this->httpheader[trim($middle[0])] = trim($middle[1]);
            }
        }        
   }
   
   function cookieproccessor() {
       if (isset($this->httpheader["Set-Cookie"])){ 
           $data=explode(";",$this->httpheader["Set-Cookie"]);
           foreach($data as $each){
               if($middle=explode(":",$each, 2)){
               	 if(count($middle)>1){
                      $this->cookie[trim($middle[0])] = trim($middle[1]);
                   }
               }
           }
       }   	
   }
   
   
   function htmlurlrewrite() {
       $this->httpbody = preg_replace_callback('/(href|src|action)=[\'"](?P<link>\S+)[\'"]+?/', array(&$this, 'base64urlout'), $this->httpbody);
   }
   
   function proxyreq() {
       curl_setopt($this->cs, CURLOPT_URL,  $this->urlmaker());
       curl_setopt($this->cs, CURLOPT_HEADER, 1);
       curl_setopt($this->cs, CURLOPT_POSTFIELDS, json_encode($this->postout));
       curl_setopt($this->cs, CURLOPT_RETURNTRANSFER,1);
       curl_setopt($this->cs, CURLOPT_TIMEOUT,30);
       curl_setopt($this->cs, CURLOPT_POST, true);
       curl_setopt($this->cs, CURLOPT_CUSTOMREQUEST, $this->config['REQUEST_METHOD']);
       curl_setopt($this->cs, CURLOPT_FOLLOWLOCATION, true);
       if (isset($this->outcookies)) {
            curl_setopt($this->cs, CURLOPT_COOKIE, $this->outcookies);
       }
       $this->response = curl_exec ($this->cs);
   }
   
   function proxyprocess(){
       $this->proxyreq();
       $this->responseprocessor();
       $this->headprocessor();
       $this->cookieproccessor();
       if (preg_match("/html/",$this->httpheader["Content-Type"])){
          $this->htmlurlrewrite();
       }
   }
}

session_start();
$cu = new osonproxy;
$cu->config['originalurl'] = "";
$cu->config['targeturl'] = "";
$cu->config['linkval'] = "snjkcaq";
$cu->config['REQUEST_METHOD']=$_SERVER['REQUEST_METHOD'];
$cu->inputar=$_GET;
$cu->postout = $_POST;
if(isset($_SESSION['savedcookies'])){
   $cu->outcookies=$_SESSION['savedcookies'];
}
$cu->proxyprocess();
header("Content-Type:".$cu->httpheader["Content-Type"]);
if (isset($cu->httpheader["Set-Cookie"])){ 
    $_SESSION['savedcookies'] = $cu->httpheader["Set-Cookie"];
}



?>