<?php
/**
 * Hidra_DB
 * @version V 2.3
 * @author Alberto Vara (C) Copyright 2011
 * @package gobalo.Hidra_DB
 * 
 * 
 * Construir Query para INSERT, UPDATE, DELETE segmentada:
 * Seleccionar el tipo de accion a ejecutar.
 * Formar la query con addIUDField().
 * Seleccionar la tabla con addTable()
 * Poner las condiciones con addUDCond (Sólo para UPDATES y DELETES).
 * Realizar el comando con executeSegQuery()
 */

class hidra_DB extends OlimpoBaseSystem{
	/**
	 * $h_db
	 * Objeto donde se instancia la clase mysqli
	 */
	private $h_db;
	/**
	 * $con_id
	 * variable con la ID de la sesión
	 */
	private $con_id = "";	
	/**
	 * $link
	 * Objeto que recibe la conexión mysqli
	 */
	public $link;
	/**
	 * $actions
	 * Posibles acciones del sistema
	 */
	public $actions = array(
		'I'	=> 	'INSERT',
		'U'	=>	'UPDATE',
		'D'	=>	'DELETE'
	);
	/**
	 * $action
	 * Variable que guarda la accion a realizar
	 */
	public $action;	
	/**
	 * $query
	 * Variable que guarda la query para hacer UPDATE e INSERT
	 */
	public $query = "";			
	/**
	 * $params
	 * Variable que guarda los parámetros para contruir la query para UPDATE e INSERT
	 */
	public $params = array();	
	/**
	 * $conds
	 * Variable que guarda las condiciones de una query para UPDATE o DELETE
	 */
	public $conds = "";			
	/**
	 * $params
	 * Variable que guarda los parámetros para las condiciones de la query para UPDATE o DELETE
	 */
	public $params_cond = array();	
	
