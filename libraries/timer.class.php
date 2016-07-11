<?php

class Timer{
    
    protected static $instancia;
    protected $tiempos = array();
    
    protected function __constructor(){
        
    }
    
    public static function getInstance(){  
      if (  !self::$instancia instanceof self){
         self::$instancia = new self;
      }
      return self::$instancia;
    }
    
    public function init(){
        $this->tiempos = array();
       $this->tiempos['inicio'] = microtime(true);
    }
    
    public function setTime($code){
        if (in_array($code,array('inicio','end' ))) return;
        $this->tiempos[$code] =  microtime(true) - $this->tiempos['inicio'];
    }
    
    public function end(){
         $this->tiempos['end'] = microtime(true);
        //setLog('microtime', $this->tiempos);
        print_r($this->tiempos);
        
    }
    
    
    
}

