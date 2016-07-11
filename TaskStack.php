<?php

/*
 * CREATE TABLE `v2_TaskStack` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `status` int(2) DEFAULT NULL,
  `data` longtext NOT NULL,  
  `result`  varchar(255) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `procesed_on` datetime NULL DEFAULT NULL ,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'taskStackData'
CREATE TABLE `taskStackData` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(11) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `data` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */


define ('TAREA_PENDIENTE',0);
define ('TAREA_OK',1);
define ('TAREA_FAIL',-1);






class TaskStack {
    
    protected $model;
    protected $controllersDir;
    
   
    
    function __construct($wpdb){
        $this->model = new StackModel($wpdb);
        $this->controllersDir = __DIR__.'/stackControllers';
       
        //register_shutdown_function( array($this,'processStack'));        
    }
    
    function addTask($order_id,$name,$type,$data=array(),$priority=5){
        
        $task = array(
            'name'      => $name,
            'order_id'  => $order_id,
            'type'      => $type,
            'priority'  => $priority,
            'data'      => serialize($data)
        );
        
        return $this->model->add($task);
    }
    
    function removeTask($taskId){
        return $this->model->remove($taskId);
    }
    
     public function processStack($priority){
        $pendientes = $this->model->getNotProcessed($priority);
        
        write_log('Tareas encontradas '.count($pendientes));
        if(empty($pendientes)) return;
        $processed = array();
        foreach ($pendientes as $tarea){
           set_time_limit(30);
           $response = $this->runTask($tarea);
        
           $status = ($response->status)? 1: -1;
         
           $this->model->updateStatus($tarea->id, $status, $response->result);
        }  
    }
    
    protected function runTask($task){
       write_log('Ejecutando tarea '.$task->id);
       $controllerFile = $this->controllersDir."/{$task->type}.php";
       if(!is_file($controllerFile)) return new Response(FALSE,'No existe el controlador ' .$controllerFile);
       
       write_log('cargando controlador '.$controllerFile);
       $className = "{$task->type}Controller";
       
       include_once $controllerFile;
       $controller = new $className($task,$this->model);
       return $controller->processTask();
    }
    
}


function TaskStack(){
    if(!isset($GLOBALS['TaskStack'])){
        global $wpdb;
        $GLOBALS['TaskStack'] = new TaskStack($wpdb);
    }
    return $GLOBALS['TaskStack'];
}

