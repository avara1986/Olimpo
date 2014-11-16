<?php
/*
 * (C) Copyright 2011 Hermes_Mailer
 *		Alberto Vara Navarrete
 *		a.vara.1986@gmail.com
 *		Readaptación de la función sendmail.php de Gerard
 */

class hermes_Mailer
{
	/** *
	* h_mail
	* Objeto donde se instancia la clase phpMailer
	*/
	private $h_mail;
	/**
	* __construct
	* Constructor de la clase
	*/
	public function __construct(){
		if(is_file(ROOT_DIR_CONF.'modulos/PHPMailer/class.phpmailer.php')){
			require_once (ROOT_DIR_CONF.'modulos/PHPMailer/class.phpmailer.php');
		}else{
			die("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] No se puede crear el objeto");
		}
		
		if(@!defined(MAILER_HOST))@define("MAILER_HOST","smtp.DOMAIN.com");
		if(@!defined(MAILER_PORT))@define("MAILER_PORT",25);
		if(@!defined(MAILER_USER))@define("MAILER_USER","test@DOMAIN.com");
		if(@!defined(MAILER_PASS))@define("MAILER_PASS","******");
		
		$this->h_mail = new phpmailer();
		$this->h_mail->PluginDir = ROOT_DIR_CONF."modulos/PHPMailer/";
		$this->h_mail->Host     = MAILER_HOST;
		$this->h_mail->Port		= MAILER_PORT;
		$this->h_mail->Username = MAILER_USER;
		$this->h_mail->Password = MAILER_PASS;
		$this->h_mail->SMTPAuth = true;  // authentication enabled
		//$this->h_mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
		$this->h_mail->Timeout = 50;
		$this->h_mail->Mailer = "smtp";
		$this->h_mail->IsSMTP();
		$this->h_mail->IsHTML(true);
		$this->h_mail->CharSet = "UTF-8";
		$this->h_mail->SMTPDebug = 1;
		$this->checkTimeMailer("[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Construytendo la clase");
	}
	/**
	 * function constructMail
	 * Params:
	 * $from: email del remitente
	 * $fromname: nombre del remitente
	 * $subject: titulo del mensaje
	 * $html: contenido del mail
	 * $plain_text: texto plano: suele ser el aviso legal
	 * $reply: contestar a:
		*
		**/
	public function constructMail($from, $fromname, $subject, $html, $plain_text, $reply,$default=false){
		//Si no se ha enviado ningún cuerpo, se incluye uno por defecto
		$body="";
		$html=$this->stringToMail($html);
		//$subject=$this->stringToMail($subject);
		if($default==true){
			$body = '<html>
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			</head>
			<table width="676" border="0" align="center" cellpadding="0" cellspacing="0">
			  <tr>
			    <td>&nbsp;<img src="http://www.gobalo.es/phpmailer/mail.jpg" alt="Gobalo using inspiration"/></td>
			  </tr>
			  </table>';			
		}
		$body.=$html;
		if($default==true){
			$body.='<table width="676" border="0" align="center" cellpadding="0" cellspacing="0">
			  <tr>
			    <td>&nbsp;
			    <p style="font-family:Tahoma, Geneva, sans-serif; color:#444444; font-size:9px; line-height:17px;">AVISO LEGAL<br /> 
			Este mensaje está dirigido únicamente a su destinatario y es confidencial. Si lo ha recibido o tenido conocimiento del mismo por error, GOBALO le informa que su contenido es reservado y su lectura, copia y uso no está autorizado, por lo que en tal caso le rogamos nos lo comunique por la misma vía y proceda a borrarlo de manera inmediata.</p>
			<p style="font-family:Tahoma, Geneva, sans-serif; color:#444444; font-size:9px; line-height:17px;">GOBALO no garantiza la confidencialidad de los mensajes transmitidos vía Internet, por lo que si el destinatario de este mensaje no consiente en la utilización de este medio, deberá ponerlo inmediatamente en nuestro conocimiento. GOBALO se reserva el derecho a ejercer las acciones legales que le correspondan contra todo tercero que acceda de forma ilegítima al contenido de este mensaje y al de los ficheros contenidos en el mismo.</p></td>
			  </tr>
			</table>';
		}
		//Footer plain text
		$plain_text.='AVISO LEGAL
		Este mensaje está dirigido únicamente a su destinatario y es confidencial. Si lo ha recibido o tenido conocimiento del mismo por error, GOBALO le informa que su contenido es reservado y su lectura, copia y uso no está autorizado, por lo que en tal caso le rogamos nos lo comunique por la misma vía y proceda a borrarlo de manera inmediata.
		
		GOBALO no garantiza la confidencialidad de los mensajes transmitidos vía Internet, por lo que si el destinatario de este mensaje no consiente en la utilización de este medio, debería ponerlo inmediatamente en nuestro conocimiento. GOBALO se reserva el derecho a ejercer las acciones legales que le correspondan contra todo tercero que acceda de forma ilegítima al contenido de este mensaje y al de los ficheros contenidos en el mismo.';
			
		$this->h_mail->From     = $from;
		$this->h_mail->FromName = $fromname;
		$this->h_mail->Subject  = $subject;
		$this->h_mail->AddReplyTo($reply, $reply);
		$this->h_mail->AltBody = $plain_text;
		$this->h_mail->Body = $body;
			
			
	}
	/**
	 * Adjuntar destinatarios
	 */
	public function addAddress($address){
		return $this->h_mail->AddAddress($address);
	}
	/**
	 * Adjuntar destinatarios
	 */
	public function AddBCC($address){
		return $this->h_mail->AddBCC($address);
	}
	/**
	 * Adjuntar ficheros
	 *
	 */
	public function addAttachment($atach="",$atach_name=""){
		if( is_file($atach) ){
			$this->h_mail->AddAttachment($atach,$atach_name);
		}
	}
	/**
	 * Enviar mail
	 *
	 */
	public function sendMail(){
		//se envia el mensaje, si no ha habido problemas
		//la variable $exito tendra el valor true
		$result=$this->h_mail->Send();
		//Si el mensaje no ha podido ser enviado se realizaran 4 intentos mas como mucho
		//para intentar enviar el mensaje, cada intento se hara 5 segundos despues
		//del anterior, para ello se usa la funcion sleep
		/*
		$intentos=1;
		while ((!$result) && ($intentos < 5)) {
			sleep(5);
			//echo $mail->ErrorInfo;
			$result = $this->h_mail->Send();
			if(!$result) {
				echo 'Error: ' . $this->h_mail->ErrorInfo;
			}
			$intentos++;
		}
		*/
		$this->h_mail->ClearAddresses();
		return $result;
	}
	/**
	* Enviar mail de Error
	*
	*/
	public function sendMailError($texto_error,$web,$optional_title=''){
		global $c_time;
		global $m_cms;
		if(is_object($m_cms)){

			$history=$m_cms->getUserHistory("print");
		}else{
			$sesion="No está inicializada la clase Minutoauro";
		}
		if(is_object($c_time)){

			$milestones=$c_time->getTotalTime("print");
		}else{
			$milestones="No hay hitos guardados";
		}
		$aviso_text="<div style='padding:25px 25px 35px 35px;color:#000;background:#FFF;font-size:12px'>
			<p>Se ha detectado un error en <b>".$web."</b> a las ".date('h:i:s')." del ".date('jS \of F Y')."<br>
			<b style='font-weight:boldcolor:#DC378D'>ERROR</b>:</p><font color='#000' style='font-weight:bold'>".$texto_error."</font><p>
			<h2>INFORMACIÓN DEL ERROR:</h2>
			<b>SERVER:</b> ". $_SERVER ["HTTP_HOST"] . "<br>" .
			"<b>METODO:</b>" .$_SERVER ["REQUEST_METHOD"] . "<br>".
			"<b>URL:</b> " . $_SERVER ["REQUEST_URI"]."<br>".
			"--------------------------------------------------------<br>".
			"<h2>INFORMACIÓN DEL USUARIO:</h2> ".
			"<b>IP:</b> ". $_SERVER ["REMOTE_ADDR"] . "<br>" . 
			"<b>NAVEGADOR:</b> ". @$_SERVER['HTTP_USER_AGENT'] . "<br>" . 
			"<b>RESOLUCIÓN:</b> ". @$_COOKIE['width_screen'] . "x" . @$_COOKIE['height_screen'] . "<br>" . 
			"<b>URL:</b> " . $_SERVER ["REQUEST_URI"]."<br>".
			"--------------------------------------------------------<br>".		
			"<h2>INFORMACIÓN DE HITOS DE TIEMPO Y MEMORIA:</h2> ".
			"<b></b> ". $milestones . "<br>" . 		
			"--------------------------------------------------------<br>".		
			"<h2>HISTORIAL DEL USUARIO VISITANTE:</h2> ".
			"<b></b> ". $history . "<br>" . 					
			"</div>";
		if($optional_title==''){
			$optional_title="Error en código detectado";
		}
		$this->constructMail(MAILER_USER, "Reporte de error", "[AVISO] ".$optional_title." en ".$_SERVER ["HTTP_HOST"], $aviso_text, "", MAILER_USER,true);
		//$this->addAddress("info@gobalo.es");
		//$this->addAddress("a.vara@gobalo.es");
		$this->addAddress("support@gobalo.es");
		$result=$this->sendMail();

		return $result;
	}
	public function stringToMail($texto){
		$texto=html_entity_decode($texto,ENT_NOQUOTES);
		$texto=htmlspecialchars_decode($texto);
		$texto=str_replace("\\\"", '"', $texto);
		$texto=str_replace("\\n", '', $texto);
		$texto=str_replace("\\r", '', $texto);
		//$texto=utf8_encode($texto);
		return $texto;
	}
	function checkTimeMailer($text){
		global $c_time;
		$ok=false;
		if(!is_object($c_time)) {
			$ok=false;
			$texto_error="[".get_class($this)."::".__FUNCTION__."::".__LINE__."] No se inicializó C_TIME \n";
			$this->sendMailError($texto_error, WEB_URL);
		}else{
			$ok=true;
		}
		if($ok){
			if($text==""){
				$text="[".get_class($this)."::".__FUNCTION__."::".__LINE__."] Query";
			}
			$c_time->setTimeCount($text);
			$ok=true;
		}else{
			$ok=false;
		}
		return $ok;
	}	

}
?>
