<?
/**
 * cerbero_Clients
 * @version Beta 1.1.9
 * @author Alberto Vara (C) Copyright 2012
 * @package gobalo.cerbero_Clients
 */

class cerbero_Clients extends hidra_DB{
	/**
	 * $fields
	 * Campos que contendrá la tabla "cliente"
	 */
	public $table = "";	
	/**
	 * $fields
	 * Campos que contendrá la tabla "cliente"
	 */
	public $fields = array();
	/**
	 * $field_user
	 * Campo que contendrá el usuario de la tabla "cliente"
	 */
	public $field_user = "";
	/**
	 * $fields_user_show
	 * Campo/s que se mostrarán del "cliente"
	 */
	public $fields_user_show = "";			
	/**
	 * $field_pass
	 * Campo que contendrá la contraseña de la tabla "cliente"
	 */
	public $field_pass = "";		
	/**
	 * $action_login
	 * Posibles acciones del sistema
	 */
	public $actions_login = array(
		'U'	=> 	'USER',
		'UP'=>	'USER_PASS',
		'P'	=>	'PASS'
	);
	/**
	 * $action
	 * Variable que guarda la accion a realizar
	 */
	public $action_login = '';			
	/**
	 * loginClientAnonymous
	 * Conectar como usuario anónimo
	 */	
	function loginClientAnonymous(){
		$client_id=uniqid();
		if($this->getRequestSessionVar('client_id') == "" ){
			$this->assignSessionVar('client_id',$client_id);
			$this->assignSessionVar('client_level',0);
			if($this->getRequestCookieVar('client_id') == "" || $this->getRequestSessionVar('client_id') != $this->getRequestCookieVar('client_id')){
				$this->assignCookieVar('client_id',$client_id);
				//$_SESSION['user_rol']='0';
			}	
		}else{
			if($this->getRequestCookieVar('client_id') == "" || $this->getRequestSessionVar('client_id') != $this->getRequestCookieVar('client_id')){
				$this->assignCookieVar('client_id',$this->getRequestSessionVar('client_id'));
				//$_SESSION['user_rol']='0';
			}	
		}		
	}	
	/**
	* loginClient
	* Conecta la sesión con el usuario de la BBDD
	* */
	function loginClient($name="",$password=""){
		$params=array();
		$ok=false;
		//echo "ENTRA1: ".$name." PASS".$password."<br>";
		if($this->checkActions()){;
			switch ($this->action_login){	
				case "USER":
					$cond=$this->field_user." = LOWER(?) AND STATUS = '1'";
					$params[]=$name;					
				break;
				
				case "USER_PASS":
					$cond= $this->field_user." = LOWER(?) AND ".$this->field_pass." = (sha1(?)) AND STATUS = '1'";
					$params[]=$name;
					$params[]=$password;						

				break;
				case "PASS":
					$cond=$this->field_pass." = (sha1(?)) AND STATUS = '1'";
					$params[]=$password;					
				break;			
			}
			//echo "ENTRA1: ".$name." PASS".$password."<br>";
			//echo "FIELDS: ".$this->getClientFields()."<br>";
			//echo "CONDS: ".$cond."<br>";
			$result=$this->query('ID,'.$this->getClientFields(),$this->table."","",$cond,"",$params);
			$num_result=count($result);
			//$this->showArray($result);
			if($num_result>0){
				$this->assignSessionVar('client_name', htmlentities($result[0][$this->fields_user_show]));
				$this->assignSessionVar('client_id', $result[0]["ID"]);
				$this->assignSessionVar('client_level', 1);
				$this->assignCookieVar('client_id',$result[0]["ID"]);
				$this->assignCookieVar('client_level', 1);
				//echo "<br>OK<br>";
				$ok=true;
			}		
		}
		return $ok;
	}	
	/**
	 * logoutClient
	 * Desconecta al usuario
	 * */	
	function logoutClient(){
		$this->assignSessionVar('client_name', "");
		
		$this->assignSessionVar('client_id',"");
		$this->assignCookieVar('client_id',"");
		
		$this->assignSessionVar('client_level',"");
		$this->assignCookieVar('client_level',"");
		//echo "DESC: ".$this->getRequestVar("Desc");
		return true;
	}
	/**
	 * clientIsLogued
	 * Verifica que el usuario esta logueado y que existe en la BBDD
	 * */
	function clientIsLogued(){
		$ok=false;
		$num_result=0;
		if($this->table!='none'){
			if(isset($_SESSION['client_name']) && isset($_SESSION['client_id'])){
				if(strlen($_SESSION['client_name'])>0 && strlen($_SESSION['client_id'])>0){
					$params=array();
					$params[]=$this->getRequestSessionVar('client_id');
					if($this->checkActions()){
						$result=$this->query('ID,'.$this->getClientFields(),$this->table,"","ID = ? AND STATUS = '1'","",$params);
						$num_result=count($result);
					}
					if($num_result==1){
						$ok=true;
					}
				}
			}			
		}
		return $ok;
	}
		/**
	* checkActions
	* Verifica que se puede hacer una consulta a la BBDD sin cascar
	* */
	function checkActions(){
		$ok=true;
		$errors=array();
		if(strlen($this->action_login)>0){
			switch ($this->action_login){	
				case "USER":
					if(strlen($this->field_user)==0){
						$ok=false;
						$errors[]='NO_FIELD_USER';
					}				
				break;
				
				case "USER_PASS":
					if(strlen($this->field_pass)==0 || strlen($this->field_user)==0){
						$ok=false;
						$errors[]='NO_USER_PASS';
					}	
				break;
				case "PASS":
					if(strlen($this->field_pass)==0){
						$ok=false;
						$errors[]='NO_PASS';
					}							
				break;			
			}
		}else{
			$ok=false;
		}
		if(strlen($this->fields_user_show)==0){
			$errors[]='NO_FIELD_USER_SHOW';
			$ok=false;
		}
		if(strlen($this->table)==0){
			$errors[]='NO_TABLE';
			$ok=false;
		}		
		if($this->getClientFields()===false){
			$errors[]='NO_FIELDS';
			$ok=false;
		}	
		if($ok===false){
			echo "[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Se produjeron los siguientes errores: <br/>";
			for($i;$i<count($errors);$i++){
				echo $errors[$i]."<br>";
			}
			die();
		}
		return $ok;	
	}
    /**
     * setClientNameShow
     * asigna valores al array fields
     */
    function setClientTable($fields){
    	if(strlen($fields)>0){
    		$this->table=$fields;
    	}else{
    		return false;
    	}

    } 		
    /**
     * setClientNameShow
     * asigna valores al array fields
     */
    function setClientNameShow($fields){
    	if(strlen($fields)>0){
    		$this->fields_user_show=$fields;
    	}else{
    		return false;
    	}

    } 	
    /**
     * setClientFields
     * asigna valores al array fields
     */
    function setClientPass($field){
    	if(strlen($field)>0){
    		$this->field_pass=$field;
    	}else{
    		return false;
    	}

    } 
    /**
     * setClientFields
     * asigna valores al array fields
     */
    function setClientUser($field){
    	if(strlen($field)>0){
    		$this->field_user=$field;
    	}else{
    		return false;
    	}

    } 
    /**
     * setClientFields
     * asigna valores al array fields
     */
    function setClientFields($field){
    	if(strlen($field)>0){
    		$this->fields[]=$field;
    	}else{
    		return false;
    	}

    }         		
    /**
     * getClientFields
     * devuelve los campos de la tabla de clientes
     * @param $num string: número de resultados -> numérico o "all"
     * @param $method string: forma de devolverlo -> "string" o "array"
     */
    function getClientFields($num='all',$method='string'){
    	if($method=='array'){
    		if($num='all'){
    			return $this->fields;
    		}else{
    			if(is_numeric($num))
    				return $this->fields[$num];
    			else
    				return false;
    		}
    	}elseif($method=='string'){
    	    if($num='all'){
    			$num_fields=count($this->fields);
    		}elseif(is_numeric($num)){
    			$num_fields=$num;
    		}else{
    			return false;
    		}
    		$fields="";
    		$sep="";
    		if($num_fields>0){
	    		   for($i=0;$i<$num_fields;$i++){
	    			if(($i+1)==$num_fields){
	    				$sep="";
	    			}
	    			$fields.=$sep.$this->fields[$i];
	    			$sep=", ";
	    		} 			
    		}else{
    			return false;
    		}
    		return $fields;
    	}else{
    		return false;
    	}

    }  
	/**
	 * setActionLogin
	 * Define que tipo de query se va a construir
	 * @param $type
	 */
	public function setActionLogin($type){
		$type = mb_strtoupper($type, 'UTF-8');
		if($type=='U' || $type=='UP' || $type=='P'){
			$this->action_login=$this->actions_login[$type];
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
}
?>
