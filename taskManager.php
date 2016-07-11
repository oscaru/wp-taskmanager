<?php
/**
 * TODO : 
 * Recuerda  activar los Schedules
 *  Desactivar wp-cron.php 
define('DISABLE_WP_CRON', true);
 */
define('LIBRARIES', __DIR__.'/libraries');


require __DIR__.'/StackModel.php';
require __DIR__.'/TaskStack.php';

if (!function_exists('write_log')) {
    function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}


if(!function_exists('tz_date')){
    /**
     * Date con timezone ,
     */
    function tz_date($format="r", $timestamp=false, $timezone='Europe/Madrid'){
        $userTimezone = new DateTimeZone(!empty($timezone) ? $timezone : 'GMT');
        $gmtTimezone = new DateTimeZone('GMT');
        $myDateTime = new DateTime(($timestamp!=false?date("r",(int)$timestamp):date("r")), $gmtTimezone);
        $offset = $userTimezone->getOffset($myDateTime);
        return date($format, ($timestamp!=false?(int)$timestamp:$myDateTime->format('U')) + $offset);
    }
    
}




class Response {
    public $status;
    public $result;
    
    function __construct($status,$result='') {
        $this->status = $status;
        $this->result = $result;
    }
}



class TaskController {
    
    protected $task;
    protected $model;
    
    function __construct($task, $model) {
        $this->model = $model;
        $task->data  = unserialize($task->data);
        $this->task = $task;
        
        $lang = (!empty($task->data['lang']))? $task->data['lang'] : 'en';
        $this->load($lang);
        
    }
    
    protected function load($lang){
        if(strtolower($lang) == 'es') {
            load_textdomain( 'taskdomain', THEME_PATH.'/languages/es_ES.mo' );
        }else {
            unload_textdomain( 'taskdomain' );
        }
    }
    
    /*
     * return class response
     */
    public function processTask(){
        return new Response(false,'No está implementada processTask');
    }
    
    
}



class taskManager {
  
    const PRIORIDAD_ALTA   = 3; 
    const PRIORIDAD_NORMAL = 5;
    const PRIORIDAD_BAJA   = 10;
    
    private $stack;
    protected $controllersDir;
    
    
    
    function __construct() {
        global $wpdb;
        add_action('init',array($this,'schedules_activation'));

        
        $this->stack = new TaskStack ($wpdb);
        $this->actionJobs();
        if(!empty($_GET['clearTasks'])){ 
                $this->clear_schedules(); 
                $this->test();
                return;}
        if(!empty($_GET['activeTasks'])){ 
                $this->schedules_activation(); 
                $this->test();
                return;}
        if(!empty($_GET['testTask'])){ $this->test(); }
        if(!empty($_GET['force'])){
            write_log ('Forzando las tareas');
            add_action('init', array( $this,'f_hourly'));
            add_action('init', array( $this,'f_daily'));
            return;
        }
       
        
        register_shutdown_function( array($this,'processShutdownStack'));  
       
    }
    
    
    public function actionJobs(){
        add_action( '1min_task_job' , array($this,'f_1min'), 10 );
        add_action( '15min_task_job' , array($this,'f_15min'), 15 );
        add_action( 'hourly_task_job', array($this,'f_hourly'), 20 );
        add_action( 'daily_task_job' , array($this,'f_daily'),30 );
        
        
    }
    
    
    public function registerTasksSchedules($schedules) {
        
        //defaults The default options are hourly, twicedaily or daily
        $schedules['1min'] = array(
                'interval' => 1 * 60, // 15 minutos
                'display' => __( '1 minute')
        );
        
        $schedules['15min'] = array(
                'interval' => 15 * 60, // 15 minutos
                'display' => __( '15 minutes')
        );
        
        $schedules['hourly'] = array(
                'interval' => 60 * 60, // 60 minutos
                'display' => __( '1 hour')
        );
      
        return $schedules;
    }
    
    
    function test(){
        
        $min1  = wp_next_scheduled('1min_task_job');
        $min15 = wp_next_scheduled('15min_task_job');
        $hly   = wp_next_scheduled('hourly_task_job');
        $dly   = wp_next_scheduled('daily_task_job');
        echo 'Hora actual:'.date('d/m/Y H:i:s').'<br>';
        var_dump('1min_task_job: '.  ($min1? date('d/m/Y H:i:s',$min1) : '--' ) );
        var_dump('15min_task_job: '.  ($min15? date('d/m/Y H:i:s',$min15) : '--' ) );
        var_dump('hourly_task_job: '. ($hly?   date('d/m/Y H:i:s',$hly) : '--' ) );
        var_dump('daily_task_job: '.  ($dly?   date('d/m/Y H:i:s',$dly) : '--' ) );
     
        
        
        
    }
    
    
    function schedules_activation() {
        
        add_filter( 'cron_schedules', array($this, 'registerTasksSchedules') ); //resgistramos los periodos del cron.
        
        if(!wp_next_scheduled('1min_task_job')) wp_schedule_event( time(), '1min' , '1min_task_job'  );        
        if(!wp_next_scheduled('15min_task_job')) wp_schedule_event( time(), '15min' , '15min_task_job'  );
        if(!wp_next_scheduled('hourly_task_job'))wp_schedule_event( time(), 'hourly', 'hourly_task_job' );
        if(!wp_next_scheduled('daily_task_job'))wp_schedule_event( time(), 'daily' , 'daily_task_job');
       
    }
    
    
    function clear_schedules(){
        wp_clear_scheduled_hook( '1min_task_job'   );
        wp_clear_scheduled_hook( '15min_task_job'   );
        wp_clear_scheduled_hook( 'hourly_task_job' );
        wp_clear_scheduled_hook( 'daily_task_job' );
        
    }
    
    
    
    
    public function f_daily(){
        //tareas de mantenimiento
        $this->processStack(self::PRIORIDAD_BAJA);
        syncMailChimp();
        
    }
    public function f_15min(){
         
    }
    
    public function f_1min(){
         $this->processStack(self::PRIORIDAD_ALTA);
    }
    
    public function f_hourly(){
        $this->processStack(self::PRIORIDAD_NORMAL);
        if(function_exists('checkComprasAbandonadas') )checkComprasAbandonadas(); 
    }
    


    public function processStack($priority = 10){
        write_log ('Procesando tareas de prioridad '.$priority);
        $this->stack->processStack($priority);
        
    }
    
    public function processShutdownStack() {
        
    }
    
    
}

function taskManager(){
    if(empty($GLOBALS['taskManager'])){
        $GLOBALS['taskManager'] = new taskManager();
    }
    return  $GLOBALS['taskManager'];
}

if(defined('DOING_CRON')){
    taskManager();
}

