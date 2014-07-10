<?/**
* minotauro_CMS
* @version Beta 1.0.3
* @author Alberto Vara (C) Copyright 2012
* @package gobalo.minotauro_CMS
*/
class minotauro_CMS extends hidra_DB{
	function loginAnonymous(){
		$user_id=uniqid();
		if($this->getRequestSessionVar('user_id') == "" ){
			$this->assignSessionVar('user_id',$user_id);
			if($this->getRequestCookieVar('user_id') == "" || $this->getRequestSessionVar('user_id') != $this->getRequestCookieVar('user_id')){
				$this->assignCookieVar('user_id',$user_id);
				$_SESSION['user_rol']='0';
			}	
		}else{
			if($this->getRequestCookieVar('user_id') == "" || $this->getRequestSessionVar('user_id') != $this->getRequestCookieVar('user_id')){
				$this->assignCookieVar('user_id',$this->getRequestSessionVar('user_id'));
				$_SESSION['user_rol']='0';
			}	
		}		
	}
	/**
	* loginUser
	* Función genéreica de conectar a la sesión el usuario y comprobar si existe en la base de datos de CEO_USERS
	* */
	function loginUser($name,$password){
		$params=array();
		$ok=false;
		$params[]=$name;
		$params[]=$password;
		$session_failures=$this->getRequestSessionVar('l_fails');
		$cond="LOWER(EMAIL) = LOWER(?) AND PASSWORD = (md5(?)) AND STATUS = '1'";
		//$result=$this->query(" ID,NAME,SURNAME","ceo_users","","","LIMIT 0,1",$params);
                //echo $session_failures;
		if($session_failures<3){
			$result=$this->query("ID, EMAIL, PASSWORD, CREATED, NAME, SURNAME, PHONE, ADDRESS, GOOGLE_ACCOUNT, ROL, STATUS",
			TABLE_USERS."",
			"",$cond,"",$params);
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
				 $m_cms->executeCommand();	
				}			
				$ok=false;
			}elseif($num_result==1){
				$this->assignSessionVar('user', htmlentities($result[0]["NAME"]." ".$result[0]["SURNAME"]));
				$this->assignSessionVar('user_id',$result[0]["ID"]);
				$this->assignSessionVar('user_rol',$result[0]["ROL"]);
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
	* loginUser
	* Función genéreica de conectar a la sesión el usuario y comprobar si existe en la base de datos de CEO_USERS
	* */
	function loginUserOpenID($email){
		$params=array();
		$ok=false;
		$params[]=$email;
		//$session_failures=$this->getRequestSessionVar('l_fails');
		$session_failures=0;
		$cond="LOWER(GOOGLE_ACCOUNT) = LOWER(?)AND STATUS = '1'";
		if($session_failures<3){
			$result=$this->query("ID, EMAIL, PASSWORD, CREATED, NAME, SURNAME, PHONE, ADDRESS, GOOGLE_ACCOUNT, ROL, STATUS",
			TABLE_USERS."",
			"",$cond,"",$params);
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
				$this->assignSessionVar('user', htmlentities($result[0]["NAME"]." ".$result[0]["SURNAME"]));
				$this->assignSessionVar('user_id',$result[0]["ID"]);
				$this->assignSessionVar('user_rol',$result[0]["ROL"]);
				$ok=true;
			}else{
				$ok=false;
			}			
		}else{
			$ok=false;
		}
		return $ok;
	}	
	function logoutUser(){
		$this->assignSessionVar('user', "");
		$this->assignSessionVar('user_id',"");
		$this->assignSessionVar('user_rol',"");
		return true;
	}
	/**
	 * userIsLogued
	 * Verifica que el usuario esta logueado y que existe en la BBDD
	 * */
	function userIsLogued(){
		$ok=false;
		if(isset($_SESSION['user']) && isset($_SESSION['user_id'])){
			if(strlen($_SESSION['user'])>0 && strlen($_SESSION['user_id'])>0){
				$params=array();
				$params[]=$this->getRequestSessionVar('user_id');
				$result=$this->query("ID,NAME,SURNAME",TABLE_USERS,"","ID = ? AND STATUS = '1'","LIMIT 0,1",$params);
				$num_result=count($result);
				if($num_result==0){
					$ok=false;
				}elseif($num_result==1){
					$ok=true;
				}else{
					$ok=false;
				}
			}else{
				$ok=false;
			}
		}
		return $ok;
	}
	/**
	 * checkLevelRol
	 * Comprueba los niveles de seguridad del usuario conectado
	 * @param $type
	 */	
	function checkLevelRol($lvlRol){
		$ok=false;
		if(strlen($lvlRol)>0){
			$lvlUser=$this->getRequestSessionVar('user_rol');
			//echo "ROL USER: ".$lvlUser."<br>";
			//echo "ROL CHECK: ".$lvlRol."<br>";
			if(strlen($lvlUser)>0){
				if($lvlUser<=$lvlRol){
					$ok=true;
				}				
			}

		}
		return $ok;
	}
	/* creates a compressed zip file */
	function create_zip($files = array(),$name_files_into_zip = array(),$destination = '',$overwrite = false) {
	  //echo "ENTRAAAA: ".$destination;
	  //if the zip file already exists and overwrite is false, return false
	  if(file_exists($destination) && !$overwrite) { return false; }
	  //vars
	  $valid_files = array();
	  //if files were passed in...
	  if(is_array($files)) {
	    //cycle through each file
	    $i=0;
	    $valid_name_files_into_zip= array();
	    foreach($files as $file) {
	      if(file_exists($file)) {
	        $valid_files[] = $file;
	        //echo $file."<br>";
	        $valid_name_files_into_zip[]=$name_files_into_zip[$i];
	      }
	      $i++;
	    }
	  }
	  //if we have good files...
	  if(count($valid_files)>0) {
	  	
	    //create the archive
	    $zip = new ZipArchive();
	    if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
	      return false;
	    }
	    //add the files
	    $i=0;
	    foreach($valid_files as $file) {
	      $zip->addFile($file,$valid_name_files_into_zip[$i]);
	      $i++;
	    }
	    //debug
	    //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
	    $mensaje_zip=utf8_decode(" 
	    === =============================================================================== ===
	    ===                         XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX                     ===
	    ===                           http://wwww.XXXXXXXXXXXXXXXXXXX                       ===
	    === =============================================================================== ===");
	    $zip->setArchiveComment($mensaje_zip);
	    //close the zip -- done!
	    $zip->close();
	    if(! file_exists($destination)){
	    	$texto_error="[ERROR minotauro_CMS::create_zip] Se produjo un error: ".print_r(error_get_last(),true) ."\n";
			$result=$this->sendError($texto_error,WEB_URL);
	    }
	    //check to make sure the file exists
	    return file_exists($destination);
	  }
	  else
	  {
    	$texto_error="[ERROR minotauro_CMS::create_zip] Se produjo un error: ".print_r(error_get_last(),true) ."\n";
		$result=$this->sendError($texto_error,WEB_URL);	  	
	    return false;
	  }
	}            
}
?>
