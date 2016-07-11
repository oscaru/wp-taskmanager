<?php



class StackModel {
    protected $db;
   
    protected $tableName = 'TaskStack';
    protected $fields = array(
        'name',
        'order_id',
        'type',
        'priority',
        'data'
    );
   
    
    
    function __construct($wpdb) {
        $this->db = $wpdb;
        $this->controllersDir = __DIR__.'/stackControllers';
    }
    
    public function remove($taskId){
        $sql = "DELETE FROM {$this->tableName} WHERE id = $taskId ";
        return $db->query($sql); 
    }
    
    public function add($task){
        $valores = array();
        $db = $this->db;
        
        foreach($this->fields as $field){
           // if(empty($task[$field])) return false;
            $valores[] = addslashes($task[$field]);
        }
        
        $sql = "INSERT INTO {$this->tableName} (". implode (',',$this->fields) ." , status) VALUES ('". implode("','", $valores) ."', ". TAREA_PENDIENTE .") ";
       
        if(!$db->query($sql)) return false;
        $task_id = $db->insert_id;
       
        return $task_id;
    }
    
    function notProcessed ($order_id = false){
        $db = $this->db;
        $filterByOrder = ($order_id)? " AND order_id = $order_id  " : '';
        $query = "SELECT * FROM {$this->tableName} WHERE status = ".TAREA_PENDIENTE." {$filterByOrder} ORDER BY priority asc,  created_on DESC ";
        return $db->get_results($query);
    }
    
    function updateStatus($id, $status, $result= ''){
        $db = $this->db;
        $now = tz_date('Y-m-d H:i:s');
        $result = addslashes(serialize($result));
        $sql  = "UPDATE {$this->tableName} SET status ={$status}, result='{$result}', procesed_on = '{$now}'  WHERE id = {$id} ";
      
        $db->query($sql);
    }
    
    function getNotProcessed($priority){
        $db = $this->db;
        $sql = "SELECT * from {$this->tableName} WHERE priority <= {$priority} AND status = ". TAREA_PENDIENTE ;
        return $db->get_results($sql);
    }
}