	/**
	 * $table
	 * Variable que guarda la pabla donde atacar
	 */
	public $table = "";	

	
	/**
	 * __construct
	 * Constructor de la clase
	 *
	 */
	public function __construct($dbhost,$dbuser,$dbpass,$dbname,$conect=true){
		$this->checkTime("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Creando la clase");
		if($conect==true){
			$this->connect($dbhost,$dbuser,$dbpass,$dbname);
		}
	}
	/**
	 * connect
	 * Crea conexión SQL
	 *
	 */
	public function connect($dbhost,$dbuser,$dbpass,$dbname){
		if(strlen($dbhost)==0 || strlen($dbuser)==0 || strlen($dbpass)==0 || strlen($dbname)==0){
			$texto_error="[ERROR ".get_class($this)."::__construct] No se realizó la conexión correctamente: ". $this->h_db->connect_error."\n";
			if(strlen($dbhost)==0) $texto_error.="<br>No se ha seleccionado servidor de conexión";
			if(strlen($dbuser)==0) $texto_error.="<br>No se ha seleccionado usuario";
			if(strlen($dbpass)==0) $texto_error.="<br>No se ha pasado la clave";
			if(strlen($dbname)==0) $texto_error.="<br>No se ha seleccionado Base de Datos";
			$result=$this->sendError($texto_error,WEB_URL);
			die("[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] ERROR DE CONEXIÓN A BASE DE DATOS");
		}else{
			$this->h_db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			/* check connection */
			if ($this->h_db->connect_error) {
				$texto_error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjo un error: ". $this->h_db->connect_error."\n";
				$result=$this->sendError($texto_error,WEB_URL);
				die("[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] ERROR DE CONEXIÓN A BASE DE DATOS");
			}else{
				$this->checkTime("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Construyendo la clase. Creando conexión ID: ".$this->getConectionID());
			}
		}
	}
	private function getConectionID(){
		$result=$this->masterQuery('SELECT CONNECTION_ID() CONNECTION',array());
		if(strlen($result[0]['CONNECTION'])>0){
			if(strlen($this->con_id)==0)$this->con_id=$result[0]['CONNECTION'];
			return $result[0]['CONNECTION'];
		}else{
			return false;
		}
		
	}
	public function close(){
		$result=$this->h_db->close();
		if($result===true){
			$this->checkTime("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Conexión a BBDD cerrada correctamente ID <b>".$this->con_id."</b>");
		}else{
			$this->checkTime("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] ERROR cerrando conexión a BBDD ID <b>".$this->con_id."</b>");
		}	
		return $result;
	}
	/**
	 * query
	 * Params:
	 * $columns: columnas de las tablas que se van a seleccionar en la consulta SQL
	 * $table: tabla que se seleccionará en la consulta
	 * $join: reglas para unir varias tablas
	 * $condition condiciones de unión
	 * $order condiciones de ordenación
	 * $params: parámetros de la consulta SQL
		* $debug: devuelve array con los datos de la consulta SQL
		*/
	public function query($columns,$table,$join="",$condition="",$order="",$parms,$debug=false){
		$this->checkTime("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Inicio de query contra ".$table);
		$result = array();
		$debug_params=$parms;
		$sql = "SELECT ".$columns." FROM ".$table." ".$join;
		if(strlen($condition)>0){
			if(strpos($join, 'GROUP BY')!==false){
				$key_cond=" HAVING ";
			}else{
				$key_cond=" WHERE ";
			}
			$sql .= $key_cond."".$condition;
		}
		if(strlen($order)>0)$sql .=" ".$order;
		$stmt = $this->h_db->prepare($sql);
		if(!$stmt){
			$texto_error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjo un error al ejecutar la sentencia SQL: ".$sql.".<br> El error fue: ".$this->h_db->error."\n";
			$result=$this->sendError($texto_error,WEB_URL);
			die ("error al ejecutar la sentencia SQL: ".$sql.".<br> El error fue: ".$this->h_db->error);
		}
		/****/
		$types="";
		if(count($parms)>0){
			$num_params=count($parms);
			if($num_params>0){
				for($i=0;$i<$num_params;$i++){
					$types.="s";
				}
				$parms = array_merge((array)$types,(array)$parms);
				call_user_func_array(array($stmt, "bind_param"), $this->refValues($parms));
			}
		}
		unset($parms);
		$stmt->execute();
		if($this->h_db->error){
			$texto_error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjo un error: ". $this->h_db->error."\n";
			$result=$this->sendError($texto_error,WEB_URL);
			die("ERROR BBDD: ".$this->h_db->error);
		}
		$meta = $stmt->result_metadata();
		while ( $field = $meta->fetch_field() ) {
			$parameters[] = &$row[$field->name];
		}
		call_user_func_array(array($stmt, 'bind_result'), $this->refValues($parameters));
		unset($parameters);
		$results=array();
		while ( $stmt->fetch() ) {
			$x = array();
			foreach( $row as $key => $val ) {
				//$val=utf8_encode($val);
				$val=str_replace("\\\"", '"', $val);
				$x[$key] = utf8_encode($val);
				//$x[$key] =htmlentities($x[$key]);
			}
			$results[] = $x;
		}
		if($debug==true){
			$results['debug']=array('SQL'=>$sql,'params' =>$debug_params);
		}
		$this->checkTime("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Fin de query contra <b>".$table."</b>");
        return  $results;				
	}
	public function masterQuery($sql,$parms,$returnResults=true,$debug=false){
		$this->checkTime("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Inicio de query: ".$sql);
		$result = array();
		$debug_params=$parms;
		$stmt = $this->h_db->prepare($sql);
		if(!$stmt){
			$texto_error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjo un error al ejecutar la sentencia SQL: ".$sql.".<br> El error fue: ".$this->h_db->error."\n";
			$result=$this->sendError($texto_error,WEB_URL);
			die ("error al ejecutar la sentencia SQL: ".$sql.".<br> El error fue: ".$this->h_db->error);
		}
		/****/
		$types="";
		if(count($parms)>0){
			$num_params=count($parms);
			if($num_params>0){
				for($i=0;$i<$num_params;$i++){
					$types.="s";
				}
				$parms = array_merge((array)$types,(array)$parms);
				call_user_func_array(array($stmt, "bind_param"), $this->refValues($parms));
			}
		}
		unset($parms);
		$stmt->execute();
		if($this->h_db->error){
			$texto_error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjo un error: ". $this->h_db->error."\n";
			$result=$this->sendError($texto_error,WEB_URL);
			die("ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."".$this->h_db->error);
		}
		$meta = $stmt->result_metadata();
		//$this->showArray($meta);
		$results=array();
		if($returnResults){
			while ( $field = $meta->fetch_field() ) {
				$parameters[] = &$row[$field->name];
			}
			call_user_func_array(array($stmt, 'bind_result'), $this->refValues($parameters));
			unset($parameters);
			while ( $stmt->fetch() ) {
				$x = array();
				foreach( $row as $key => $val ) {
					//$val=utf8_encode($val);
					$val=str_replace("\\\"", '"', $val);
					$x[$key] = utf8_encode($val);
					//$x[$key] =htmlentities($x[$key]);
				}
				$results[] = $x;
			}
			if($debug==true){
				$results['debug']=array('SQL'=>$sql,'params' =>$debug_params);
			}
			$this->checkTime("[".get_class($this)."::".__LINE__."] Fin de query: ".$sql);			
		}
        return  $results;				
	}	
	public function queryPaged($columns,$table,$join="",$condition="",$order="",$parms,$pag_show,$num_elements_page=5,$num_block_pages=3){
			if(strpos($join, 'GROUP BY')!==false){
				$result_page=$this->query("COUNT(*) NUM_ELEMENTS",$table,'',$condition,'',$parms,false);
			}else{
				$result_page=$this->query("COUNT(*) NUM_ELEMENTS",$table,$join,$condition,$order,$parms,false);
			}
        	
        	$total_block=($num_block_pages*2);
        	if(!is_numeric($pag_show)) $pag_show = 0;
	    	if($pag_show < 0) $pag_show = 0;
	    	/* Calcular número de páginas */
	    	$result_final['num_pages']=ceil($result_page[0]['NUM_ELEMENTS']/$num_elements_page);
	    	/* Validar que la página a ver no es mayor que el número de páginas*/
	    	if($pag_show >= $result_final['num_pages'] && $pag_show > 0) $pag_show = $result_final['num_pages']-1;
        	$in=$pag_show*$num_elements_page;
        	$fin=$num_elements_page;
        	$parms[]=$in;
        	$parms[]=$fin;
        	/* LIMITAMOS LOS RESULTADOS A LA PÁGINA ACTUAL*/
        	$result=$this->query($columns,$table,$join,$condition,$order." LIMIT ?,?",$parms,false);
        	$result_final['elements']=$result;
        	$result_final['num_elements']=$result_page[0]['NUM_ELEMENTS'];
        	$result_final['actual_page']=$pag_show;
        	$result_final['prev_page']=$pag_show-1;
        	if(($pag_show+1)<$result_final['num_pages']){
        		$result_final['next_page']=$pag_show+1;
        	}else{
        		$result_final['next_page']="";
        	}
        	
		    if(is_numeric($num_block_pages)){
	    		$num_blocks_next=$num_block_pages;
	    		$num_blocks_prev=$num_block_pages;
	    		$pag_show_aux=$pag_show+1;
	    		if(($pag_show_aux+$num_blocks_next)>$result_final['num_pages']){
	    			$max_blocks_next=$num_blocks_next-(($pag_show_aux+$num_blocks_next)-$result_final['num_pages']);	
	    		}else{
	    			$max_blocks_next=$num_blocks_next;
	    		}
	    		
	    		if(($pag_show_aux-$num_blocks_prev)<=0){
	    			$max_blocks_prev=$num_blocks_prev-($num_blocks_prev-$pag_show_aux+1);
	    		}else{
	    			$max_blocks_prev=$num_blocks_prev;
	    		} 
	    		
	    		if(($max_blocks_prev+$max_blocks_next)<$total_block){
	    			if($max_blocks_prev<$max_blocks_next){
	    				$max_blocks_next=$max_blocks_next+($num_block_pages-$max_blocks_prev);
	    			}elseif($max_blocks_prev>$max_blocks_next){
	    				$max_blocks_prev=$max_blocks_prev+($num_block_pages-$max_blocks_next);
	    			}
	    		}	
	    		$j=0;
	    		for($i=$pag_show+1;$j<$max_blocks_next && $i<$result_final['num_pages'];$i++){
	    			$result_final['blocks_next'][]=$i;
	    			$j++;
	    		}
	    		$j=0;
	    		for($i=$pag_show-1;$j<$max_blocks_prev && $i>=0;$i--){
	    			$result_final['blocks_prev'][]=$i;
	    			$j++;
	    		}	
	    		if(@is_array($result_final['blocks_prev'])) sort($result_final['blocks_prev']);  		    		
	    	}       	
        	unset($result);
        	unset($result_page);
        	return $result_final;			
	}
	/**
	 * Command
	 * Params:
	 * $type: tipo de comando: INSERT, UPDATE, DELETE
	 * $columns: columnas de las tablas que se van a seleccionar en la consulta SQL
	 * $table: tabla que se seleccionará en la consulta
	 * $condition condiciones de unión
	 * $params: par�metros de la consulta SQL
	 */
	public function command($type,$columns,$table,$condition="",$parms,$debug=false,$set_log=true){
		$result = array();
		$ok=true;
		if($type=='INSERT'){
			$sql = "INSERT INTO ".$table."  (".$columns.") VALUES (";
		}elseif($type=='UPDATE'){
			$sql = "UPDATE ".$table." SET ".$columns." ";
		}elseif($type=='DELETE'){
			if(strlen($columns)>0){
				$ok=false;
				$error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjo un error al ejecutar la sentencia SQL: DELETE no puede llevar columnas que editar \n";
			}
			$sql = "DELETE FROM ".$table."";
		}
		$types="";
		$cond="";
		$num_params=count($parms);
		foreach( $parms as $key => $val ) {
			$val=utf8_encode($val);
			//$val=str_replace("\\\"", '"', $val);
		}
		if($num_params>0){
			for($i=0;$i<$num_params;$i++){
				$types.="s";
				if(strlen($cond)>0 && $type=="INSERT")$cond.=",?"; else $cond.="?";
			}
			$parms = array_merge((array)$types,(array)$parms);
		}
		if($type=="INSERT") $sql.=$cond.")";
		if(strlen($condition)>0)$sql .=" WHERE ".$condition;
		if(strlen($condition)==0 && $type=='UPDATE' ){
			$ok=false;
			$error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] En update obligatorio mandar parámetros";
		}
		if($ok===true){
			$stmt = $this->h_db->prepare($sql);
			if(!$stmt){
				//print_r(error_get_last());
				$texto_error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjo un error: ". $this->h_db->error."\n";
				$result=$this->sendError($texto_error,WEB_URL);
				die("[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] query. Se produjo un error al ejecutar la sentencia SQL: ".$sql.". El error fue: ".$this->h_db->error);

			}
			if(count($parms)>0){
				call_user_func_array(array($stmt, "bind_param"), $this->refValues($parms))or die ("[ERROR]query. Se produjo un error al ejecutar la sentencia SQL: query: ".$sql."<br>Params:".print_r($parms).". El error fue: ".$this->h_db->error);	;
			}
			$results=$stmt->execute();
			if($results===true){
				$ok=true;
				//echo "OK";
			}else{
				$ok=false;
				//echo "ERROR";
			}			
		}else{
			$results=$error;
		}
		if($debug==true){
			$results['debug']=array('SQL'=>$sql,'params' =>$debug_params);
		}
		if($set_log===true){
			$ok = $this->insertLog($sql,$parms);
		}
		if($ok===true){
			return  $results;
		}else{
			return  "ERROR";
		}
	}
	/**
	 * setAction
	 * Define que tipo de query se va a construir
	 * @param $type
	 */
	public function setAction($type){
		$type = mb_strtoupper($type, 'UTF-8');
		if($type=='I' || $type=='U' || $type=='D'){
			$this->action=$this->actions[$type];
		}else{
			$texto_error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] No se ha seleccionado una acción válida\n";
			$result=$this->sendError($texto_error,WEB_URL);
			die($texto_error);
		}
		if(strlen($this->action)>0) 
			$result=true;
		else
			$result=false;
			
