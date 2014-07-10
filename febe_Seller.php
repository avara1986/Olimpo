<?
/**
 * febe_Seller
 * @version Beta 0.8.1
 * @author Alberto Vara (C) Copyright 2012
 * @package gobalo.febe_Seller
 */
class febe_Seller extends OlimpoBaseSystem{
	/** *
	 * $type
	 * Opciones permitidas: Sanbox y live
	 */
	private $type = array ('0' => 'sanbox','1' => 'live');	
	/** *
	 * $signature
	 * token para conectar con paypal
	 */
	private $signature;
	/** *
	 * $api_signature
	 * API Servers for API Signature Security
	 */
	private $api_signature;	
	/** *
	 * $api_certificate
	 * API Servers for API Signature Security
	 */
	private $api_certificate;	
	/** *
	 * $api_websrc
	 * API Servers for API Signature Security
	 */
	private $api_websrc;		
	/** *
	 * $api_username
	 * Usuario receptor de los pagos
	 */
	private $api_username;		
	/** *
	 * $api_passowrd
	 * Contraseña de suario receptor de los pagos
	 */
	private $api_password;		
	/** *
	 * $pp_v
	 * Paypal version
	 */
	private $pp_v = "86.0";		
	/** *
	 * $paymentaction
	 * Paypal version
	 */
	private $paymentaction = "Authorization";	
	/** *
	 * $method
	 * Paypal version
	 */
	private $method = "SetExpressCheckout";			
	/** *
	 * $returnUrl
	 * Paypal version
	 */
	private $returnUrl = "";	
	/** *
	 * $cancelUrl
	 * Paypal version
	 */
	private $cancelUrl = "";		
	/** *
	 * $amt
	 * Precio del producto a cobrar
	 */
	private $amt = "";		
	/** *
	 * $dbSent
	 * array de variables enviadas, usada para validaciones
	 */
	private $dbSent = array();		
	/** *
	 * $dbRequest
	 * array de variables recibidas, usada para validaciones
	 */
	private $dbRequest = array();	
	/** *
	 * $ppToken
	 * token que se recibe cuando se conecta con paypal
	 */
	private $ppToken = "";					
	/**
	 * $ch
	 * Variable donde se inicialica Curl
	 */
	private $ch = null;
	/**
	 * $table_operations
	 * Tabla donde guardar las transacciones
	 */
	private $table_operations = TABLE_PAYMENT;	
	/**
	 * $client_session_name
	 * Nombre de la variable de sesión donde se guarda el Identificador del cliente que está operando
	 */
	public $client_session_name = 'client_id';		
	/**
	 * $productName
	 * Variable donde se inicialica Curl
	 */
	private $productName = "";	
	/**
	 * $productDesc
	 * Variable donde se inicialica Curl
	 */
	private $productDesc = "";		
	/**
	 * construct
	 * $mode => configura si las operaciones se van a hacer en podo de prueba(Sandbox) o versión en ejecución (Live)
	 */	
	public function __construct($mode='0'){
		$type_s=$this->type[$mode];
		if($type_s=='sanbox'){
			$this->api_signature='https://api-3t.sandbox.paypal.com/nvp';
			$this->api_certificate='https://api-3t.sandbox.paypal.com/nvp';
			$this->api_websrc="https://www.sandbox.paypal.com/cgi-bin/webscr";
			$this->signature="AYRP3.rCUTDUhKRuQJLWvm0w7PaBAXa.pbLVKqFR9OvU5Jid3som2kF2";
			$this->api_username="support-facilitator_api1.gobalo.es";
			$this->api_password="1367998042";
		}elseif($type_s=='live'){
			$this->api_signature='https://api-3t.paypal.com/nvp';
			$this->api_certificate='https://api-3t.paypal.com/nvp';
			$this->api_websrc="https://www.paypal.com/cgi-bin/webscr";
			$this->signature="AFcWxV21C7fd0v3bYYYRCpSSRl31AZHSypJEbkNa3OZym-BjzU1rhcVn";
			$this->api_username="";
			$this->api_password="";			
		}else{
			$texto_error="[ERROR febe_Seller::__construct] No se pudo inicializar la clase.\n";
			$result=$this->sendError($texto_error,WEB_URL);
			die("[ERROR febe_Seller] NO PAYPAL MODE");
		}
		$this->initCurl();
	}	
	/**
	 * initCurl
	 * inicializa Curl
	 */		
	private function initCurl() {
		if(function_exists('curl_init')){	
			$ch = curl_init();
			//curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, TRUE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			//curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
			$this->ch = $ch;
		}else{
			$texto_error="[ERROR febe_Seller::initCurl] No se encuentra la función curl_init \n";
			$result=$this->sendError($texto_error,WEB_URL);
			die("[ERROR febe_Seller] No se encuentra la función curl_init");			
		}			
	}	
	/**
	 * getSignature
	 * Devuelve la Signature asociada
	 */
	function getSignature(){
		return $this->signature;
	}
	/**
	 * getDataSent
	 * Devuelve las variables enviadas en la última transacción a Paypal. Sirve para depurar
	 */	
	function getDataSent(){
		return $this->dbSent;
	}
	/**
	 * getDataRequest
	 * Devuelve las variables enviadas por Paypal. Sirve para depurar
	 */	
	function getDataRequest(){
		return $this->dbRequest;
	}
	/**
	 * getToken
	 * Devuelve la Signature asociada
	 */
	function getToken(){
		if(strlen($this->ppToken)==0){
			return $this->getRequestSessionVar('token');
		}else{
			return $this->ppToken;
		}
		
	}		
	/**
	 * getExpressCheckout
	 * Devuelve la Signature asociada
	 */
	function getExpressCheckoutUrl(){
		$url=$this->api_websrc."?cmd=".urlencode('_express-checkout')."&rm=2&token=".urlencode($this->ppToken);
		return $url;
	}		
	/**
	 * setReturnUrl
	 * Asigna la url de retorno
	 */		
	function setReturnUrl($url){
		$this->returnUrl=$url;
	}
	/**
	 * setCancelUrl
	 * Asigna la url de retorno en caso de cancelación
	 */			
	function setCancelUrl($url){
		$this->cancelUrl=$url;
	}
	/**
	 * setProductName
	 * Asigna la url de retorno en caso de cancelación
	 */			
	function setProductName($value){
		$this->productName=$value;
	}	
	/**
	 * setProductDesc
	 * Asigna la url de retorno en caso de cancelación
	 */			
	function setProductDesc($value){
		$this->productDesc=$value;
	}		
	/**
	 * Fija el precio
	 * Asigna la url de retorno en caso de cancelación
	 */				
	function setAmt($amount){
		$amount=$this->formatNumber($amount);
		if(is_numeric($amount) && strlen($amount)>0 && $amount>0) {
			$this->amt=$amount;
			$result=true;
		}else{
			$result=false;
		}
		return $result;
	}		
	/**
	 * setExpressCheckout
	 * Crea una conexión con PayPal, creando el TOKEN
	 */
	function setExpressCheckout(){
		$this->method='SetExpressCheckout';
		if($this->returnUrl != '' && $this->cancelUrl != ''){
			$paypal_data = array(
			    'USER' => urlencode($this->api_username),
			    'PWD' => urlencode($this->api_password),
			    'SIGNATURE' => urlencode($this->signature),
			    'VERSION' => urlencode($this->pp_v),
			    'PAYMENTREQUEST_0_PAYMENTACTION' => urlencode($this->paymentaction),
				'PAYMENTREQUEST_0_CURRENCYCODE' => urlencode('EUR'),
				'METHOD' => urlencode($this->method),
				'LOCALECODE' => 'ES',
				'RM' => urlencode('2'),
			    'L_PAYMENTREQUEST_0_NAME0' => ($this->productName),
				'L_PAYMENTREQUEST_0_DESC0' => ($this->productDesc),
				'L_PAYMENTREQUEST_0_AMT0' => urlencode($this->amt),
			    'PAYMENTREQUEST_0_AMT' => urlencode($this->amt),
			     'RETURNURL' => ($this->returnUrl),
			    'CANCELURL' => ($this->cancelUrl)
			);
			/* Debug */		
			$this->dbSent=$paypal_data;
			/* ***** */ 	
			/*	
			$url = .'?' . ;
			echo "URL: ".$url."<br/>";
			*/
			curl_setopt($this->ch, CURLOPT_URL, $this->api_signature);		
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($paypal_data));
			//getting response from server
			$result = curl_exec($this->ch);
			if(curl_errno($this->ch)) {
				$texto_error="[ERROR febe_Seller::setExpressCheckout] Curl Error: " . curl_errno($this->ch) . ": " . curl_error($this->ch)." \n";
				$result=$this->sendError($texto_error,WEB_URL);
				die("[ERROR febe_Seller] Curl Error: " . curl_errno($this->ch) . ": " . curl_error($this->ch)."");					
			}		
			curl_close($this->ch);
			parse_str($result, $result);
			/* Debug */
			$this->dbRequest=$result;
			/* ***** */ 
		}else{
			$texto_error="[ERROR febe_Seller::setExpressCheckout] No se especificó la url de retorno o de cancelación \n";
			$result=$this->sendError($texto_error,WEB_URL);
			die("[ERROR febe_Seller] No se especificó la url de retorno o de cancelación");			
		}
		//die($result['TOKEN']." - ".$result['ACK']);
		if(strlen($result['TOKEN'])>0 && preg_match("/^Success/",$result['ACK'])){
			$this->ppToken=$result['TOKEN'];
			$this->assignSessionVar('token', $result['TOKEN']);
		}else{
			$texto_error="[ERROR febe_Seller::setExpressCheckout] Error al validar la conexión Paypal: <pre>".print_r($result,true)."</pre> \n<br> Información enviada:<pre>".print_r($paypal_data,true)."</pre>";
			$result=$this->sendError($texto_error,WEB_URL);
			die("[ERROR febe_Seller] Error al validar la conexión Paypal");			
		}
		return $result;
	}
	/**
	 * getExpressCheckout
	 * Nos devuelve la información de la compra
	 */	
	function getExpressCheckout(){
		$this->method='GetExpressCheckoutDetails';
		$paypal_data = array(
		    'USER' => urlencode($this->api_username),
		    'PWD' => urlencode($this->api_password),
		    'SIGNATURE' => urlencode($this->signature),
		    'VERSION' => urlencode($this->pp_v),
		    'METHOD' => urlencode($this->method),
			'TOKEN' => urlencode($this->getToken())
		);
		/* Debug */		
		$this->dbSent=$paypal_data;
		/* ***** */ 	
		/*	
		$url = .'?' . ;
		echo "URL: ".$url."<br/>";
		*/
		curl_setopt($this->ch, CURLOPT_URL, $this->api_signature);		
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($paypal_data));
		//getting response from server
		$result = curl_exec($this->ch);
		if(curl_errno($this->ch)) {
			$texto_error="[ERROR febe_Seller::setExpressCheckout] Curl Error: " . curl_errno($this->ch) . ": " . curl_error($this->ch)." \n";
			$result=$this->sendError($texto_error,WEB_URL);
			die("[ERROR febe_Seller] Curl Error: " . curl_errno($this->ch) . ": " . curl_error($this->ch)."");					
		}		
		curl_close($this->ch);
		parse_str($result, $result);
		/* Debug */
		$this->dbRequest=$result;
		/* ***** */ 
		if($this->checkPayPalTokens($result['TOKEN']) && $result['ACK']=='Success'){
			
		}else{
			$texto_error="[ERROR febe_Seller::getExpressCheckout] Error al validar la conexión Paypal: <pre>".print_r($result,true)."</pre> \n<br> Información enviada:<pre>".print_r($paypal_data,true)."</pre>";
			$result=$this->sendError($texto_error,WEB_URL);
			die("[ERROR febe_Seller] Error al validar la conexión Paypal");			
		}
		return $result;
	}
	/**
	 * checkPayPalTokens
	 * Pasamos un token y nos verifica si es el toquen de la sesión creada
	 */		
	function checkPayPalTokens($token){
		$token_session=$this->getRequestSessionVar('token');
		if($token===$token_session && strlen($token)>0 && strlen($token_session)>0){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * saveProcessProducts_Paypal
	 * Guardamos la información del proceso de compra
	 */			
	function saveProcessProducts_Paypal($products,$info_client){
		if (class_exists('hidra_DB')) {
			$h_db = new hidra_DB(DBHOST,DBUSER,DBPASS,DBNAME);
		}else{
			die("[ERROR en febe_Seller::".__LINE__."]hidra_DB no se ha construido");
		}		
		if(strlen($this->getRequestSessionVar($this->client_session_name))==0){
			die("[ERROR en febe_Seller::".__LINE__."]No existe ninguna ID de usuario");
		}		
		if(is_array($products) && is_array($info_client)){		
			$num_products=count($products);
			for($i=0;$i<$num_products;$i++){
				$result=$h_db->query('ID',$this->table_operations,'','SESSION_TOKEN = ? AND FK_CLIENT = ? AND FK_PRODUCT = ?','',array($info_client['TOKEN'],$this->getRequestSessionVar($this->client_session_name),$products[$i]['ID']));
				if(count($result)==0){
					$h_db->addIUDTable($this->table_operations);
					$h_db->setAction('I');
					$h_db->addIUField('ID', uniqid());
					$h_db->addIUField('FK_CLIENT', $this->getRequestSessionVar($this->client_session_name));
					$h_db->addIUField('FK_PRODUCT', $products[$i]['ID']);
					$h_db->addIUField('UNITS', '1');
					$h_db->addIUField('PRICE', $info_client['AMT']);
					
					$h_db->addIUField('SESSION_TOKEN', $info_client['TOKEN']);
					
					$h_db->addIUField('PP_PAYERID', $info_client['PAYERID']);
					
					$h_db->addIUField('ORDER_DATE', $info_client['TIMESTAMP']);
					$h_db->addIUField('CORRELATONID', $info_client['CORRELATIONID']);
					$h_db->addIUField('PAYMENT_STATUS', $info_client['PAYERSTATUS']);
					$h_db->addIUField('CHECKOUT_STATUS', $info_client['CHECKOUTSTATUS']);
					$h_db->addIUField('INVOICE_CLIENT_NAME', $info_client['FIRSTNAME']);
					$h_db->addIUField('INVOICE_CLIENT_SURNAME', $info_client['LASTNAME']);
					$h_db->addIUField('INVOICE_CLIENT_COUNTRY', $info_client['COUNTRYCODE']);
					$h_db->addIUField('INVOICE_SHIP_NAME', $info_client['SHIPTONAME']);
					$h_db->addIUField('INVOICE_SHIP_ADDRESS', $info_client['SHIPTOSTREET']." (".$info_client['SHIPTOCITY']."), ".$info_client['SHIPTOSTREET']." CP: ".$info_client['SHIPTOZIP']." COUNTRY: ".$info_client['SHIPTOCOUNTRYNAME']);
					$result=$h_db->executeCommand();
				}else{
					$result=false;
				}
			}
		}else{
			$result=false;
		}


		return $result;
	}
	/**
	 * doExpressCheckoutPayment
	 * Cobramo$ la pa$sta!j€j€
	 */	
	function doExpressCheckoutPayment($token="",$payerid="",$product_price, $orderId){
		$this->method='DoExpressCheckoutPayment';
		//$amount=$this->formatNumber($amount);
		/*
		echo "<pre>";
		print_r($result);
		echo "</pre>";	
		*/	
		if(is_numeric($product_price) && $product_price > 0 && strlen($product_price)>0){
				$paypal_data = array(
			    'USER' => urlencode($this->api_username),
			    'PWD' => urlencode($this->api_password),
			    'SIGNATURE' => urlencode($this->signature),
			    'VERSION' => urlencode($this->pp_v),
				'PAYMENTACTION'	=>	'Authorization',
			    'METHOD' => urlencode($this->method),
				'AMT' => $product_price,
				'CURRENCYCODE' => urlencode('EUR'),
				'TOKEN' => urlencode($token),
				'PAYERID' => urlencode($payerid)
			);
			/* Debug */		
			$this->dbSent=$paypal_data;
			/* ***** */ 	
			/*	
			$url = .'?' . ;
			echo "URL: ".$url."<br/>";
			*/
			curl_setopt($this->ch, CURLOPT_URL, $this->api_signature);		
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($paypal_data));
			//getting response from server
			$result = curl_exec($this->ch);
			if(curl_errno($this->ch)) {
				$texto_error="[ERROR febe_Seller::setExpressCheckout] Curl Error: " . curl_errno($this->ch) . ": " . curl_error($this->ch)." \n";
				$result=$this->sendError($texto_error,WEB_URL);
				die("[ERROR febe_Seller] Curl Error: " . curl_errno($this->ch) . ": " . curl_error($this->ch)."");					
			}		
			curl_close($this->ch);
			parse_str($result, $result);
			/* Debug */
			$this->dbRequest=$result;
			/*
			echo "<pre>";
			print_r($result);
			echo "</pre>";	
			*/		
			/* ***** */ 
			if($this->checkPayPalTokens(@$result['TOKEN']) && $result['ACK']=='Success'){
				if (class_exists('hidra_DB')) {
					$h_db = new hidra_DB(DBHOST,DBUSER,DBPASS,DBNAME);
				}else{
					die("[ERROR en febe_Seller::".__LINE__."]hidra_DB no se ha construido");
				}		
				if(strlen($this->getRequestSessionVar($this->client_session_name))==0){
					die("[ERROR en febe_Seller::".__LINE__."]No existe ninguna ID de usuario");
				}			
				$h_db->addIUDTable($this->table_operations);
				$h_db->setAction('I');
				$h_db->addIUField('ID', uniqid());
				$h_db->addIUField('PAYMENT_ID', $result['TRANSACTIONID']);
				$h_db->addIUField('PAYMENT_DATE', $result['TIMESTAMP']);
				$h_db->addIUField('PRICE', $result['AMT']);
				$h_db->addIUField('PAYMENT_STATUS', $result['PAYMENTSTATUS']);
				$h_db->addIUField('CHECKOUT_STATUS', $result['CHECKOUTSTATUS']);
				//$h_db->addIUField('CORRELATIONID', $result['CORRELATIONID']);
				$h_db->addIUField('FK_ORDER', $orderId);
				$h_db->addIUField('FK_CLIENT', $this->getRequestSessionVar($this->client_session_name));
				$h_db->addIUField('SESSION_TOKEN', $result['TOKEN']);
				//$h_db->addIUField('PP_PAYERID', $result['PAYERID']);
				//$h_db->addIUField('INVOICE_CLIENT_NAME', $result['FIRSTNAME']);
				//$h_db->addIUField('INVOICE_CLIENT_SURNAME', $result['LASTNAME']);
				//$h_db->addIUField('INVOICE_CLIENT_COUNTRY', $result['COUNTRYCODE']);
				//$h_db->addIUField('INVOICE_SHIP_NAME', $result['SHIPTONAME']);
				
				/*
				$h_db->setAction('U');
				$h_db->addIUField('PAYMENT_ID', $result['TRANSACTIONID']);
				$h_db->addIUField('PAYMENT_DATE', $result['TIMESTAMP']);
				$h_db->addIUField('PAYMENT_STATUS', $result['PAYMENTSTATUS']);
				
				
				$h_db->addUDCond('FK_ORDER', $orderId);
				$h_db->addUDCond('FK_CLIENT', $this->getRequestSessionVar($this->client_session_name));
				$h_db->addUDCond('SESSION_TOKEN', $result['TOKEN']);
				 * */
				$result=$h_db->executeCommand();			
			}else{
				$texto_error="[ERROR febe_Seller::doExpressCheckoutPayment] Error al validar la conexión Paypal: <pre>".print_r($result,true)."</pre> \n<br> Información enviada:<pre>".print_r($paypal_data,true)."</pre>";
				$result=$this->sendError($texto_error,WEB_URL);
				die("[ERROR febe_Seller] Error al validar la conexión Paypal");			
			}		
		}else{
				$texto_error="[ERROR febe_Seller::doExpressCheckoutPayment] Error al mandar los datos: la cantidad no puede ser 0</pre>";
				$result=$this->sendError($texto_error,WEB_URL);
				die("[ERROR febe_Seller::doExpressCheckoutPayment] Error al mandar los datos: la cantidad no puede ser 0");				
		}
		return $result;
	}
	/**
	 * 	
	 * getTransactionDetails
	 * Crea una conexión con PayPal, creando el TOKEN
	 */
	function getTransactionDetails($id_transaction){
		$this->method='GetTransactionDetails';
		if(strlen($id_transaction)>0){
				$paypal_data = array(
			    'METHOD' => urlencode($this->method),
			    'TRANSACTIONID' => urlencode($id_transaction),
			    'USER' => urlencode($this->api_username),
			    'PWD' => urlencode($this->api_password),
			    'SIGNATURE' => urlencode($this->signature),
			    'VERSION' => urlencode($this->pp_v)				
			);
			/* Debug */		
			$this->dbSent=$paypal_data;
			/* ***** */ 	
			/*	
			$url = .'?' . ;
			echo "URL: ".$url."<br/>";
			*/
			curl_setopt($this->ch, CURLOPT_URL, $this->api_signature);		
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($paypal_data));
			//getting response from server
			$result = curl_exec($this->ch);
			if(curl_errno($this->ch)) {
				$texto_error="[ERROR febe_Seller::getTransactionDetails] Curl Error: " . curl_errno($this->ch) . ": " . curl_error($this->ch)." \n";
				$result=$this->sendError($texto_error,WEB_URL);
				die("[ERROR febe_Seller] Curl Error: " . curl_errno($this->ch) . ": " . curl_error($this->ch)."");					
			}		
			//curl_close($this->ch);
			parse_str($result, $result);
			/* Debug */
			$this->dbRequest=$result;		
		}else{
			$result=false;
		}
		return $result;
	}
	/**
	 * saveProcessProducts_Paypal
	 * Guardamos la información del proceso de compra
	 */			
	function updateProcess_PaymentStatus($id_transaction,$status){
		if (class_exists('hidra_DB')) {
			$h_db = new hidra_DB(DBHOST,DBUSER,DBPASS,DBNAME);
		}else{
			die("[ERROR en febe_Seller::".__LINE__."]hidra_DB no se ha construido");
		}		
		if(strlen($this->getRequestSessionVar($this->client_session_name))==0){
			die("[ERROR en febe_Seller::".__LINE__."]No existe ninguna ID de usuario");
		}		
		if(strlen($id_transaction)>0 && strlen($status)>0){		

				$result=$h_db->query('PAYMENT_ID',$this->table_operations,'','PAYMENT_ID = ?','',array($id_transaction));
				if(count($result)!=0){
					$h_db->addIUDTable($this->table_operations);
					$h_db->setAction('U');
					$h_db->addIUField('PAYMENT_STATUS', $status);
					if($status=='Completed'){
						$h_db->addIUField('RECEIPT_DATE', date('Y-m-d H:m:s'));
					}
					
					$h_db->addUDCond('PAYMENT_ID', $result[0]['PAYMENT_ID']);
					$result=$h_db->executeCommand();
				}else{
					$result=false;
				}
		}else{
			$result=false;
		}
		return $result;
	}	
			
}
				/*
				 * 
				 * 
				 * 			 * CREATE TABLE IF NOT EXISTS `fit_clients_payments` (
`ID` VARCHAR( 40 ) NOT NULL ,
`CREATED` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`FK_CLIENT` VARCHAR( 40 ) NOT NULL,
`FK_PRODUCT` VARCHAR( 40 ) NOT NULL,
`UNITS` DECIMAL(8,0) NOT NULL,
`PRICE` DECIMAL(8,2) NOT NULL COMMENT 'Paypal: [AMT]',
`FIT_START_DATE` date NOT NULL,
`FIT_END_DATE` date NOT NULL,
SESSION_TOKEN VARCHAR( 250 ) NOT NULL,
PP_PAYERID VARCHAR( 40 ) NOT NULL,
`ORDER_DATE` datetime NOT NULL COMMENT 'Fecha que se registró la factura  Paypal: [TIMESTAMP]',
`CORRELATONID` VARCHAR(40)  NOT NULL COMMENT 'Paypal: [CORRELATIONID]',
`PAYMENT_DATE` datetime NOT NULL COMMENT 'Fecha que se realizo el pago',
`PAYMENT_ID` VARCHAR( 50 ) NOT NULL COMMENT 'Paypal: [PAYMENTINFO_0_TRANSACTIONID]',
`PAYMENT_STATUS` VARCHAR( 50 ) NOT NULL COMMENT 'Paypal: [PAYERSTATUS]|verified,Pending',
CHECKOUT_STATUS VARCHAR( 50 ) NOT NULL COMMENT 'Paypal: [CHECKOUTSTATUS]',
`RECEIPT_DATE` datetime NOT NULL COMMENT 'Fecha que se cobro',
INVOICE_CLIENT_NAME VARCHAR( 255 ) NOT NULL COMMENT 'Paypal: [FIRSTNAME]',
INVOICE_CLIENT_SURNAME VARCHAR( 255 ) NOT NULL COMMENT 'Paypal: [LASTNAME]',
INVOICE_CLIENT_COUNTRY VARCHAR( 255 ) NOT NULL COMMENT 'Paypal: [COUNTRYCODE]',
INVOICE_SHIP_NAME VARCHAR( 255 ) NOT NULL COMMENT 'Facturar a... | Paypal: [SHIPTONAME] ',
INVOICE_SHIP_ADDRESS VARCHAR( 255 ) NOT NULL COMMENT 'Dirección de facturación | Paypal: [SHIPTOSTREET],[SHIPTOCITY],[SHIPTOZIP],[SHIPTOCOUNTRYNAME]',
PRIMARY KEY ( `ID` )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin;


				 *     [TOKEN] => EC-3F3834943R725443J
					    [SUCCESSPAGEREDIRECTREQUESTED] => false
					    [TIMESTAMP] => 2012-03-22T14:54:45Z
					    [CORRELATIONID] => 4df6f7e92cfef
					    [ACK] => Success
					    [VERSION] => 86.0
					    [BUILD] => 2649250
					    [TRANSACTIONID] => 8DA90762TF455360P
					    [TRANSACTIONTYPE] => expresscheckout
					    [PAYMENTTYPE] => instant
					    [ORDERTIME] => 2012-03-22T14:54:43Z
					    [AMT] => 5.95
					    [TAXAMT] => 0.00
					    [CURRENCYCODE] => EUR
					    [PAYMENTSTATUS] => Pending
					    [PENDINGREASON] => authorization
					    [REASONCODE] => None
					    [PROTECTIONELIGIBILITY] => Eligible
					    [INSURANCEOPTIONSELECTED] => false
					    [SHIPPINGOPTIONISDEFAULT] => false
					    [PAYMENTINFO_0_TRANSACTIONID] => 8DA90762TF455360P
					    [PAYMENTINFO_0_TRANSACTIONTYPE] => expresscheckout
					    [PAYMENTINFO_0_PAYMENTTYPE] => instant
					    [PAYMENTINFO_0_ORDERTIME] => 2012-03-22T14:54:43Z
					    [PAYMENTINFO_0_AMT] => 5.95
					    [PAYMENTINFO_0_TAXAMT] => 0.00
					    [PAYMENTINFO_0_CURRENCYCODE] => EUR
					    [PAYMENTINFO_0_PAYMENTSTATUS] => Pending
					    [PAYMENTINFO_0_PENDINGREASON] => authorization
					    [PAYMENTINFO_0_REASONCODE] => None
					    [PAYMENTINFO_0_PROTECTIONELIGIBILITY] => Eligible
					    [PAYMENTINFO_0_PROTECTIONELIGIBILITYTYPE] => ItemNotReceivedEligible,UnauthorizedPaymentEligible
					    [PAYMENTINFO_0_SECUREMERCHANTACCOUNTID] => PUWCE6VUMAG4Y
					    [PAYMENTINFO_0_ERRORCODE] => 0
					    [PAYMENTINFO_0_ACK] => Success
				 * 
				 * 
				 * 
				 * */
?>