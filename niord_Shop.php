<?
/**
 * niord_Shop
 * @version Beta 0.8.1
 * @author Alberto Vara (C) Copyright 2012
 * @package gobalo.febe_Seller
 */
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

}

?>