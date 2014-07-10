<?
 /**
 * OlimpoBaseSystem
 * @version 2.7.1
 * @author Alberto Vara (C) Copyright 2012
 * @package gobalo.OlimpoBaseSystem
 */
/**
 * Inicializamos la clase para cálculos de tiempos e Hitos.
 * */
if (class_exists('cronos_Time')) {
	$c_time = new cronos_Time();
	$c_time->startTimeCount();
}else{
	die("[OlimpoBaseSystem::".__LINE__."]cronos_Time no se ha definido");
}
/**
 * Inicializamos la clase para envío de E-mails de errores y avisos.
 * */
if (class_exists('hermes_Mailer')) {
	$h_mail = new hermes_Mailer();
}else{
	die("[OlimpoBaseSystem::".__LINE__."]hermes_Mailer no se ha definido");
}
class OlimpoBaseSystem {
	/** *
	 * h_mail
	 * @desc Objeto donde se instancia la clase hermes_Mailer
	 */
	public $h_mail;
	/** *
	 * menu
	 * @desc array donde se guarda el menú
	 */
	protected $menu = array();	
	/**
	 * $crackers_mensaje
	 * @desc Mensaje genérico para los crackers
	 */
	public $crackers_mensaje = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
	<html xmlns=\"http://www.w3.org/1999/xhtml\">
	<head>
	<title>Crackers Go Home</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
	<body><div style=\"width:100%;text-align:100%\">¡DONT HACK MY SITE!</div></body></html>";
	/**
	 * $userHistory
	 * Array donde se guarda el historial del usuario navegante de la web
	 */
	protected $userHistory;
	/**
	 * Stores the question ID.
	 *
	 * @access private
	 * @var integer
	 */
	private $tokenTimeout = 5;

	/**
	 * Stores the new token.
	 *
	 * @access private
	 * @var string
	 */
	private $token = null;

	/**
	 * Stores the error code raised by Check method.
	 *
	 * The possible values are:
	 * 0 No error detected.
	 * 1 No Request Token detected.
	 * 2 No Session Token corrisponding to the Request Token.
	 * 3 No value for the Session Token.
	 * 4 Token reached the timeout.
	 * @access private
	 * @var integer
	 */
	private $tokenError = 0;	
	