		return $result;	
	}
	/**
	 * addIUField
	 * Añade un campo para ir construyendo la query
	 * @param $field_name: Nombre del campo de la BBDD
	 * @param $value: valor a asignar
	 * * @param $not_null: si not_null = true, no se modificará el campo si la variable $value está vacía
	 */		
	public function addIUField($field_name,$value,$not_null=false){
		if($not_null===true && strlen($value)==0){
			
		}else{
			if($this->action == 'INSERT'){
				if(strlen($value)>0){
					$this->params[]=$value;
					if(strlen($this->query)>0)
						$this->query .= ", ".$field_name;
					else 
						$this->query = $field_name;
				}
				$result=true;
			}elseif($this->action == 'UPDATE'){
				$this->params[]=$value;
				if(strlen($this->query)>0)
					$this->query.=", ".$field_name."=?";
				else 
					$this->query=$field_name."=?";			
				$result=true;		
			}else{
				$result=false;
			}
			return $result;			
		}

	}
	/**
	 * addIUDTable
	 * Selecciona la tabla contra la que atacar
	 * @param $table_name: Nombre de la tabla a seleccionar
	 */		
	public function addIUDTable($table_name){
		$this->table = $table_name;
		return true;
	}
	/**
	 * addUDCond
	 * Añade una condición cuando se trata de UPDATES o DELETES
	 * @param $fields_name: Nombre de la tabla a seleccionar
	 */		
	public function addUDCond($fields_name,$values,$sep="AND"){
		if($this->action == 'DELETE' || $this->action == 'UPDATE'){
			if(is_array($values)){
				$num_values=count($values);
				for($i=0;$i<$num_values;$i++){
					$this->params_cond[]=$values[$i];
				}			
			}elseif(strlen($values)>0){
				$this->params_cond[]=$values;
			}
			if(strlen($this->conds)>0)
				$this->conds.=" ".$sep." ".$fields_name."=? ";
			else 
				$this->conds=$fields_name."=?";			
			$result=true;		
		}else{
			$result=false;
		}
		return $result;
	}
	/**
	 * executeCommand
	 * Ejecuta la query que se ha creado.
	 * $debug: devuelve la query que se ejecuta
	 * $set_log: guardar si/no los datos en el log
	 */
	public function executeCommand($debug=false,$set_log=true){
		$result = array();
		$ok=true;
		if($this->action == 'INSERT'){
			$sql = "INSERT INTO ".$this->table."  (".$this->query.") VALUES (";	
			$parms = $this->params;
		}elseif ($this->action == 'UPDATE'){
			$sql = "UPDATE ".$this->table." SET ".$this->query." ";
			$parms = array_merge((array)$this->params,(array)$this->params_cond);
		}elseif ($this->action == 'DELETE'){
			$sql = "DELETE FROM ".$this->table."";
			$parms = $this->params_cond;
		}
		$types="";
		$cond="";
		$num_params=count($parms);
		if($num_params>0){
			foreach( $parms as $key => $val ) {
				$val=utf8_encode($val);
			}
		}
		if($num_params>0){
			for($i=0;$i<$num_params;$i++){
				$types.="s";
				if(strlen($cond)>0 && $this->action == 'INSERT')$cond.=",?"; else $cond.="?";
			}
			$parms = array_merge((array)$types,(array)$parms);
		}
		if($this->action == 'INSERT') $sql.=$cond.")";
		if(strlen($this->conds)>0)$sql .=" WHERE ".$this->conds;
		if(strlen($this->conds)==0 && $this->action == 'U' ){
			$ok=false;
			$error="[ERROR Hidra_DB::command] En update obligatorio mandar parámetros";
		}
		if($ok===true){
			$stmt = $this->h_db->prepare($sql);
			if(!$stmt){
				//print_r(error_get_last());
				$texto_error="[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjo un error: ". $this->h_db->error."\n";
				$result=$this->sendError($texto_error,WEB_URL);
				die("[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] query. Se produjo un error al ejecutar la sentencia SQL: ".$sql.". El error fue: ".$this->h_db->error);

			}
			if(count($parms)>0){
				call_user_func_array(array($stmt, "bind_param"), $this->refValues($parms))or die ("[ERROR]query. Se produjo un error al ejecutar la sentencia SQL: query: ".$sql."<br>Params:".print_r($parms).". El error fue: ".$this->h_db->error);	;
			}
			$results=$stmt->execute();
			if($results==true){
				$ok=true;
			}else{
				echo $this->h_db->error;
				$ok=false;
			}
		}else{
			$results=$error;
		}
		if($debug==true){
			$results['debug']=array('SQL'=>$sql,'params' =>$debug_params);
		}
		if($set_log==true){
			$ok = $this->insertLog($sql,$parms);
		}
		$this->action="";// unset($this->action);
		$this->table=""; //unset($this->table);
		$this->query=""; //unset($this->query);
		$this->params=array(); //unset($this->params);
		$this->conds=""; //unset($this->conds);
		$this->params_cond=array(); //unset($this->params_cond);
		if($ok){
			return  $results;
		}else{
			return  false;
		}
	}
	public function showConstructQuery(){
		echo "==================================================<br>";
		echo "+ ACTION:      ".$this->action."                               ++<br>";
		echo "+ TABLE:       ".$this->table."                                ++<br>";
		echo "+ QUERY:       ".$this->query."                                ++<br>";
		echo "+ PARAMS:      <pre>".print_r($this->params,true)."</pre>      ++<br>";
		echo "+ CONDS:       ".$this->conds."                                ++<br>";
		echo "+ PARAMS_COND: <pre>".print_r($this->params_cond,true)."</pre> ++<br>";
		echo "==================================================<br>";
		
	} 
	private function insertLog($sql,$parms){
		$log_params=array();
		$log_params[]=$this->getRequestSessionVar('user_id');
		$log_params[]=$_SERVER ["REMOTE_ADDR"];
		$text_params=print_r($parms,true);
		$log_params[]="# QUERY:\n".$sql."\n# PARAMS:\n".$text_params."\n";
		$ok=$this->command("INSERT","FK_USER,IP,ACTION", "ceo_log","", $log_params,false,false);		
		return $ok;
	}
 	/**
        * getMaxReg
        * Devuelve el número de registros de una tabla
        * Params:
        * 
        */
    private function getMaxReg($table,$join="",$condition="",$parms,$debug=false){
        $num_reg=$this->query("COUNT(ID) NUM_REG",$table,$join,$condition,"",$parms,$debug=false);
        if(count($num_reg)==0){
        	return false;
        }else{
        	return $num_reg[0]['NUM_REG'];
        }
    }	
	/**
	 * refValues
	 * Params:
	 * $arr: Array a dividir
	 */
	private function refValues($arr){
		if (strnatcmp(phpversion(),'5.3') >= 0) {
			$refs = array();
			foreach($arr as $key => $value)
			$refs[$key] = &$arr[$key];
			$refs[$key] = $this->h_db->real_escape_string($refs[$key]);
			return $refs;
		}
		return $arr;
    	}
 		/**
        * startTransaction
        * Desactiva autocomit para si se produce un error no guardar el proceso
        * $arr: Array a dividir
        */    	
        public function startTransaction(){		  	        	
        	$this->h_db->autocommit(FALSE);
        	return true;
        }    
 		/**
        * endTransaction
        * Desactiva autocomit para si se produce un error no guardar el proceso
        * $arr: Array a dividir
        */    	
        public function endTransaction($result){	
        	if($result===true){
        		$this->h_db->commit();
        	}	
            if($result===false){
        		$this->h_db->rollback();
        	}        	  	        	
        	$this->h_db->autocommit(true);
        	return true;
	}
	/**
	 * writeCsvHard
	 * Leer registros de un fichero csv
	 * #filename: fichero a escribir
	 * #params: array de bidimensión a escribir
	 * #opciones:
	 * 		-delete: tiene que ir acompañado de una id en params para borrar
	 * 		-vaciar: vacia el fichero para volver a llenarlo
	 *
	 */
	public function writeXlsHard($filename="",$params,$option=""){
        	$num_results=array();
        	$write_header=false;
        	$ok=true;
             /**
             * Si la opción es vaciar, saltar proceso y vaciar fichero.
             * */ 
        	if($option!="vaciar"){
	        	$num_results=count($params);  
	        	$fp = fopen($filename, 'w');  
	        	if($write_header){
	        		/* Array para escribir la cabecera de el CSV con el nombre de los campos*/
	        	    foreach ($params[0] as $key => $value) {
	        	    		fputs($fp, mb_convert_encoding($value, 'UTF-16LE', 'UTF-8')."\t");  
	        	    		/*
	        	    		fputs
	        				$key_format=$key;
		            		$key_format=html_entity_decode($key_format,ENT_NOQUOTES);	
							$key_format=htmlspecialchars_decode($key_format);	    
							$key_format=utf8_decode($key_format);
							$key_format = mb_convert_encoding($key_format, 'UTF-16LE', 'UTF-8');    	    			
		            		$ar_cabecera[$key]=$key_format;  
		            		*/	    		
	        	    }
	        	    //fputcsv($fp, $ar_cabecera,';','"');
	        	    fputs($fp, "\n");          		
	        	}    		
        	    for($i=0;$i<$num_results;$i++){
        	    	foreach ($params[$i] as $key => $value) {
        	    		fputs($fp, mb_convert_encoding($value, 'UTF-16LE', 'UTF-8')."\t");  
        	    		/*
        	    		$value=html_entity_decode($value,ENT_NOQUOTES);	
						$value=htmlspecialchars_decode($value);	
						$value = mb_convert_encoding($value, 'UTF-16LE', 'UTF-8'); 	
						$params[$i][$key]=$value;	
						*/
        	    	}      	
            		fputs($fp, "\n"); 
        		}      		
        	}else{
        		$fp = fopen($filename, 'w');
        		fwrite($fp,"");
        	}	
			fclose($fp);
			return $ok;
	}	
	/**
	 * writeCsvHard
	 * Leer registros de un fichero csv
	 * #filename: fichero a escribir
	 * #params: array de bidimensión a escribir
	 * #opciones:
	 * 		-delete: tiene que ir acompañado de una id en params para borrar
	 * 		-vaciar: vacia el fichero para volver a llenarlo
	 *
	 */
	public function writeCsvHard($filename="",$params,$option=""){
        	$num_results=array();
        	$write_header=false;
        	$ok=true;
             /**
             * Si la opción es vaciar, saltar proceso y vaciar fichero.
             * */ 
        	if($option!="vaciar"){
	        	$num_results=count($params);  
	        	$fp = fopen($filename, 'w');  
	        	if($write_header){
	        		/* Array para escribir la cabecera de el CSV con el nombre de los campos*/
	        	    foreach ($params[0] as $key => $value) {
	        				$key_format=$key;
		            		$key_format=html_entity_decode($key_format,ENT_NOQUOTES);	
							$key_format=htmlspecialchars_decode($key_format);	    
							$key_format=utf8_decode($key_format);
							$key_format = mb_convert_encoding($key_format, 'UTF-16LE', 'UTF-8');    	    			
		            		$ar_cabecera[$key]=$key_format;  	    		
	        	    }
	        	    fputcsv($fp, $ar_cabecera,';','"');        		
	        	}    		
        	    for($i=0;$i<$num_results;$i++){
        	    	foreach ($params[$i] as $key => $value) {
        	    		$value=html_entity_decode($value,ENT_NOQUOTES);	
						$value=htmlspecialchars_decode($value);	
						$value = mb_convert_encoding($value, 'UTF-16LE', 'UTF-8'); 	
						$params[$i][$key]=$value;	
        	    	}      	
            		fputcsv($fp, $params[$i],';','"');
        		}      		
        	}else{
        		$fp = fopen($filename, 'w');
        		fwrite($fp,"");
        	}	
			fclose($fp);
			return $ok;
	}
	/**
	 * writeCsvSoft [DEPRECATED]
	 * Guarda registros de un fichero csv sin tocar los que existian
	 * #filename: fichero a escribir
	 * #params: array de una dimensión a escribir
	 * #opciones:
	 * 		-delete: tiene que ir acompañado de una id en params para borrar
	 * 		-vaciar: vacia el fichero para volver a llenarlo
	 *
	 */
	public function writeCsvSoft($filename="",$params,$option=""){
		$num_results=array();
		//asumimos que en csv siempre el campo $params[0] es la ID.
		$result=$this->readCsv($filename,$params[0]);
		$num_results=count($result);
		$ok=true;
		/**
		 * Si la opción es vaciar, saltar proceso y vaciar fichero.
		 * */
		if($option!="vaciar"){
			/**
			 * Si no encuentra coincidencias en el CSV lo inserta al final
			 * */
			if($num_results==0){
				$fp = fopen($filename, 'a+');
				fputcsv($fp, $params,';','"');
			}else{
				/**
				 * Si hay coincidencia, sustituye la nueva linea por la vieja
				 * */
				$result=$this->readCsv($filename);
				$num_results=count($result);
				$fp = fopen($filename, 'w+');
				for($i=0;$i<$num_results;$i++){
					if($result[$i][0]==$params[0]){
						if($option!="delete"){
							fputcsv($fp, $params,';','"');
						}
					}else{
						fputcsv($fp, $result[$i],';','"');
					}
				}
			}
		}else{
			$fp = fopen($filename, 'w+');
			fwrite($fp,"");
		}
		fclose($fp);
		return $ok;
	}
	/**
	 * readCsv
	 *
	 */
	public function readCsv($filename="",$delimiter=';',$enclosure='"'){
		$list_array=array();
		$row=0;
		ini_set('auto_detect_line_endings',TRUE);
		if (($handle = fopen($filename, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 0,$delimiter,$enclosure)) !== FALSE) {
				$num = count($data);
				$row++;
				for ($c=0; $c < $num; $c++) {
					$data[$c];
				}
				/*
				if(strlen($id)>0){
					if($id==$data[0]){
						$list_array[]=utf8_encode($data);
					}
				}else{
					
				}*/
				$list_array[]=$data;
			}
			fclose($handle);
		}
		return $list_array;
	}
}
?>