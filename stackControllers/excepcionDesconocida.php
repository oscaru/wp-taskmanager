<?php



  class excepcionDesconocidaController extends TaskController{
        
        // 1- enviamos mailAlusuario
        
        public function processTask(){
            $task = $this->task;
            
           
            $content = json_encode($task); 
            $mailsTo = $GLOBALS['mailsTo'];
            $fails = array();
            foreach($mailsTo as $to){
                if(!wp_mail($to, 'Error de EXCEPCION en '.get_bloginfo('url') , $content)){
                    $fails[]=$to;
                }
            }
            if(empty($fails)) return new Response(true);
            else {
                return new Response(false, $fails);
            }
        }
    }