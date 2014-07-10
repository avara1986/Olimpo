<?/**
* minotauro_CMS
* @version Beta 0.6.3
* @author Alberto Vara (C) Copyright 2011
* @package gobalo.minotauro_CMS
*/
class minotauro_CMS extends hidra_DB{

    	/**
	* loginAnonymous
	* Función genéreica para crear una sesión al visitante aunque no exista login de usuario.
	* */
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
		$cond="LOWER(U.EMAIL) = LOWER(?) AND U.PASSWORD = (md5(?)) AND STATUS = '1'";
		//$result=$this->query(" ID,NAME,SURNAME","ceo_users","","","LIMIT 0,1",$params);
                //echo $session_failures;
		if($session_failures<3){
			$result=$this->query("U.ID, U.EMAIL, U.PASSWORD, U.CREATED, U.NAME, U.SURNAME, U.PHONE, U.ADDRESS, U.STATUS,R.NAME_ES ROL_NAME_ES,R.ID ID_ROL,R.NAME_EN ROL_NAME_EN","ceo_users U","LEFT JOIN ceo_users_rel_rols UR ON(UR.FK_USER=U.ID) LEFT JOIN ceo_rols R ON (R.ID=UR.FK_ROL)",$cond,"",$params);
			$num_result=count($result);
                        //$this->showArray($result);
			if($num_result==0){
				$this->assignSessionVar('asistente_login','0');
				//echo "INTENTOS: ".$session_failures."<br>";
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
				  die('Ha pasado el límite de intentos. Sus datos serán enviados a los administradores para evaluar si su bloqueo a esta página es permanente o no.');
				}			
				$ok=false;
			}elseif($num_result==1){
				$this->assignSessionVar('user', htmlentities($result[0]["NAME"]." ".$result[0]["SURNAME"]));
				$this->assignSessionVar('user_id',$result[0]["ID"]);
				$this->assignCookieVar('user_id',$result[0]["ID"]);
				$_SESSION['user_rol']="";
				$sep="";
				for($i=0;$i<$num_result;$i++){
					$_SESSION['user_rol'].=$sep.$result[$i]["ID_ROL"];
					$sep=",";
				}
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
				$result=$this->query("ID,NAME,SURNAME","ceo_users","","ID = ? AND STATUS = '1'","LIMIT 0,1",$params);
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
	 * getUsers [DEPRECATED IN V.7]
	 * Enter description here ...
	 * @param $id string: id del usuario a buscar
	 */
	function getUsers($id="",$rol="",$filtet_activo=true){
		$params=array();
		$cond="";
		$sep="";
		if(strlen($id)>0){
			$cond.=" U.ID=?";
			$params[]=$id;	
			$sep=" AND ";		
		}
        	if(strlen($rol)>0){
			$cond.=$sep." R.ID=?";
			$params[]=$rol;
			$sep=" AND ";			
		}	
        	if($filtet_activo){
			$cond.=$sep." U.STATUS = '1' ";
			$sep=" AND ";			
		}				
		//$cond.=$sep." AND STATUS = '1'"
		$users=$this->query("U.ID, U.EMAIL, U.PASSWORD, U.CREATED, U.NAME, U.SURNAME, U.PHONE, U.ADDRESS, U.STATUS, R.ID ID_ROL, R.NAME_ES ROL_NAME_ES, R.NAME_EN ROL_NAME_EN","ceo_users U","LEFT JOIN ceo_users_rel_rols UR ON(UR.FK_USER=U.ID) LEFT JOIN ceo_rols R ON (R.ID=UR.FK_ROL)",$cond,"ORDER BY CREATED DESC",$params);
		return $users;
	}
	/**
	 * saveUserData [DEPRECATED IN V.7]
	 * Enter description here ...
	 * @param $id string: id del usuario a buscar
	 */
	function saveUserData($id,$params,$option,$insert_rol=false){
	$result=false;
    	$params_check=array($params[0],$id);
    	$check_email=$this->query("ID", "ceo_users","","LOWER(EMAIL) = LOWER(?) AND ID != ?","" ,$params_check);
    	$params_check=array($params[2],$id);
    	//$check_name=$this->query("ID", "ceo_users","","LOWER(NICKUSER) = LOWER(?) AND ID != ?","" ,$params_check); 
    	if(count($check_email)>0){
    			return "EMAIL_EXIST";
    		}   
    	/*
    	if(count($check_name)>0){
    		return "NAME_EXIST";
    	} 
    		*/
    	if($option!="delete"){
    		$users=$this->getUsers($id);
    		if(count($users)>0){
				$sql="";
				$sep="";
				$params_query=array();
				if($users[0]['EMAIL']!=$params[0] && strlen($params[0])>0){
					$params_query[]=$params[0];
					$sql.=" EMAIL=?";
					$sep=", ";
				}
				if($users[0]['PASSWORD']!=md5($params[1]) && strlen($params[1])>0){
					$params[1]=md5($params[1]);
					$params_query[]=$params[1];
					$sql.=$sep;
					$sql.=" PASSWORD=?";
					$sep=", ";
				}
				if($users[0]['NAME']!=$params[2] && strlen($params[2])>0){
					$params_query[]=$params[2];
					$sql.=$sep;
					$sql.=" NAME=?";
					$sep=", ";
				}
				if($users[0]['SURNAME']!=$params[3]){
					$params_query[]=$params[3];
					$sql.=$sep;
					$sql.=" SURNAME=?";
					$sep=", ";
				}
				if($users[0]['PHONE']!=$params[4]){
					$params_query[]=$params[4];
					$sql.=$sep;
					$sql.=" PHONE=?";
					$sep=", ";
				}
				if($users[0]['ADDRESS']!=$params[5]){
					$params_query[]=$params[5];
					$sql.=$sep;
					$sql.=" ADDRESS=?";
					$sep=", ";
				}
				if(count($params_query)>0){
					$cond = "ID=?";
					$params_query[] = $id;
					$result=$this->command("UPDATE",$sql, "ceo_users",$cond, $params_query);
				}else{
					$result=true;
				}
			}else{
				$sql="";
				$params_query=array();
				$sql .= "ID";
				$sep=", ";
				$params_query[] =	$id;
		    		if(strlen($params[0])>0){
	    		    		$params_query[]=$params[0];
	    		    		$sql.=$sep;
					$sql.=" EMAIL";
					$sep=", ";			
				}
	    		if(strlen($params[1])>0){    		
    	    		$params_query[]=md5($params[1]);
    	    		$sql.=$sep;
					$sql.=" PASSWORD";
					$sep=", ";			
				}
				if(strlen($params[2])>0){
					$params_query[]=$params[2];
					$sql.=$sep;
					$sql.=" NAME";
					$sep=", ";
				}
				if(strlen($params[3])>0){
					$params_query[]=$params[3];
					$sql.=$sep;
					$sql.=" SURNAME";
					$sep=", ";
				}
				if(strlen($params[4])>0 ){
					$params_query[]=$params[4];
					$sql.=$sep;
					$sql.=" PHONE";
					$sep=", ";
				}
				if(strlen($params[5])>0){
					$params_query[]=$params[5];
					$sql.=$sep;
					$sql.=" ADDRESS";
					$sep=", ";
				}
				$params_query[]='1';
				$sql.=$sep;
				$sql.="STATUS";
				$result=$this->command("INSERT", $sql,"ceo_users","",$params_query);
				if($result && $insert_rol===true){
					$params_rol=array();
					$params_rol[]=uniqid();
					$params_rol[]=$id;
					$params_rol[]='2';
					$result=$this->command("INSERT", "ID,FK_USER,FK_ROL","ceo_users_rel_rols","",$params_rol);
				}else{
					$result=false;
				}
			}
		}else{
			$users=$this->getUsers($id,"",false);
			if(count($users)>0){
				$params_query=array();
				$params_query[]=$id;
				$cond="ID=?";
	    			if($users[0]['STATUS']=='1'){
	    				$result=$this->command("UPDATE","STATUS = '0' ", "ceo_users",$cond, $params_query);	
	    			}elseif ($users[0]['STATUS']=='0'){
	    				$result=$this->command("UPDATE","STATUS = '1' ", "ceo_users",$cond, $params_query);	
	    			}else{
	    				$result=false;
	    			}				    			
				//$result=$this->command("DELETE","", "ceo_users",$cond, $params_query);
			}
		}
		return $result;
	}
	/**
	 * getNews [DEPRECATED IN V.7]
	 * Devuelve todos los registros de la tabla ceo_news.
	 * @param $id string: id del usuario a buscar
	 */
	function getNews($id="",$type=""){
		$params=array();
		$cond="";
		$sep="";
		if(strlen($id)>0){
			$cond.=" ID=?";
			$params[]=$id;
			$sep=" AND ";
		}
		if(strlen($type)>0){
			$cond.=$sep;
			$cond.=" TYPE=?";
			$params[]=$type;
			$sep=" AND ";
		}
		$news=$this->query("ID, CREATED, PUB_DATE, IN_HOME, POSITION, TYPE ,LINK,IMG_1,IMG_2,VIDEO, FK_USER, STATUS","ceo_news","",$cond,"ORDER BY POSITION,CREATED",$params);
		$num_news=count($news);
		for($i=0;$i<$num_news;$i++){
			$news[$i]['TEXTS']=$this->query("ID, IDPARENT,LANG, TITLE, DESCRIPTION, DESCRIPTION_SHORT","ceo_texts","","IDPARENT=?","",array($news[$i]['ID']));
		}
		return $news;
	}
	/**
	 * saveNewData [DEPRECATED IN V.7]
	 * Guarda en base de datos un registro en la tabla ceo_news
	 * @param $id string: id del texto a comprobar o guardar
	 * @param $params string: Parámetros a guardar
	 * @param $id_user string: usuario que ha creado el registro
	 * @param $option string: opciones de comando: delete o no
	 */
	function saveNewData($id,$params,$user_id,$option){
		if($option!="delete"){
			$news=$this->getNews($id);
			if(count($news)>0){
				$sql="";
				$sep="";
				$params_query=array();
				if($news[0]['PUB_DATE']!=$params[0]){
					$params_query[]=$params[0];
					$sql.=" PUB_DATE=?";
					$sep=", ";
				}
				if($news[0]['POSITION']!=$params[1]){
					$params_query[]=$params[1];
					$sql.=$sep;
					$sql.=" POSITION=?";
					$sep=", ";
				}
				if($news[0]['TYPE']!=$params[2]){
					$params_query[]=$params[2];
					$sql.=$sep;
					$sql.=" TYPE=?";
					$sep=", ";
				}
				if($news[0]['LINK']!=$params[3]){
					$params_query[]=$params[3];
					$sql.=$sep;
					$sql.=" LINK=?";
					$sep=", ";
				}
				if($news[0]['VIDEO']!=$params[4]){
					$params_query[]=$params[4];
					$sql.=$sep;
					$sql.=" VIDEO=?";
					$sep=", ";
				}
				if($news[0]['IMG_1']!=$params[5]){
					$params_query[]=$params[5];
					$sql.=$sep;
					$sql.=" IMG_1=?";
					$sep=", ";
				}
				if($news[0]['IMG_2']!=$params[6]){
					$params_query[]=$params[6];
					$sql.=$sep;
					$sql.=" IMG_2=?";
					$sep=", ";
				}
				if($news[0]['IN_HOME']!=$params[7]){
					$params_query[]=$params[7];
					$sql.=$sep;
					$sql.=" IN_HOME=?";
					$sep=", ";
				}
				if(count($params_query)>0){
					$cond = "ID=?";
					$params_query[] =	$id;
					$result=$this->command("UPDATE",$sql, "ceo_news",$cond, $params_query);

				}else{
					$result=true;
				}
			}else{
				$sql="";
				$params_query=array();
				$sql .= "ID";
				$sep=", ";
				$sql.=$sep;
				$params_query[] =	$id;
				if(strlen($params[0])>0){
					$params_query[]=$params[0];
					$sql.=" PUB_DATE";
					$sep=", ";
				}
				if(strlen($params[1])>0){
					$params_query[]=$params[1];
					$sql.=$sep;
					$sql.=" POSITION";
					$sep=", ";
				}
				if(strlen($params[2])>0){
					$params_query[]=$params[2];
					$sql.=$sep;
					$sql.=" TYPE";
					$sep=", ";
				}
				if(strlen($params[3])>0 ){
					$params_query[]=$params[3];
					$sql.=$sep;
					$sql.=" LINK";
					$sep=", ";
				}
				if(strlen($params[4])>0){
					$params_query[]=$params[4];
					$sql.=$sep;
					$sql.=" VIDEO";
					$sep=", ";
				}
				if(strlen($params[5])>0){
					$params_query[]=$params[5];
					$sql.=$sep;
					$sql.=" IMG_1";
					$sep=", ";
				}
				if(strlen($params[6])>0){
					$params_query[]=$params[6];
					$sql.=$sep;
					$sql.=" IMG_2";
					$sep=", ";
				}
				if(strlen($params[7])>0){
					$params_query[]=$params[7];
					$sql.=$sep;
					$sql.=" IN_HOME";
					$sep=", ";
				}
				if(strlen($user_id)>0){
					$params_query[]=$user_id;
					$sql.=$sep;
					$sql.=" FK_USER";
					$sep=", ";
				}
				$params_query[]='1';
				$sql.=$sep;
				$sql.=" STATUS";
				$sep=", ";
				$result=$this->command("INSERT", $sql,"ceo_news","",$params_query);
			}
		}else{
			$news=$this->getNews($id);
			if(count($news)>0){
				$params_query=array();
				$params_query[]=$id;
				$cond="ID=?";
				$result=$this->command("DELETE","", "ceo_news",$cond, $params_query);
			}else{
				$result=false;
			}
		}
		return $result;
	}
	/**
	 * getImgs [DEPRECATED IN V.7]
	 * Devuelve todos los registros de la tabla ceo_imgs.
	 * @param $id string: id del usuario a buscar
	 */
	function getImgs($id="",$section="",$subsection="",$limit=""){
		$params=array();
		$cond="";
		$sep="";
		$order="";
		if(strlen($id)>0){
			$cond.=" ID=?";
			$params[]=$id;
			$sep=" AND ";
		}
		if(strlen($section)>0){
			$cond.=$sep;
			$cond.=" SECTION=?";
			$params[]=$section;
			$sep=" AND ";
		}
		if(strlen($subsection)>0){
			$cond.=$sep;
			$cond.=" SUBSECTION=?";
			$params[]=$subsection;
			$sep=" AND ";
		}
		if(strlen($limit)>0){
			$order=" LIMIT 0,".$limit;
		}
		$news=$this->query("ID, CREATED, PUB_DATE, POSITION, SECTION, SUBSECTION, LINK,IMG, STATUS","ceo_imgs","",$cond,"ORDER BY POSITION,CREATED".$order,$params);
		$num_news=count($news);
		for($i=0;$i<$num_news;$i++){
			$news[$i]['TEXTS']=$this->query("ID, IDPARENT,LANG, TITLE, DESCRIPTION, DESCRIPTION_SHORT","ceo_texts","","IDPARENT=?","",array($news[$i]['ID']));
		}
		return $news;
	}
	/**
	 * saveNewData [DEPRECATED IN V.7]
	 * Guarda en base de datos un registro en la tabla ceo_imgs
	 * @param $id string: id del texto a comprobar o guardar
	 * @param $params string: Parámetros a guardar
	 * @param $id_user string: usuario que ha creado el registro
	 * @param $option string: opciones de comando: delete o no
	 */
	function saveImgData($id,$params,$user_id,$option){
		if($option!="delete"){
			$news=$this->getImgs($id);
			if(count($news)>0){
				$sql="";
				$sep="";
				$params_query=array();
				if($news[0]['PUB_DATE']!=$params[0]){
					$params_query[]=$params[0];
					$sql.=" PUB_DATE=?";
					$sep=", ";
				}
				if($news[0]['POSITION']!=$params[1]){
					$params_query[]=$params[1];
					$sql.=$sep;
					$sql.=" POSITION=?";
					$sep=", ";
				}
				if($news[0]['SECTION']!=$params[2]){
					$params_query[]=$params[2];
					$sql.=$sep;
					$sql.=" SECTION=?";
					$sep=", ";
				}
				if($news[0]['SUBSECTION']!=$params[3]){
					$params_query[]=$params[3];
					$sql.=$sep;
					$sql.=" SUBSECTION=?";
					$sep=", ";
				}
				if($news[0]['IMG']!=$params[4]){
					$params_query[]=$params[4];
					$sql.=$sep;
					$sql.=" IMG=?";
					$sep=", ";
				}
				if($news[0]['LINK']!=$params[5]){
					$params_query[]=$params[5];
					$sql.=$sep;
					$sql.=" LINK=?";
					$sep=", ";
				}
				if(count($params_query)>0){
					$cond = "ID=?";
					$params_query[] =	$id;
					$result=$this->command("UPDATE",$sql, "ceo_imgs",$cond, $params_query);
	    	
				}else{
					$result=true;
				}
			}else{
				$sql="";
				$params_query=array();
				$sql .= "ID";
				$sep=", ";
				$sql.=$sep;
				$params_query[] =	$id;
				if(strlen($params[0])>0){
					$params_query[]=$params[0];
					$sql.=" PUB_DATE";
					$sep=", ";
				}
				if(strlen($params[1])>0){
					$params_query[]=$params[1];
					$sql.=$sep;
					$sql.=" POSITION";
					$sep=", ";
				}
				if(strlen($params[2])>0){
					$params_query[]=$params[2];
					$sql.=$sep;
					$sql.=" SECTION";
					$sep=", ";
				}
				if(strlen($params[3])>0 ){
					$params_query[]=$params[3];
					$sql.=$sep;
					$sql.=" SUBSECTION";
					$sep=", ";
				}
				if(strlen($params[4])>0){
					$params_query[]=$params[4];
					$sql.=$sep;
					$sql.=" IMG";
					$sep=", ";
				}
				if(strlen($params[5])>0){
					$params_query[]=$params[5];
					$sql.=$sep;
					$sql.=" LINK";
					$sep=", ";
				}
				$params_query[]='1';
				$sql.=$sep;
				$sql.=" STATUS";
				$sep=", ";
				$result=$this->command("INSERT", $sql,"ceo_imgs","",$params_query);
			}
		}else{
			$news=$this->getImgs($id);
			if(count($news)>0){
				$params_query=array();
				$params_query[]=$id;
				$cond="ID=?";
				$result=$this->command("DELETE","", "ceo_imgs",$cond, $params_query);
			}else{
				$result=false;
			}
		}
		return $result;
	}
	/** 
	 * getPages [DEPRECATED IN V.7]
	 * 
	 * @param $id string:
	 */
	function getPages($id="",$id_menu="",$pub_date=false,$search="",$type=""){
		$params=array();
		$cond="";
		$sep="";
		if(strlen($id)>0){
			$cond.=" ID=?";
			$params[]=$id;
			$sep=" AND ";
		}
		if(strlen($id_menu)>0){
			$cond.=$sep;
			$cond.=" MENU_ID=?";
			$params[]=$id_menu;
			$sep=" AND ";
		}
		if(strlen($search)>0){
			$cond.=$sep;
			$cond.=" TITLE_ES LIKE CONCAT ('%',?,'%') OR TITLE_EN LIKE CONCAT ('%',?,'%') OR SUBTITLE_ES LIKE CONCAT ('%',?,'%') OR SUBTITLE_EN LIKE CONCAT ('%',?,'%') ";
			$params[]=$type;
			$sep=" AND ";
		}			
		if(strlen($type)>0){
			$cond.=$sep;
			$cond.=" TYPE=?";
			$params[]=$type;
			$sep=" AND ";
		}		
		if($pub_date===true){
			$cond.=$sep." (PUB_DATE < CURDATE() OR PUB_DATE = '0000-00-00' )";
		}
		$news=$this->query("ID, CREATED, PUB_DATE, MENU_ID, TYPE , IMG, VIDEO, LINK, TITLE_ES,TITLE_EN, SUBTITLE_ES, SUBTITLE_EN, STATUS","ceo_pages","",$cond,"ORDER BY MENU_ID",$params);
		$num_news=count($news);
		for($i=0;$i<$num_news;$i++){
			$news[$i]['TEXTS']=$this->query("ID, IDPARENT, POSITION, LANG, TITLE, DESCRIPTION, DESCRIPTION_SHORT","ceo_texts","","IDPARENT=?","ORDER BY POSITION",array($news[$i]['ID']));
		}
		return $news;
	}
	/**
	 * saveNewData [DEPRECATED IN V.7]
	 * Guarda en base de datos un registro en la tabla ceo_news
	 * @param $id string: id del texto a comprobar o guardar
	 * @param $params string: Parámetros a guardar
	 * @param $id_user string: usuario que ha creado el registro
	 * @param $option string: opciones de comando: delete o no
	 */
	function savePage($id,$params,$option){
		if($option!="delete"){
			$pages=$this->getPages($id);
			if(count($pages)>0){
				$sql="";
				$sep="";
				$params_query=array();
				if($pages[0]['PUB_DATE']!=$params[0]){
					$params_query[]=$params[0];
					$sql.=" PUB_DATE=?";
					$sep=", ";
				}
				if($pages[0]['MENU_ID']!=$params[1]){
					$params_query[]=$params[1];
					$sql.=$sep;
					$sql.=" MENU_ID=?";
					$sep=", ";
				}
				if($pages[0]['TYPE']!=$params[2]){
					$params_query[]=$params[2];
					$sql.=$sep;
					$sql.=" TYPE=?";
					$sep=", ";
				}
				if($pages[0]['VIDEO']!=$params[3]){
					$params_query[]=$params[3];
					$sql.=$sep;
					$sql.=" VIDEO=?";
					$sep=", ";
				}
				if($pages[0]['IMG']!=$params[4]){
					$params_query[]=$params[4];
					$sql.=$sep;
					$sql.=" IMG=?";
					$sep=", ";
				}
				if($pages[0]['LINK']!=$params[5]){
					$params_query[]=$params[5];
					$sql.=$sep;
					$sql.=" LINK=?";
					$sep=", ";
				}
				if($pages[0]['TITLE_ES']!=$params[6]){
					$params_query[]=$params[6];
					$sql.=$sep;
					$sql.=" TITLE_ES=?";
					$sep=", ";
				}
				if($pages[0]['TITLE_EN']!=$params[7]){
					$params_query[]=$params[7];
					$sql.=$sep;
					$sql.=" TITLE_EN=?";
					$sep=", ";
				}
				if($pages[0]['SUBTITLE_ES']!=$params[8]){
					$params_query[]=$params[8];
					$sql.=$sep;
					$sql.=" SUBTITLE_ES=?";
					$sep=", ";
				}	
				if($pages[0]['SUBTITLE_EN']!=$params[9]){
					$params_query[]=$params[9];
					$sql.=$sep;
					$sql.=" SUBTITLE_EN=?";
					$sep=", ";
				}								
				if(count($params_query)>0){
					$cond = "ID=?";
					$params_query[] =	$id;
					$result=$this->command("UPDATE",$sql, "ceo_pages",$cond, $params_query);

				}else{
					$result=true;
				}

			}else{
				$sql="";
				$params_query=array();
				$sql .= "ID";
				$sep=", ";
				$sql.=$sep;
				$params_query[] =	$id;
				if(strlen($params[0])>0){
					$params_query[]=$params[0];
					$sql.=" PUB_DATE";
					$sep=", ";
				}
				if(strlen($params[1])>0){
					$params_query[]=$params[1];
					$sql.=$sep;
					$sql.=" MENU_ID";
					$sep=", ";
				}
				if(strlen($params[2])>0){
					$params_query[]=$params[2];
					$sql.=$sep;
					$sql.=" TYPE";
					$sep=", ";
				}
				if(strlen($params[3])>0 ){
					$params_query[]=$params[3];
					$sql.=$sep;
					$sql.=" VIDEO";
					$sep=", ";
				}
				if(strlen($params[4])>0){
					$params_query[]=$params[4];
					$sql.=$sep;
					$sql.=" IMG";
					$sep=", ";
				}
				if(strlen($params[5])>0){
					$params_query[]=$params[5];
					$sql.=$sep;
					$sql.=" LINK";
					$sep=", ";
				}
				if(strlen($params[6])>0){
					$params_query[]=$params[6];
					$sql.=$sep;
					$sql.=" TITLE_ES";
					$sep=", ";
				}
				if(strlen($params[7])>0){
					$params_query[]=$params[7];
					$sql.=$sep;
					$sql.=" TITLE_EN";
					$sep=", ";
				}
				if(strlen($params[8])>0){
					$params_query[]=$params[8];
					$sql.=$sep;
					$sql.=" SUBTITLE_ES";
					$sep=", ";
				}
				if(strlen($params[9])>0){
					$params_query[]=$params[9];
					$sql.=$sep;
					$sql.=" SUBTITLE_EN";
					$sep=", ";
				}								
				$params_query[]='1';
				$sql.=$sep;
				$sql.=" STATUS";
				$sep=", ";
				$result=$this->command("INSERT", $sql,"ceo_pages","",$params_query);
			}
		}else{
			$pages=$this->getPages($id);
			if(count($pages)>0){
				$params_query=array();
				$params_query[]=$id;
				$cond="ID=?";
				$result=$this->command("DELETE","", "ceo_pages",$cond, $params_query);
			}else{
				$result=false;
			}
		}
		return $result;
	}
	/**
	 * getRols [DEPRECATED IN V.7]
	 * devuelve los registros de la tabla ceo_rols
	 * @param $id string: id del rol a comprobar
	 */
	function getRols($id=""){
		$params=array();
		$cond="";
		if(strlen($id)>0){
			$cond.=" ID=?";
			$params[]=$id;
			$sep=" AND ";
		}
		//$cond.=$sep." AND STATUS = '1'"
		$rols=$this->query("ID, NAME_ES,NAME_EN","ceo_rols","",$cond,"ORDER BY ID",$params);
		return $rols;
	}
	/**
	 * sendUserRol [DEPRECATED IN V.7]
	 * guarda o borra los datos de rol de un usuario
	 * @param $id_user string: id del usuario a comprobar
	 * @param $id_rol string: id del rol a comprobar
	 * @param $option string: opciones de comando: delete o no
	 */
	function sendUserRol($id_user,$id_rol,$option){
		$params=array();
		if($option=="insert"){
			$params[]=uniqid();
			$params[]=$id_user;
			$params[]=$id_rol;
			$result=$this->command("INSERT", $sql,"ceo_users_rel_rol","",$params);
		}elseif($option=="delete"){
			$params[]=$id_user;
			$params[]=$id_rol;
			$cond="FK_USER=? AND FK_ROL=?";
			$result=$this->command("DELETE","", "ceo_news",$cond, $params);
		}

		return $result;
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
