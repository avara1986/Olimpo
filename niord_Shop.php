<?
/**
 * niord_Shop
 * @version Beta 0.8.1
 * @author Alberto Vara (C) Copyright 2012
 * @package gobalo.febe_Seller
 */
 /*
class niord_Shop extends OlimpoBaseSystem{
	/** *
	 * $shoppingCart
	 * 
	 */
	//private $shoppingCart = array();	
	/** *
	 * $maxProducts
	 * Número máximo de productos por sesión
	 */
	/*private $maxProducts = '1';		
	
	
	public function __construct(){
		$this->shoppingCart = $this->getRequestSessionVar('shoppingCart');
	}
	public function addProductToCart($productId,$amount=1){
		if(strlen($productId)>0){
			$this->checkVarSecurity($productId);
			$this->removeXSS($productId);			
			$this->checkVarSecurity($amount);
			$this->removeXSS($amount);
			if(is_numeric($amount) && $amount>0){
				$num_products=count(@$_SESSION['shoppingCart']);	
				$k=$num_products;
				if($k>=$this->maxProducts){
					$k=$this->maxProducts - 1;
				}
				$_SESSION['shoppingCart'][$k]['ID']=$productId;
				$_SESSION['shoppingCart'][$k]['AMOUNT']=$amount;	
				$result=true;		
			}else{
				$result=false;	
			}			
			
		}else{
			$result=false;
		}
		return $result;
	}	
	public function getShoppingCart($num_products=0){
		return $_SESSION['shoppingCart'];
	}	
	public function destroyShoppingCart(){
		$this->assignSessionVar('shoppingCart', '');
		return true;
	}		

}*/

/*************** SON NECESARIAS LAS SIGUIENTES CONSTANTES DEFINIDAS *********************
 * 																						*
 * TABLE_PRODUCTS		->	Guarda la info de los productos								*
 * TABLE_CLIENTS		->	Guarda la info de los clientes registrados					*
 * TABLE_ORDERS			->	Guarda los pedidos realizados por cada cliente				*
 * TABLE_ORDER_CLIENTS	->	Guarda la info de los productos comprados en cada pedido	*
 * 																						*
 ***************************************************************************************/
class niord_Shop extends OlimpoBaseSystem{
	/** *
	 * $shoppingCart
	 * 
	 */
	private $shoppingCart = array();	
	/** *
	 * $maxProducts
	 * Número máximo de productos por sesión
	 */
	private $maxProducts = '1';
	
	
	public function __construct(){
		$this->shoppingCart = $this->getRequestSessionVar('shoppingCart');
		if(!$this->shoppingCart){
			$_SESSION['shoppingCart']=array();
		}
		//$this->$maxProducts = count($this->shoppingCart);
	}
	
	public function addProductToCart($productId,$amount=1){ //$amount es + o -
		//Valido la id y la cantidad
		if(strlen($productId)>0){
			$this->checkVarSecurity($productId);
			$this->detectXSS($productId);			
			$this->checkVarSecurity($amount);
			$this->detectXSS($amount);
			if(is_numeric($amount) && ($amount>0 || $amount<0)){
				//Los añado a la sesión
				if(isset($_SESSION['shoppingCart'][$productId])){ //Si el producto existe, añade la cantidad a los que ya hay
					$_SESSION['shoppingCart'][$productId]['ID']=$productId;
					//$_SESSION['shoppingCart'][$productId]['AMOUNT']+=$amount;
					$_SESSION['shoppingCart'][$productId]['AMOUNT']=$amount;
				}else{
					$_SESSION['shoppingCart'][$productId]['ID']=$productId;
					$_SESSION['shoppingCart'][$productId]['AMOUNT']=$amount;
				}
				
				//Los añado al atributo
				if(isset($this->shoppingCart[$productId])){ //Si el producto existe, añade la cantidad a los que ya hay
					$this->shoppingCart[$productId]['ID']=$productId;
					//$this->shoppingCart[$productId]['AMOUNT']+=$amount;
					$this->shoppingCart[$productId]['AMOUNT']=$amount;
				}else{
					$this->shoppingCart[$productId]['ID']=$productId;
					$this->shoppingCart[$productId]['AMOUNT']=$amount;	
				}
				//Actualizo la cantidad de productos en el carrito
				$this->maxProducts=count($this->shoppingCart);
				$result=true;		
			}else $result=false;					
		}else $result=false;
		//Resultado de la operación
		return $result;
	}
	public function saveCart($basket){
		if(count($basket)>0){
			//Vacío el carrito
			$this->removeCart();
			//Añado cada producto individualmente
			if($basket!=false){
				foreach($basket as $product){
					$this->addProductToCart($product['id'],$product['amount']);
				}
			}
			$result=true;
		}else{
			$this->removeCart();
			$result=false;
		}
		return $result;
	}
	public function removeProductFromCart($productId){
		if(strlen($productId)>0){
			$this->checkVarSecurity($productId);
			$this->detectXSS($productId);			
			if(isset($_SESSION['shoppingCart'][$productId])){
				unset($_SESSION['shoppingCart'][$productId]);
			}
			if(isset($this->shoppingCart[$productId])){
				unset($this->shoppingCart[$productId]);
			}
			$result=true;
		}else{
			$result=false;
		}
		return $result;
	}
	
