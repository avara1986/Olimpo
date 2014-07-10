<?
/**
 * cerbero_Clients
 * @version Beta 1.1.9
 * @author Alberto Vara (C) Copyright 2012
 * @package gobalo.cerbero_Clients
 */
if(APIS_GOOGLE){
	require_once ROOT_DIR_WEB.'google_api/google-api-php-client/src/Google_Client.php';
	require_once ROOT_DIR_WEB.'google_api/google-api-php-client/src/contrib/Google_DriveService.php';
}
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
	 * $encrypt
	 * Campos que contendrá la tabla "cliente"
	 */
	public $encrypt = "sha1";	
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
	 * $action
	 * Objeto donde se instancia la clase de Google_Client
	 */
	public $AG_client="";
	/**
	 * $AG_service
	 * Objeto donde se instancia la clase de Google_DriveService
	 */
	public $AG_service="";		
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
		if($this->checkActions()){
			switch ($this->action_login){	
				case "USER":
					$cond=$this->field_user." = LOWER(?) AND VALIDATE = '1' AND STATUS = '1'";
					$params[]=$name;					
				break;
				
				case "USER_PASS":
					
					$cond= $this->field_user." = LOWER(?) AND ".$this->field_pass." = (".$this->encrypt."(?)) AND VALIDATE = '1' AND STATUS = '1'";
					$params[]=$name;
					$params[]=$password;						
					//echo "ENTRA:".$cond."<br>".$password."<br>".$name."<br>";
				break;
				case "PASS":
					$cond=$this->field_pass." = (sha1(?)) AND VALIDATE = '1' AND STATUS = '1'";
					$params[]=$password;					
				break;			
			}
			$session_failures=$this->getRequestSessionVar('l_fails');
			$session_failures=0;
			if($session_failures<3){
				$result=$this->query($this->getClientFields(),$this->table."","",$cond,"",$params);
				$num_result=count($result);
				if($num_result==0){
					if(strlen($session_failures)>0){
						$session_failures++;
						$this->assignSessionVar('l_fails',$session_failures);
					}else{
						$session_failures=1;
						//echo "INTENTOS2: ".$session_failures."<br>";
						$result=$this->assignSessionVar('l_fails',$session_failures);
						if($result){
							$session_failures=$this->getRequestSessionVar('l_fails');
							//echo "INTENTOS3: ".$session_failures."<br>";
						}
					}
					if($session_failures>=3){
						$mensaje="Sobrepasados los intentos de inicio de sesión:<br/>
						<b>Usuario:</b>".$name."<br>
						<b>Pass:</b>".$password."<br>";
						$result=$this->sendError($mensaje,WEB_URL,"Sobrepasados los límites de acceso");
						$ok=false;
						$this->setAction('I');
						$this->addIUDTable(TABLE_BLACKLIST);
						$this->addIUField('IP',$_SERVER ["REMOTE_ADDR"]);
						$this->executeCommand();
					}
					$ok=false;
					}elseif($num_result==1){
							//$this->assignSessionVar('client_name', htmlentities($result[0][$this->fields_user_show]));
							$this->assignSessionVar('client_name', htmlentities(utf8_decode($result[0][$this->fields_user_show])));
							$this->assignSessionVar('client_id', $result[0]["ID"]);
							$this->assignSessionVar('client_level', 2);
							$this->assignCookieVar('client_id',$result[0]["ID"]);
							$this->assignCookieVar('client_level', 2);
							//echo "<br>OK<br>";
							$ok=true;
					}				
			}
		}
		return $ok;
	}	
	/**
	 * loginClientOpenID
	 * Función para loguear con Gmail y OpenID
	 * */	
	function loginClientOpenID($email){
		$params=array();
		$ok=false;
		$params[]=$email;
		//$session_failures=$this->getRequestSessionVar('l_fails');
		$session_failures=0;
		$cond="LOWER(GOOGLE_ACCOUNT) = LOWER(?) AND STATUS = '1'";
		if($session_failures<3){
			$result=$this->query($this->getClientFields(),$this->table."","",$cond,"",$params);
			$num_result=count($result);
			if($num_result==0){
				if(strlen($session_failures)>0){
					$session_failures++;
					$this->assignSessionVar('l_fails',$session_failures);
				}else{
					$session_failures=1;
					$result=$this->assignSessionVar('l_fails',$session_failures);
					if($result){
						$session_failures=$this->getRequestSessionVar('l_fails');
					}
				}
				if($session_failures>=3){
					$mensaje="Sobrepasados los intentos de inicio de sesión:<br/>
					<b>Usuario:</b>".$email."<br>
					<b>Pass:</b>".@$password."<br>";
					$result=$this->sendError($mensaje,WEB_URL,"Sobrepasados los límites de acceso");
					$ok=false;
					$m_cms->setAction('I');
					$m_cms->addIUDTable(TABLE_BLACKLIST);
					$m_cms->addIUField('IP',$_SERVER ["REMOTE_ADDR"]);
					$m_cms->executeCommand();
				}
				$ok=false;
			}elseif($num_result==1){
						$this->assignSessionVar('client_name', htmlentities(utf8_decode($result[0][$this->fields_user_show])));
						$this->assignSessionVar('client_id', $result[0]["ID"]);
						$this->assignSessionVar('client_level', 2);
						$this->assignCookieVar('client_id',$result[0]["ID"]);
						$this->assignCookieVar('client_level', 2);
				$ok=true;
			}else{
				$ok=false;
			}
		}else{
			$ok=false;
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
    	if(count($this->fields)==0 || !in_array("ID", $this->fields)){
    		$this->fields[]="ID";
    	}
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
	    				$sep=",";
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
     * setEncrypt
     * asigna valores al array fields
     */
    function setEncrypt($encrypt){
    	if($encrypt=='none'){
    		$this->encrypt='';
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
	/**
	 * checkLevelRol
	 * Comprueba los niveles de seguridad del usuario conectado
	 * @param $type
	 */	
	function checkLevelRol($lvlRol){
		$ok=false;
		if(strlen($lvlRol)>0){
			$lvlUser=$this->getRequestSessionVar('client_level');
			//echo "CERBERO ROL USER: ".$lvlUser."<br>";
			//echo "CERBERO ROL CHECK: ".$lvlRol."<br>";
			if(strlen($lvlUser)>0){
				if($lvlUser>=$lvlRol){
					$ok=true;
				}				
			}
		}
		return $ok;
	}	   
	/* METODOS de APIS de GOOGLE */
	function initializeGoogleClient(){
		$this->AG_client = new Google_Client();
		// Get your credentials from the APIs Console
		$this->AG_client->setClientId(AG_CLIENT);
		$this->AG_client->setClientSecret(AG_SECRET);
		$this->AG_client->setRedirectUri('https://www.export-accelerator.com/area_privada/exportacion_y_logistica');
		$this->AG_client->setScopes(array(AG_SCOPES));		
	}
	function initializeGoogleService($code){
		$this->AG_service = new Google_DriveService($this->AG_client);
		//echo "CODE: ".$_GET['code']."<br>";
		//echo "TEST: ".$_GET['test']."<br>";
		//Request authorization
		if (isset($code)&& strlen($code)>0) {
		  //$client->authenticate($_GET['code']);
		  //
		  
		  $authCode = trim(($code));
		  $accessToken = $this->AG_client->authenticate($authCode);
		  $this->AG_client->setAccessToken($accessToken);  
		  $_SESSION['token'] = $this->AG_client->getAccessToken();
		  //header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
		}
		if (isset($_SESSION['token'])) {
		  $this->AG_client->setAccessToken($_SESSION['token']);
		}
	}	
	function ap_setAccessToken(){
		if(isset($_SESSION['token'])){
			$this->AG_client->setAccessToken($_SESSION['token']);
		}		
	}
	function ap_getFiles(){
		if ($this->AG_client->getAccessToken()) {
			$files=$this->ap_retrieveAllFiles($this->AG_service);
			$_SESSION['token'] = $this->ap_existCon();
		}else{
			$files=false;
		}
		return $files;
	}
	function ap_createConURL(){
		return $this->AG_client->createAuthUrl();
	}	
	function ap_existCon(){
		return $this->AG_client->getAccessToken();
	}
	function ap_retrieveAllFiles($service) {
		$result = array();
		$pageToken = NULL;
	
		do {
			try {
				$parameters = array();
				if ($pageToken) {
					$parameters['pageToken'] = $pageToken;
				}
				$files = $service->files->listFiles($parameters);
				 /*
				echo "1<pre>";
				print_r($files);
				echo "</pre>";
				*/
				//$pageToken = $files->getNextPageToken();
				return $files;
				//$result = array_merge($result, $files->getItems());
	
			} catch (Exception $e) {
				print "An error occurred: " . $e->getMessage();
				$pageToken = NULL;
			}
		} while ($pageToken);
		return $files;
	}	
}
?>
