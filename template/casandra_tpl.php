<?php
/**
 * Casandra_TPL
 * @version Beta 0.8.1
 * @author Alberto Vara (C) Copyright 2011
 * @package gobalo.Hidra_DB
 */
class Casandra_TPL {
	/**
	 * assignSessionVar
	 * Asigna la variable de sesión indicada en $name
	 * @param string $name nombre de la variable a asignar
	 * @param string $value valor a asignar a la variable
	 */
	function drawFacebookButton(){
		$button='<a name="fb_share" type="icon" > <img src="'.WEB_URL.'img/noticias_facebook.gif" /></a>
				<script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" 
				        type="text/javascript">
				</script>';	
		return $button;
	}
}

?>