	public function buyCart($m_cms){
		/*
		$result=$m_cms->setAction('U');
			$m_cms->addIUDTable(TABLE_ORDERS);
			$m_cms->addUDCond('ID', $_SESSION['orderId']);
			$m_cms->addIUField('GASTOS_ENVIO',0);
			$m_cms->addIUField('STATUS',1);
		$m_cms->executeCommand();
		
		if($result) $this->removeCart();
		$_SESSION['order_id']="";
		unset($_SESSION['order_id']);
		
		return $result;
		*/
		$basket=$this->getShoppingCart();
		$orderId=$_SESSION['order_id'];
		$clientId=$m_cms->getRequestSessionvar("temp_client")?$m_cms->getRequestSessionvar("temp_client_id"):$m_cms->getRequestSessionvar("client_id");
		if(count($basket)>0){
		//	$orderId=uniqid();
			$status=0;			
			$totalPrice=0;
			
			foreach($basket as $producto){
				$product=$m_cms->query("*",TABLE_PRODUCTS,"","ID = ?","",array($producto['ID']));
				$product=$product[0];
				$amount=$producto['AMOUNT'];
				$totalSubPrice=$product['PRICE']*$amount;
				$totalSubPrice+=$totalSubPrice*$product['IVA']/100;
				$totalSubPrice=round($totalSubPrice,2);
				$totalPrice+=$totalSubPrice;
				
				$result=$m_cms->setAction('I');
					$m_cms->addIUDTable(TABLE_ORDER_PRODUCTS);	
					$m_cms->addIUField('FK_ORDER',$orderId);
					$m_cms->addIUField('FK_USER',$clientId);
					$m_cms->addIUField('FK_PRODUCT',$product['ID']);
					$m_cms->addIUField('AMOUNT',$producto['AMOUNT']);
					$m_cms->addIUField('PRICE',$product['PRICE']);
					$m_cms->addIUField('SUBTOTAL',$totalSubPrice);
					$m_cms->addIUField('STATUS',1);
				$m_cms->executeCommand();
			}
			//Añado  gastos de envío aquí
			
			//----------------------------------------------------------
			$client=$m_cms->query("NAME,SURNAME,PHONE,EMAIL,ADDRESS,CP,NIF,FK_COUNTRY,FK_PROVINCE",TABLE_CLIENTS,"","ID = ?","",array($clientId));
			$result=$m_cms->setAction('I');
				$m_cms->addIUDTable(TABLE_ORDERS);	
				$m_cms->addIUField('ID',$orderId);
				$m_cms->addIUField('NAME',utf8_decode($client[0]['NAME']));
				$m_cms->addIUField('SURNAME',utf8_decode($client[0]['SURNAME']));
				$m_cms->addIUField('PHONE',$client[0]['PHONE']);
				$m_cms->addIUField('EMAIL',$client[0]['EMAIL']);
				$m_cms->addIUField('NIF',$client[0]['NIF']);
				$m_cms->addIUField('FK_USER',$clientId);
				$m_cms->addIUField('PRICE',$totalPrice);
				$m_cms->addIUField('STATUS',1);
				$m_cms->addIUField('GASTOS_ENVIO',0);
				$m_cms->addIUField('ADDRESS',utf8_decode($client[0]['ADDRESS']));
				$m_cms->addIUField('CP',$client[0]['CP']);
				$m_cms->addIUField('FK_COUNTRY',$client[0]['FK_COUNTRY']);
				$m_cms->addIUField('FK_PROVINCE',$client[0]['FK_PROVINCE']);
			$m_cms->executeCommand();
			
			
			
		}else $result=false; //Cesta vacía
		
		$_SESSION['last_order_id']=$orderId;
		
		if($result){
			$this->removeCart();
			$_SESSION['order_id']="";
			unset($_SESSION['order_id']);
		}
		
		return $result;
	}
	
