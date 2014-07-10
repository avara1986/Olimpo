<?
/**
 * cerbero_Clients
 * @version Beta 0.6.3
 * @author Alberto Vara (C) Copyright 2011
 * @package gobalo.minotauro_CMS
 */
if(!defined("CLIENTS_TABLE")) define("CLIENTS_TABLE","clients");
class cerbero_Clients extends hidra_DB{
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
	* loginUser
	* Función genéreica de conectar a la sesión el usuario y comprobar si existe en la base de datos de CEO_USERS
	* */
	function loginClient($name,$password){
		$params=array();
		$ok=false;
		$params[]=$name;
		$params[]=$password;
		$cond="EMAIL = ? AND PASSWORD = (sha1(?)) AND STATUS = '1'";
		//$result=$this->query(" ID,NAME,SURNAME","ceo_users","","","LIMIT 0,1",$params);
		$result=$this->query("C.ID, C.EMAIL, C.PASSWORD, C.NAME, C.SURNAME, C.PHONE, C.ADDRESS, C.STATUS, C.FK_PRODUCT_ACTUAL",CLIENTS_TABLE." C","",$cond,"",$params);
		$num_result=count($result);
		if($num_result==0){
			$ok=false;
		}elseif($num_result>0){
			$this->assignSessionVar('client_name', htmlentities($result[0]["NAME"]." ".$result[0]["SURNAME"]));
			$this->assignSessionVar('client_id', $result[0]["ID"]);
			$this->assignSessionVar('client_level', 1);
			$this->assignCookieVar('client_id',$result[0]["ID"]);
			$this->assignCookieVar('client_level', 1);
			$ok=true;
		}else{
			$ok=false;
		}
		return $ok;
	}
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
		if(isset($_SESSION['client_name']) && isset($_SESSION['client_id'])){
			if(strlen($_SESSION['client_name'])>0 && strlen($_SESSION['client_id'])>0){
				$params=array();
				$params[]=$this->getRequestSessionVar('client_id');
				$result=$this->query("ID,NAME,SURNAME",CLIENTS_TABLE,"","ID = ? AND STATUS = '1'","LIMIT 0,1",$params);
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
     * saveUserData
     * Enter description here ...
     * @param $id string: id del usuario a buscar
     */
    function saveClientData($id,$params,$option,$insert_rol=false){
		$result=false;
    	$params_check=array($params[0],$id);
    	$check_email=$this->query("ID", CLIENTS_TABLE,"","LOWER(EMAIL) = LOWER(?) AND ID != ?","" ,$params_check);
    	if(count($check_email)>0){
    		return "EMAIL_EXIST";
    	}  
    	$result=$this->query("C.ID",CLIENTS_TABLE." C","","C.ID = ?","",array($id));
    	if($option!="delete"){
	    	if(count($results)>0){
				$m_cms->setAction('U');
				$m_cms->addUDCond('ID',$m_cms->getRequestVar('id'));
			}else{
				$m_cms->setAction('I');
				if(strlen($id)==0) $id=uniqid('');
				$m_cms->addIUField('ID',$id);
				$m_cms->addIUField('STATUS','1');
			}		
			$m_cms->addIUDTable(CLIENTS_TABLE);
			$m_cms->addIUField('EMAIL',$params[0]);
			if(strlen($params[1])>0)$m_cms->addIUField('PASSWORD',sha1($params[1]));
			$m_cms->addIUField('NAME',$params[2]);
			$m_cms->addIUField('SURNAME',$params[3]);
			$m_cms->addIUField('PHONE',$email);
			$m_cms->addIUField('COMPANY',$params[4]);
			$m_cms->addIUField('ADDRESS',$params[5]);
			$result = $m_cms->executeCommand();		
		}else{
			$result=$this->query("C.ID",CLIENTS_TABLE." C","","C.ID = ?","",array($id));	
			if(count($result)>0){
				$params_query=array();
				$params_query[]=$id;
				$cond="ID=?";
	    			if($users[0]['STATUS']=='1'){
	    				$result=$this->command("UPDATE","STATUS = '0' ", CLIENTS_TABLE,$cond, $params_query);	
	    			}elseif ($users[0]['STATUS']=='0'){
	    				$result=$this->command("UPDATE","STATUS = '1' ", CLIENTS_TABLE,$cond, $params_query);	
	    			}else{
	    				$result=false;
	    			}				    			
			}
		}
		return $result;
    }      
}
?>