	/**
	 * __construct
	 * @desc Constructor de la clase
	 */
	public function __construct(){
			$this->checkTime("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Construyendo la clase");
	}	
	/** *
	 * 
	 * checkTime
	 * @desc Guarda un hito en el objeto $c_time
	 * @param $text nombre del hito a guardar
	 */
	function checkTime($text){
		global $c_time;
		$ok=false;
		if(!is_object($c_time)) {
			$ok=false;
		}else{
			$ok=true;
		}
		if($ok){
			if($text==""){
				$text="[ERROR::".__LINE__."] Query";
			}
			$c_time->setTimeCount($text);
			$ok=true;
		}else{
			$ok=false;
		}  	
		return $ok;
	} 	
    function getDirnameLevel($level) { return($this->_path[$level]);}
    function getNumDirLevels() { return(count($this->_path));}
    function getDirnameFirstLevel() { return((strlen($this->_path[0]))?$this->_path[0]:".");}
    function getScriptName() { return($_SERVER['PHP_SELF']);}
    function getScriptBaseName() { return(basename($_SERVER['PHP_SELF']));}
    function getHostName() { return(basename($_SERVER['HTTP_HOST']));}
    function getHostUri(){ return(basename($_SERVER["REQUEST_URI"]));}
    function getActualUrl(){
		 $pageURL = 'http';
		 if (@$_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		 	$pageURL .= "://";
		 if ($_SERVER["SERVER_PORT"] != "80") {
		  	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		 } else {
		  	$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		 }
		 return $pageURL;   
    }
	/** *
	 * 
	 * showArray
	 * @desc Imprime en pantalla un array 
	 * @param $array variable tipo array a mostrar
	 */    
    function showArray($array){
		echo "<pre>";
		print_r($array);
		echo "</pre>";   
		return true;	
    }
	/** *
	 * 
	 * getLocalPath
	 * @desc Devuelve la ruta actual desde donde nos encontramos hasta el nombre del host
	 */      
    function getLocalPath() {
        $ret = ".";
        if($this->getNumDirLevels()>1) $ret = ereg_replace("/".$this->getDirnameFirstLevel()."/","",dirname($this->getScriptName()));
        return($ret);
    }
    /**
     * getRequest
     * @desc Verifica la integridad de la variable
     * @param string $var nombre de la variable a recoger
     */    
	function getRequest($var,$check_xss=true){
		$var_orig=$var;	
    	if($check_xss===true){
			if(!is_array($var)){
				if($this->detectXSS($var)){
					$this->alertXSS($var);
				}
			}else{
				if($this->detectXSSInArray($var)){
					$this->alertXSS($var);
				}				
			}
    	}
		if(!is_array($var) && !is_bool($var)){
			$var=utf8_decode($var);  
			$var=trim($var);
		}		
		//echo "VAR1 ".$var_orig." VAR2 ".$var."<br/>";
    	return $var;		
	}
    /**
     * getRequestVar
     * @desc Recoge la variable indicada en $name por POST o GET y verifica comprobaciones mínimas de seguridad
     * @param string $var nombre de la variable a recoger
     */
    function getRequestVar($name,$check_xss=true){		 
	    if(isset($_POST[$name])){
    		$var=$_POST[$name];
    	}elseif(isset($_GET[$name])){
    		$var=$_GET[$name];
    	}else{
    		$var=false;
    	}
    	//echo "|||||||||||| VAR1 ".$name." VAR2 ".$var."<br/>";
		return $this->getRequest($var,$check_xss);
	}
	/**
	 * getRequestSessionVar
	 * @desc Recoge la variable de sesión indicada en $name
	 * @param string $name nombre de la variable a recoger
	 */
	function getRequestSessionVar($name){
		//echo $name."<br>";
		(strlen($name)>0 && isset($_SESSION[$name]))? $var=$_SESSION[$name]:$var=false;	
		$var2=$this->getRequest($var);
		return $var2;
	}
	/**
	 * assignSessionVar
	 * @desc  Asigna la variable de sesión indicada en $name
	 * @param string $name nombre de la variable a asignar
	 * @param string $value valor a asignar a la variable
	 */
	function assignSessionVar($name,$value){
		$name=$this->getRequest($name);
		$value=$this->getRequest($value);
		if(strlen($name)>0){
			$_SESSION[$name]=$value;
			/*					
			if(is_string($value) || is_numeric($value)){
			}elseif (is_array($value)){
				foreach ($value as $key => $value){
					if(is_string($value)){
						/// FALTA POR PROGRAMAR 
					}elseif (is_array($value)){
						/// FALTA POR PROGRAMAR 
					}
				}
			}
			*/
			$result=true;
		}else{
			$result=false;
		}
		 
		return $result;
	}
	function assignSessionArray($name,$value){
		$name=$this->getRequest($name);
		$value=$this->getRequest($value);
		if(strlen($name)>0){
			$_SESSION[$name]=$value;
			/*					
			if(is_string($value) || is_numeric($value)){
			}elseif (is_array($value)){
				foreach ($value as $key => $value){
					if(is_string($value)){
						/// FALTA POR PROGRAMAR 
					}elseif (is_array($value)){
						/// FALTA POR PROGRAMAR 
					}
				}
			}
			*/
			$result=true;
		}else{
			$result=false;
		}
		 
		return $result;
	}	
	/**
	 * assignCookieVar
	 * Asigna la variable de cookie indicada en $name
	 * @param string $name nombre de la variable a asignar
	 * @param string $value valor a asignar a la variable
	 */
	function assignCookieVar($name,$value,$time=""){
		$value=$this->getRequest($value);
		$var=true;
		if(strlen($time)==0){
			$time=time() + (20 * 365 * 24 * 60 * 60);
		}
		if($value==""){
			//echo "BORRAR COOKIE: ".$name."<br>";
			setcookie ($name, "", $time);
			unset($_COOKIE[$name]);
		}elseif(strlen($name)>0 && isset($_COOKIE[$name])){
			//echo "1 ASIGNAR COOKIE: ".$name." | VALUE: ".$value." | ".$_SERVER ["HTTP_HOST"]."<br>";
			if(setcookie($name,$value,$time)){
				$var=($_COOKIE[$name]);
				//$this->showArray($_COOKIE);
				//echo "class COOKIE: ".$var."<br>";			
			}else{
				//echo "ERROR";	
				$var=false;
			}

		}elseif(strlen($name)>0){
			//echo "2 ASIGNAR COOKIE: ".$name."<br>";
			setcookie($name,$value, $time);
		}else{
			//echo "3ERRR<br>";
			$var=false;
		}
		return $var;
	}
	/**
	 * getRequestCookieVar
	 * Recoge la variable de cookie indicada en $name
	 * @param string $name nombre de la variable a recoger
	 */
	function getRequestCookieVar($name){
		$name=$this->getRequest($name);
		if(strlen($name)>0 && isset($_COOKIE[$name])){
			//echo "GET COOKIE: ".$name."<br>";
			$var=($_COOKIE[$name]);
			//echo "COOKIE: ".$var."<br>";
		}else{
			$var=false;
		}		
		return $var;
	}	
	/**
	 * createUniqCookie
	 * Crea una cookie codificada y única
	 * @param string $name nombre de la variable a asignar
	 * @param string $value valor a asignar a la variable
	 */
	function createUniqCookie($type='sesion'){
		$y=1113199932723;
		$x = $y +1;
		$hass=(pow($x,3)-pow($y,3))/($x-$y);
		/* LO MULTIPLICAMOS POR LA HORA EN MICROSEGUNDOS PARA GARANTIZAR QUE ES UNICO */
		$aux=mktime();
		if($type=='sesion'){
			$this->assignSessionVar('asistente_login_cookie',$hass*$aux);
		}else{
			$this->assignCookieVar('asistente_login_cookie',$hass*$aux);
		}
		
	}
	/**
	 * checkUniqCookie
	 * Crea una cookie codificada y única
	 * @param string $name nombre de la variable a asignar
	 * @param string $value valor a asignar a la variable
	 */
	function checkUniqCookie($var_check="",$type='sesion'){
		if(strlen($var_check)==0){
			if($type=='sesion'){
				$var_check=$this->getRequestSessionVar('asistente_login_cookie');
			}else{
				$var_check=$this->getRequestCookieVar('asistente_login_cookie');
			}		
			
		}
		if(strlen($var_check)>0){
			$y=1113199932723;
			$x = $y +1;
			$hass=(pow($x,3)-pow($y,3))/($x-$y);
			$verify=($var_check/$hass);
			$aux=mktime(0, 0, 0, 9, 2, 2012);
			if(($verify)-mktime()<=0 && $verify>=$aux){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}		
	/**
	 * clearTitleToUrl
	 * Quita caracteres para dejar la url amigable "bonita"
	 * @param string formatDateToSQL
	 */
	function clearTitleToUrl($url) {
		$url=mb_strtolower($url,'UTF-8'); 
		$url=str_replace(" ", "_", $url);
		$url=str_replace("á", "a", $url);
		$url=str_replace("&aacute;", "a", $url);
		$url=str_replace("é", "e", $url);
		$url=str_replace("&eacute;", "e", $url);
		$url=str_replace("í", "i", $url);
		$url=str_replace("&iacute;", "i", $url);
		$url=str_replace("ó", "o", $url);
		$url=str_replace("&oacute;", "o", $url);
		$url=str_replace("ú", "u", $url);
		$url=str_replace("&uacute;", "e", $url);
		$url=str_replace("ñ", "n", $url);
		$url=str_replace("&ntilde;", "n", $url);
		$url=str_replace("º", "o", $url);
		$url=str_replace("ª", "a", $url);
		$url=str_replace("?", "", $url);
		$url=str_replace("¿", "", $url);
		$url=str_replace("@", "", $url);
		$url=str_replace("?", "", $url);
		$url=str_replace("$", "", $url);
		$url=str_replace("/", "", $url);
		$url=str_replace(".", "_", $url);
		$url=str_replace(",", "_", $url);
		$url=str_replace("-", "_", $url);
		$url=str_replace("\\", "", $url);
		$url=preg_replace("([^0-9A-Za-z_]+)", "", $url);
		return $url;
	}
	function clearOnlyChars($string) {
		$string=strtolower($string);
		$string=str_replace(" ", "_", $string);
		$string=preg_replace("([^0-9A-Za-z_]+)", "", $string);
		return $string;
	}
	function noAcents($url) {
		$url=mb_strtolower($url,'UTF-8'); 
		$url=str_replace("º", "o", $url);
		$url=str_replace("ª", "a", $url);
		$url=str_replace("?", "", $url);
		$url=str_replace("¿", "", $url);
		$url=str_replace("@", "", $url);
		$url=str_replace("?", "", $url);
		$url=str_replace("$", "", $url);
		$url=str_replace("/", "", $url);		
		$url=str_replace("á", "a", $url);
		$url=str_replace("&aacute;", "a", $url);
		$url=str_replace("é", "e", $url);
		$url=str_replace("&eacute;", "e", $url);
		$url=str_replace("í", "i", $url);
		$url=str_replace("&iacute;", "i", $url);
		$url=str_replace("ó", "o", $url);
		$url=str_replace("&oacute;", "o", $url);
		$url=str_replace("ú", "u", $url);
		$url=str_replace("&uacute;", "e", $url);
		$url=str_replace("ñ", "n", $url);
		$url=str_replace("&ntilde;", "n", $url);

		return $url;
	}
	function getFileExt($filename)
	{
	    return end(explode(".", $filename));
	}	
	/**
	 * getInfoLoc
	 * Da información del directorio actual
	 * @param string formatDateToSQL
	 */
	function getInfoLoc() {
		echo "<pre>";
		echo getcwd()."<br>";
		echo "</pre>";
	}
	/**
	 * formatDateToSQL
	 * Da formato a una fecha para insertarla en SQL
	 * @param string formatDateToSQL
	 */
	function formatDateToSQL($date) {
		$f_dia=substr($date, 0,2);
		$f_mes=substr($date, 3,2);
		$f_anio=substr($date, 6,4);
		$fecha_formated=$f_anio."-".$f_mes."-".$f_dia;
		return $fecha_formated;
	}
	function formatDateToTPL($date) {
		$f_dia=substr($date, 8,2);
		$f_mes=substr($date, 5,2);
		$f_anio=substr($date, 0,4);
		$fecha_formated=$f_dia."/".$f_mes."/".$f_anio;
		return $fecha_formated;
	}
	function formatDateToTPL_beauty($date,$s_dayWeek=true,$s_monthText=true,$s_year=true,$sep=" de ",$lang='es'){
		//echo "<br>fecha:".$date."<br>";
		$lang=strtolower($lang);
		$dia = date('j', strtotime($date));
		$date_beautifull = date('d', strtotime($date));
		//echo "<br>Día:".$date_beautifull;
		$dia_semana_num = date('N', strtotime($date));
		$dia_mes_num = date('n', strtotime($date));
		if($s_year===true){
			$anio_num = $sep.date('Y', strtotime($date));
		}else{
			$anio_num = "";
		}
		
		//echo "<br>día de la semana".$date_beautifull."<br>";
		if($s_dayWeek===true){
			switch ($dia_semana_num) {
			    case 1:
			    	if($lang=='es'){
			    		$dia_semana_text="Lunes";
			    	}elseif($lang=='pt'){
			    		$dia_semana_text="Segunda-feira";
			    	}else{
			    		$dia_semana_text="Monday";
			    	}
			        break;
			    case 2:
			        if($lang=='es'){
			        	$dia_semana_text="Martes";
			        }elseif($lang=='pt'){
			    		$dia_semana_text="Terça-feira";
			    	}else{
			        	$dia_semana_text="Tuesday";
			        }			        
			        break;
			    case 3:
			    	if($lang=='es'){
			    		$dia_semana_text="Miércoles";
			    	}elseif($lang=='pt'){
			    		$dia_semana_text="Quarta-feira";
			    	}else{
			    		$dia_semana_text="Wednesday";
			    	}			    	
			        break;
			    case 4:
			        if($lang=='es'){
			        	$dia_semana_text="Jueves";
			        }elseif($lang=='pt'){
			    		$dia_semana_text="Quinta-feira";
			    	}else{
			        	$dia_semana_text="Thursday";
			        }			        
			        break;
			    case 5:
			        if($lang=='es'){
			        	$dia_semana_text="Viernes";
			        }elseif($lang=='pt'){
			    		$dia_semana_text="Sexta-feira";
			    	}else{
			        	$dia_semana_text="Friday";
			        }			        
			        break;
			    case 6:
			    	if($lang=='es'){
			    		$dia_semana_text="Sábado";
			    	}elseif($lang=='pt'){
			    		$dia_semana_text="Saturday";
			    	}else{
			    		$dia_semana_text="Saturday";
			    	}			    	
			        break;
			    case 7:
					if($lang=='es'){
			    		$dia_semana_text="Domingo";
			    	}elseif($lang=='pt'){
			    		$dia_semana_text="Sunday";
			    	}else{
			    		$dia_semana_text="Sunday";
			    	}		
			        break;		        		        		        		        
			}		
		}else{
			$dia_semana_text="";
		}
		if($s_monthText===true){
			switch ($dia_mes_num) {
			    case 1:
			    	if($lang=='es'){
			    		$dia_mes_text="Enero";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="janeiro";
			    	}else{
			    		$dia_mes_text="January";
			    	}			    	
			        break;
			    case 2:
					if($lang=='es'){
			    		$dia_mes_text="Febrero";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="fevereiro";
			    	}else{
			    		$dia_mes_text="February";
			    	}
			        break;
			    case 3:
					if($lang=='es'){
			    		$dia_mes_text="Marzo";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="março";
			    	}else{
			    		$dia_mes_text="March";
			    	}
			        break;
			    case 4:
					if($lang=='es'){
			    		$dia_mes_text="Abril";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="abril";
			    	}else{
			    		$dia_mes_text="April";
			    	}
			        break;
			    case 5:
					if($lang=='es'){
			    		$dia_mes_text="Mayo";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="maio";
			    	}else{
			    		$dia_mes_text="May";
			    	}
			        break;
			    case 6:
			    	if($lang=='es'){
			    		$dia_mes_text="Junio";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="junho";
			    	}else{
			    		$dia_mes_text="June";
			    	}			    	
			        break;
			    case 7:
			    	if($lang=='es'){
			    		$dia_mes_text="Julio";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="julho";
			    	}else{
			    		$dia_mes_text="July";
			    	}			    	
			        break;
			    case 8:
			    	if($lang=='es'){
			    		$dia_mes_text="Agosto";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="agosto";
			    	}else{
			    		$dia_mes_text="August";
			    	}			    	
			        break;		
			    case 9:
			    	if($lang=='es'){
			    		$dia_mes_text="Septiembre";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="setembro";
			    	}else{
			    		$dia_mes_text="September";
			    	}			    	
			        break;		
			    case 10:
			    	if($lang=='es'){
			    		$dia_mes_text="Octubre";
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="outubro";
			    	}else{
			    		$dia_mes_text="October";
			    	}			    	
			        break;		
			    case 11:
			    	if($lang=='es'){
			    		$dia_mes_text="Noviembre";
			    		
			    	}elseif($lang=='pt'){
			    		$dia_mes_text="novembro";
			    	}else{
			    		$dia_mes_text="November";
			    	}			    	
			        break;		
			    case 12:
				    if($lang=='es'){
				    	$dia_mes_text="Diciembre";
				    }elseif($lang=='pt'){
			    		$dia_mes_text="dezembro";
			    	}else{
				    	$dia_mes_text="December";
				    }			    
			        break;				        
			}			
		}else{
			$dia_mes_text="";
		}
		if($lang=='es'){
			return 	$dia_semana_text." ".$dia.$sep.$dia_mes_text.$anio_num;
		}elseif($lang=='pt'){
			return 	$dia_semana_text." ".$dia.$sep.$dia_mes_text.$anio_num;
		}else{
			return 	$dia_semana_text.", ".$dia_mes_text." ".$dia.$anio_num;
		}
		
	}
	function formatTextAreaToTPL($text) {
		$text=str_replace("\n", "<br>",$text);
		return $text;
	}
	function formatTextAreaToInput($text) {
		$text=str_replace("<br>", "\n",$text);
		return $text;
	}			
	function sumDate($fecha,$ndias) {
      if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/([0-9][0-9]){1,2}/",$fecha))
        list($dia,$mes,$año)= preg_split( '/[-\.\/ ]/', $fecha );
      if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-([0-9][0-9]){1,2}/",$fecha))
        list($dia,$mes,$año)= preg_split( '/[-\.\/ ]/', $fecha );
        $nueva = mktime(0,0,0, $mes,$dia,$año) + $ndias * 24 * 60 * 60;
        $nuevafecha=date("d/m/Y",$nueva);  
      return ($nuevafecha);        
	}		
	function formatNumber($number) {
		$number=str_replace(",", ".", $number);
		if(!is_numeric($number))$number=0;
		else $number=number_format($number, 2, '.', ''); 
		//echo $number;
		return $number;
	}
	function formatNumbertoTPL($number,$num_dec=2) {
		if(!is_numeric($number))$number='0';
		else{
			$number=number_format($number, $num_dec, ',', '.'); 
		}
		return $number;
	}	
	function cutText($texto, $longitud = 180,$force=false) {
		if((mb_strlen($texto) > $longitud)) {
			if($force===false){
				$pos_espacios = mb_strpos($texto, ' ', $longitud) - 1;
			    if($pos_espacios > 0) {
			        $caracteres = count_chars(mb_substr($texto, 0, ($pos_espacios + 1)), 1);
			        if (@$caracteres[ord('<')] > @$caracteres[ord('>')]) {
			            $pos_espacios = mb_strpos($texto, ">", $pos_espacios) - 1;
			        }
			        $texto = mb_substr($texto, 0, ($pos_espacios + 1)).'...';
			    }	
				if(preg_match_all("|(<([\w]+)[^>]*>)|", $texto, $buffer)) {
			        if(!empty($buffer[1])) {
			            preg_match_all("|</([a-zA-Z]+)>|", $texto, $buffer2);
			            if(count($buffer[2]) != count($buffer2[1])) {
			                $cierrotags = array_diff($buffer[2], $buffer2[1]);
			                $cierrotags = array_reverse($cierrotags);
			                foreach($cierrotags as $tag) {
			                        $texto .= '</'.$tag.'>';
			                }
			            }
			        }
			    }			    			
			}elseif($force===true){
				 $texto = mb_substr($texto, 0, $longitud).'...';
			}

		}
		return $texto;
	}
    /**
     * getPagination
     * Devuelve un array paginado
     * @param $array_old array: array de elementos a paginar
     * @param $pag_show int: página a mostrar 
     * @param $num_elements_page int: número de elementos por página
     * @param $num_block_pages int: número de bloques de páginas a mostrar: Ejemplo: Si es 3, Mostraría al lado de siguiente y anterior páginas: <<atras 1,2,3 siguiente>>. Si fuese 5, mostraría paginas: <<atras 5,6,7,8,9 siguiente>>
     */    
    function getPagination($array_old,$pag_show,$num_elements_page=5,$num_block_pages=1){
		$pagination=array();
		$array_new=array();
		$total_block=($num_block_pages*2);
		/* Comprobar si se pasa un array */
		if(is_array($array_old)){
			/* Comprobar página a mostrar */
	    	if(!is_numeric($pag_show)) $pag_show = 0;
	    	if($pag_show < 0) $pag_show = 0;
	    	
	    	/* Asignar variables */
	    	$pagination['num_elements']=count($array_old);
			/* Calcular número de páginas */
	    	$pagination['num_pages'] = ceil($pagination['num_elements'] / $num_elements_page);

	    	if($pag_show >= $pagination['num_pages']) $pag_show = $pagination['num_pages']-1;
	    	
			$pagination['actual_page']=$pag_show;
			$pagination['prev_page']=(($pag_show-1)>=0)? $pag_show-1 : '';
			$pagination['next_page']=(($pag_show+1)<$pagination['num_pages'])? $pag_show+1 : '';	
			

	    	if(is_numeric($num_block_pages)){
	    		$num_blocks_next=$num_block_pages;
	    		$num_blocks_prev=$num_block_pages;
	    		
	    		$pag_show_aux=$pag_show+1;
	    		if(($pag_show_aux+$num_blocks_next)>$pagination['num_pages']){
	    			$max_blocks_next=$num_blocks_next-(($pag_show_aux+$num_blocks_next)-$pagination['num_pages']);	
	    		}else{
	    			$max_blocks_next=$num_blocks_next;
	    		}
	    		
	    		if(($pag_show_aux-$num_blocks_prev)<=0){
	    			$max_blocks_prev=$num_blocks_prev-($num_blocks_prev-$pag_show_aux+1);
	    			//echo "Y: ".$max_blocks_prev." = x: ".$num_blocks_prev." - c: ".$pag_show_aux."<br>";
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
	    		//echo "TOTAL BLOXS NEXT: ".$total_block."<br>";
	    		//echo "NUM_BLOCKS_NEXT: ".$num_blocks_next." MAX_BLOCKS_NEXT: ".$max_blocks_next." || MAX_BLOCKS_PREV: ".$max_blocks_prev." NUM_BLOCKS_PREV: ".$num_blocks_prev."<br>";	
	    		$j=0;
	    		for($i=$pag_show+1;$j<$max_blocks_next && $i<$pagination['num_pages'];$i++){
	    			$pagination['blocks_next'][]=$i;
	    			//echo "2COUNT: ".$j."<br>";
	    			$j++;
	    		}
	    		$j=0;
	    		for($i=$pag_show-1;$j<$max_blocks_prev && $i>=0;$i--){

	    			//echo "3COUNT: ".$j."<br>";
	    			$pagination['blocks_prev'][]=$i;
	    			$j++;
	    		}	
	    		if(@is_array($pagination['blocks_prev'])) sort($pagination['blocks_prev']);
	    		//echo "NUM_BLOCKS_NEXT 3: ".$num_blocks_next." MAX_BLOCKS_NEXT: ".$max_blocks_next." MAX_BLOCKS_PREV: ".$max_blocks_prev." NUM_BLOCKS_PREV: ".$num_blocks_prev."<br>";  		    		
	    	}
			/* Sacar del array los elementos que nos interesan */
	    	$ini = 	($pag_show * $num_elements_page);
	    	$end = $ini + $num_elements_page - 1;	
	    	$num_tot=0;	
	    	for($i=0;$i < $pagination['num_elements'];$i++){
					$num_tot ++;
					if($i>=$ini && $i<=$end){
						$array_new[]=$array_old[$i];
					}
	    	}
	    	$pagination['elements']=$array_new;
	    	unset($array_new);   
	    	unset($array_old);    		
		}
		return $pagination;
    }	
	/**
	* errorQuery
	*       
	*/         
	function sendError($errorText="",$url="",$title=""){
		if(!is_object($this->h_mail)) {
			$this->h_mail=new hermes_Mailer();
		}else{
			die("[OlimpoBaseSystem::".__LINE__."] hermes_Mailer no se ha definido");
		}
		$result=false;
		if($url==""){
			$url=WEB_URL;
		}
		$result=$this->h_mail->sendMailError($errorText,$url);
		//unset($this->h_mail);
		return $result;
	}
	/**
	 * checkPerms
	 *
	 * Verifica los permisos de lectura y escritura de un fichero antes de ser cargado
	 * @param unknown_type $file
	 * @param unknown_type $type
	 */
	function checkPerms($file,$type){
		if(is_file($file)){
			if($type=='numeric'){
				$info=intval(substr(sprintf('%o', fileperms($file)), -3));
			}else{
				$perms = fileperms($file);
				if (($perms & 0xC000) == 0xC000) {
					// Socket
					$info = 's';
				} elseif (($perms & 0xA000) == 0xA000) {
					// Symbolic Link
					$info = 'l';
				} elseif (($perms & 0x8000) == 0x8000) {
					// Regular
					$info = '-';
				} elseif (($perms & 0x6000) == 0x6000) {
					// Block special
					$info = 'b';
				} elseif (($perms & 0x4000) == 0x4000) {
					// Directory
					$info = 'd';
				} elseif (($perms & 0x2000) == 0x2000) {
					// Character special
					$info = 'c';
				} elseif (($perms & 0x1000) == 0x1000) {
					// FIFO pipe
					$info = 'p';
				} else {
					// Unknown
					$info = 'u';
				}

				// Owner
				$info .= (($perms & 0x0100) ? 'r' : '-');
				$info .= (($perms & 0x0080) ? 'w' : '-');
				$info .= (($perms & 0x0040) ?
				(($perms & 0x0800) ? 's' : 'x' ) :
				(($perms & 0x0800) ? 'S' : '-'));

				// Group
				$info .= (($perms & 0x0020) ? 'r' : '-');
				$info .= (($perms & 0x0010) ? 'w' : '-');
				$info .= (($perms & 0x0008) ?
				(($perms & 0x0400) ? 's' : 'x' ) :
				(($perms & 0x0400) ? 'S' : '-'));

				// World
				$info .= (($perms & 0x0004) ? 'r' : '-');
				$info .= (($perms & 0x0002) ? 'w' : '-');
				$info .= (($perms & 0x0001) ?
				(($perms & 0x0200) ? 't' : 'x' ) :
				(($perms & 0x0200) ? 'T' : '-'));
			}
		}
		return $info;
	}
	/**
	 * saveUserHistory
	 *
	 * Guarda las webs visitadas por un usuario
	 */
	function saveUserHistory(){
		$id=$this->getRequestSessionVar('user_id');
		$history=$this->getRequestSessionVar('user_history');
		if(strlen($id)>0){
			$next=$this->getNumUserHistoryPages($id);	
			if(@$history[$id][$next-1]['NAME']!=$this->getActualUrl()){
				$history[$id][$next]['NAME']=$this->getActualUrl();
				$history[$id][$next]['DATE']=date("H:i:s d/m/Y");  
				$history[$id][$next]['IP']=$_SERVER ["REMOTE_ADDR"]; 
			}
	
			/*
			 echo "<pre>";
			 print_r($history);
			 echo "</pre>";
			 */
			$this->assignSessionVar('user_history', $history);			
		}
		return true;
	}
	/**
	 * getNumUserHistoryPages
	 * 
	 * Guarda las webs visitadas por un usuario
	 */
	function getNumUserHistoryPages($user_id){
		$history=$this->getRequestSessionVar('user_history');
		$num_pages=0;
		if(@is_array($history[$user_id])) $num_pages=count($history[$user_id]);
		if(strlen($num_pages)==0) $num_pages=0;
		return $num_pages;
	}	
	/**
	 * getUserHistory
	 * 
	 * Devuelve las últimas visitas del usuario
	 */
	function getUserHistory($format="array"){ 
		$id=$this->getRequestSessionVar('user_id');
		$history=$this->getRequestSessionVar('user_history');
		if($format=="array"){
			if(is_array($history[$id])){
			$result = array_reverse($history[$id]);
			}else{
				$result = false;
			}
		}elseif ($format=="print"){
			if(count($history[$id])>1){
				if(is_array($history[$id])){
					$history_user = array_reverse($history[$id]);
					$num_results = count($history_user);
					$result = "<pre>";
					for($i=0;$i<$num_results;$i++){
						$result .= "<p>* [".($i+1)."] [".$history_user[$i]['DATE']."]<b>USUARIO</b> ".$id." / ".$history_user[$i]['IP']." || <b>PAGINA: </b> ".$history_user[$i]['NAME']." </p>";
					}
					$result .= "</pre>";
				}				
			}else{
				$result = false;
			}
				
		}else{
			$result = false;
		}
		return $result;
	}
	/**
	 * checkVarSecurity
	 *
	 * Verifica que no se manda nada raro por la URL, variables de sesión, coockies.....
	 * @param unknown_type $url
	 */
	function checkVarSecurity($var){
		if(is_string($var)){
			$check=strtolower($var);
			if(strpos($check,"<script>")!==false ||
			strpos($check,"</script>")!==false ||
			strpos($check,"<?php")!==false ||
			strpos($check,"sendmailerrorharck")!==false ||
			strpos($check,"alert(")!==false){
				$result=$this->sendError("[HACK] Intento de hackeo por con: ".urlencode($var),WEB_URL,"Intento de Hack");
				die($this->crackers_mensaje);
			}
		}elseif(is_string($var)){
			/* FALTA POR PROGRAMAR */
		}

	}
		/**
	* A utility function to manage nested array structures, checking
	* each value for possible XSS. Function returns boolean if XSS is
	* found.
	*
	* @param array $array
	* An array of data to check, this can be nested arrays.
	* @return boolean
	* True if XSS is detected, false otherwise
	*/
	public static function detectXSSInArray(array $array) {
		foreach($array as $value) {
			if(is_array($value)) {
				return self::detectXSSInArray($value);
			}
			else {
				if(self::detectXSS($value) === TRUE) return TRUE;
			}
		}
		return FALSE;
	}	
	/**
	* Given a string, this function will determine if it potentially an
	* XSS attack and return boolean.
	*
	* @param string $string
	* The string to run XSS detection logic on
	* @return boolean
	* True if the given `$string` contains XSS, false otherwise.
	*/
	public static function detectXSS($string) {
		$contains_xss = FALSE;
		// Skip any null or non string values
		if(is_null($string) || !is_string($string)) {
			return $contains_xss;
		}
		// Keep a copy of the original string before cleaning up
		$orig = $string;
		// URL decode
		$string = urldecode($string);
		// Convert Hexadecimals
		$string = preg_replace('!(&#|\\\)[xX]([0-9a-fA-F]+);?!e','chr(hexdec("$2"))', $string);
		// Clean up entities
		$string = preg_replace('!(&#0+[0-9]+)!','$1;',$string);
		// Decode entities
		$string = html_entity_decode($string, ENT_NOQUOTES, 'UTF-8');
		// Strip whitespace characters
		$string = preg_replace('!\s!','',$string);
		// Set the patterns we'll test against
		$patterns = array(
		// Match any attribute starting with "on" or xmlns
		'#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>?#iUu',
		// Match javascript:, livescript:, vbscript: and mocha: protocols
		'!((java|live|vb)script|mocha):(\w)*!iUu',
		'#-moz-binding[\x00-\x20]*:#u',
		// Match style attributes
		'#(<[^>]+[\x00-\x20\"\'\/])style=[^>]*>?#iUu',
		// Match unneeded tags
		'#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>?#i'
		);
		foreach($patterns as $pattern) {
			// Test both the original string and clean string
			if(preg_match($pattern, $string) || preg_match($pattern, $orig)){
				$contains_xss = TRUE;
			}
			if ($contains_xss === TRUE) return TRUE;
		}
		return FALSE;
	}		
	/**
	 * removeXSS
	 *
	 * Elimina los posibles ataques XSS
	 */	
	function alertXSS($val){
		if(!is_array($val)){
			$result=$this->sendError("[HACK] Intento de hackeo por con: ".($val),WEB_URL,"Intento de Hack");
		}else{
			$result=$this->sendError("[HACK] Intento de hackeo por con: ".(print_r($val,true)),WEB_URL,"Intento de Hack");
		}
		
		die($this->crackers_mensaje);
	}
	/**
	 * clientIsMovile
	 * Detecta si el cliente que entra en la web es a través de un movil
	 * @param $section: si se pone a false, devuelve siempre false
	 */
	function clientIsMovile($section=true){
		/* DETECTAR SI SE NAVEGA DESDE MOVIL */
		//echo "HTTP_ACCEPT: ".$_SERVER['HTTP_USER_AGENT'];
		$mobile_browser=0;
	   	if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|android|midp|wap|phone)/i',strtolower(@$_SERVER['HTTP_USER_AGENT']))){
	    	$mobile_browser++;
	    }
	
	    //$_SERVER['HTTP_ACCEPT'] -> Indica los tipos MIME que el cliente puede recibir.
	    if((strpos(strtolower(@$_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml')>0) || ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))){
	    	$mobile_browser++;
	    }
	
	    if(strstr(@$_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr(@$_SERVER['HTTP_USER_AGENT'],'iPod')){
	    	//echo "ENTRA";
	    	$mobile_browser++;
	    }
	
	
	    $mobile_ua = strtolower(substr(@$_SERVER['HTTP_USER_AGENT'],0,4));
	    $mobile_agents = array(
	    'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
	    'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
	    'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
	    'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
	    'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
	    'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
	    'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
	    'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
	    'wapr','webc','winw','winw','xda','xda-');
	
	    //buscar agentes en el array de agentes
	    if(in_array($mobile_ua,$mobile_agents)){
	    $mobile_browser++;
	    }
	
	    //$_SERVER['ALL_HTTP'] -> Todas las cabeceras HTTP
	    //strpos -> Primera aparicion de una cadena dentro de otra
	    if(strpos(strtolower(@$_SERVER['ALL_HTTP']),'OperaMini')>0) {
	    $mobile_browser++;
	    }
	    if(strpos(strtolower(@$_SERVER['HTTP_USER_AGENT']),'windows')>0) {
	    $mobile_browser=0;
	    }
		if($section==="no_movil"){
			    return false;
		}else{
			if($mobile_browser>0){
				
			  return true;
		    }else{
			   return false;
		    }		
		}
	}
	/**
	 * showFacebookMTags
	 * Como las etiquetas de Facebook joden las validaciones de W3C. Se detecta cuando entra el robot de Cara Libro 
	 */	
	function showFacebookMTags(){
		if(!(stristr(@$_SERVER["HTTP_USER_AGENT"],'facebook') === false)){
			return true;
		}else{
			return false;	
		}			
	}
	public function loadMenu(){
		if(is_file(ROOT_DIR.DIR_WEB."conf/menu.php")) {
			include(ROOT_DIR.DIR_WEB."conf/menu.php");
			//print_r($menu);
			$this->menu=$menu['pag'];
			
			unset($menu);
			
		}else{
			$this->sendError("[OLIMPO_BASE] No se encuentra el menu: ".ROOT_DIR.DIR_WEB."conf/menu.php",WEB_URL,"Error de Menú");
			die("[OlimpoBaseSystem::".__LINE__."] No se encuentra el menu: ".ROOT_DIR.DIR_WEB."conf/menu.php");		
		}

	}
	public function getMenu(){	
		return $this->menu;
	}
	function addMenuElements($new_elements){
		$this->menu = array_merge((array)$this->menu, (array)$new_elements);
		return true;
	}
	/**
	 * Generates tokens. Generation tecnique taken from {@link http://www.playhack.net Seride}.
	 * 
	 * @access private
	 */
	private function tokenGen() {
		// Hashes a randomized UniqId.
		$hash = sha1(uniqid(rand(), true));
		// Selects a random number between 1 and 32 (40-8)
		$n = rand(1, 32);
		// Generate the token retrieving a part of the hash starting from the random N number with 8 of lenght
		$token = substr($hash, $n, 8);
		return $token;
	}
	/**
	 * Sets token.
	 *
	 * @access private
	 */
	function tokenSet($timeout=5) {
		if(is_numeric($timeout))$this->tokenTimeout = $timeout;
		$this->token = $this->tokenGen();
		$_SESSION["OS_".$this->token] = time();
	}
	/**
	 * Checks if the request have the right token set; after that, destroy the old tokens. Returns true if the request is ok, otherwise returns false.
	 */
	function tokenCheck($token) {
		// Check if the token has been sent.
		//echo "1-ENTRA! ".$token."<br/>";
		//$this->showArray($_SESSION);
		if(isset($token)) {
			//echo "2-ENTRA! ".$token."<br/>";
			// Check if the token exists
			if(isset($_SESSION["OS_".$token])) {
				//echo "3-ENTRA! ".$token."<br/>";
				// Check if the token isn't empty
				if(isset($_SESSION["OS_".$token])) {
					$age = time()-$_SESSION["OS_".$token];
					//echo "CRADA: ".$_SESSION["OS_".$token]."<br>";
					//echo "AHORA: ".time()."<br>";
					//echo "AGE: ".$age."<br>";
					//echo "AGE: ".($this->tokenTimeout*60*60)."<br>";
					// Check if the token did not timeout
					if($age > $this->tokenTimeout*60*60) $this->tokenError = 4;
				}else $this->tokenError = 3;
			}else $this->tokenError = 2;
		}else $this->tokenError = 1;
		// Anyway, destroys the old token.
		$this->tokenDelAll();
		return $this->tokenError;
	}	
	/**
	 * Sets a token to protect a form. In fact, it prints a hidden field with the token.
	 */
	function tokenProtectForm() {
		return "<input type=\"hidden\" id =\"OS_TOKEN\" name=\"OS_TOKEN\" value=\"".$this->token."\" />";
	}	
	/**
	 * Destroys all tokens except the new token.
	 */
	function tokenDelAll() {
		$sessvars = array_keys($_SESSION);
		$tokens = array();
		foreach ($sessvars as $var) if(substr_compare($var,"OS_",0,3)==0) $tokens[]=$var;
		unset($tokens[array_search("OS_".$this->token,$tokens)]);
		foreach ($tokens as $token) unset($_SESSION[$token]);
	}		
}

?>