	public function getOrderId(){return $this->orderId;}
	
	public function buyCartTemp($orderId, $m_cms){
		$basket=$this->getShoppingCart();
		$orderId=$orderId;
		$clientId=$m_cms->getRequestSessionvar("temp_client")?$m_cms->getRequestSessionvar("temp_client_id"):$m_cms->getRequestSessionvar("client_id");
		if(count($basket)>0){
		//	$orderId=uniqid();
			$status=0;			
			$totalPrice=0;
			
			$result=$m_cms->setAction('D');
				$m_cms->addIUDTable(TABLE_ORDERS);	
				$m_cms->addUDCond('ID',$orderId);
			$m_cms->executeCommand();
			$result=$m_cms->setAction('D');
				$m_cms->addIUDTable(TABLE_ORDER_PRODUCTS);	
				$m_cms->addUDCond('FK_ORDER',$orderId);
			$m_cms->executeCommand();
			
			foreach($basket as $producto){
				$product=$m_cms->query("*",TABLE_PRODUCTS,"","ID = ?","",array($producto['ID']));
				$product=$product[0];
				$amount=$producto['AMOUNT'];
				$totalSubPrice=$product['PRICE']*$amount;
				$totalSubPrice+=$totalSubPrice*$product['IVA']/100;
				$totalSubPrice=round($totalSubPrice,2);
				$totalPrice+=$totalSubPrice;
				
				$result=$m_cms->setAction('I');
					$m_cms->addIUDTable(TABLE_ORDER_PRODUCTS);	
					$m_cms->addIUField('FK_ORDER',$orderId);
					$m_cms->addIUField('FK_USER',$clientId);
					$m_cms->addIUField('FK_PRODUCT',$product['ID']);
					$m_cms->addIUField('AMOUNT',$producto['AMOUNT']);
					$m_cms->addIUField('PRICE',$product['PRICE']);
					$m_cms->addIUField('SUBTOTAL',$totalSubPrice);
					$m_cms->addIUField('STATUS',0);
				$m_cms->executeCommand();
			}
			//Añado  gastos de envío aquí
			
			//----------------------------------------------------------
			$client=$m_cms->query("NAME,SURNAME,PHONE,EMAIL,ADDRESS,CP,NIF,FK_COUNTRY,FK_PROVINCE",TABLE_CLIENTS,"","ID = ?","",array($clientId));
			$result=$m_cms->setAction('I');
				$m_cms->addIUDTable(TABLE_ORDERS);	
				$m_cms->addIUField('ID',$orderId);
				$m_cms->addIUField('NAME',$client[0]['NAME']);
				$m_cms->addIUField('SURNAME',$client[0]['SURNAME']);
				$m_cms->addIUField('PHONE',$client[0]['PHONE']);
				$m_cms->addIUField('EMAIL',$client[0]['EMAIL']);
				$m_cms->addIUField('NIF',$client[0]['NIF']);
				$m_cms->addIUField('FK_USER',$clientId);
				$m_cms->addIUField('PRICE',$totalPrice);
				$m_cms->addIUField('GASTOS_ENVIO',0);
				$m_cms->addIUField('ADDRESS',$client[0]['ADDRESS']);
				$m_cms->addIUField('CP',$client[0]['CP']);
				$m_cms->addIUField('FK_COUNTRY',$client[0]['FK_COUNTRY']);
				$m_cms->addIUField('FK_PROVINCE',$client[0]['FK_PROVINCE']);
				$m_cms->addIUField('STATUS',0);
			$m_cms->executeCommand();
			
			
			
		}else $result=false; //Cesta vacía
		return $result;
	}
	public function removeCart(){
		unset($_SESSION['shoppingCart']);
		unset($this->shoppingCart);
		return true;
	}
	public function getShoppingCart($num_products=0){
		if(!isset($_SESSION['shoppingCart'])){
			$_SESSION['shoppingCart']=array();
			$this->shoppingCart=array();
		}
		return $_SESSION['shoppingCart'];
	}	
	public function destroyShoppingCart(){
		$this->assignSessionVar('shoppingCart', '');
		return true;
	}
	public function existShoppingCart(){
		if($_SESSION['shoppingCart']!=null && $_SESSION['shoppingCart']!="" && $_SESSION['shoppingCart']!=undefined){
			return true;
		}else return false;
	}
	public function existProductInCart($productId){
		isset($_SESSION[$productId]) ? true : false;
	}

}

?>