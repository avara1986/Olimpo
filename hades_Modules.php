<?
/**
 * hades_Modules
 * @version Beta 0.8.1
 * @author Alberto Vara (C) Copyright 2012
 * @package gobalo.hades_Modules
 */
class hades_Modules extends OlimpoBaseSystem{
	/** *
	 * $dir_extensions
	 * Directorio de las extensiones
	 */
	private $dir_extensions = "extensions";	
	/** *
	 * $modules
	 * Array donde se guardan los módulos que se van a cargar
	 */
	private $modules = array ();
	
	public function __construct(){
		$this->checkTime("[HADES_MODULES::".__LINE__."] Creando la clase");
	}	
	/**
	 * getPathModule
	 * Devuelve el path de un módulo
	 */		
	public function getPathModule($name) {
		//echo ROOT_DIR_WEB."admin/".$this->dir_extensions."/".$name."/";
		if(strlen($name)>0){
			return ROOT_DIR_WEB."admin/".$this->dir_extensions."/".$name."/";
		}else{
			return false;
		}
		
	}	
	/**
	 * addModule
	 * Añade el módulo al listado de módulos a cargar
	 */		
	public function addModule($name) {
		if(is_dir($this->getPathModule($name))){
			$this->modules[$this->getNumModules()]['NAME']=$name;
			return true;
		}else{
			return false;
		}
	}
	/**
	 * getNumModules
	 * Devuelve el número de módulos
	 */		
	private function getNumModules() {
			return count($this->modules);
	}		
	/**
	 * loadAllModules
	 * Carga todos los módulos
	 */
	public function loadAllModulesConfMenu(){

		if($this->getNumModules()>0){
			$check_loading=true;
			$message="Todo correcto.";
			for($i=0;$i<$this->getNumModules() && $check_loading===true;$i++){
				/* Cargamos el menú para generar la ruta a los ficheros */
				$check_loading=$this->loadConfMenu($this->modules[$i]['NAME']);
				if($check_loading===false){
					$message="Error cargando menú de: <b>".$this->modules[$i]['NAME']."</b>";
				}
				/* Cargamos la configuración específica de cada módulo*/
				$this->loadConfModule($this->modules[$i]['NAME']);
				/*
				if($check_loading===false){
					$message="Error cargando configuración de: <b>".$this->modules[$i]['NAME']."</b>";
				}
				*/				
			}
			$result['RESULT']=$check_loading;
			$result['RESULT_MSG']=$message;		
			return $result;

		}else{
			return false;
		}
	}
	/**
	 * loadConfMenu
	 * Se carga el menú del módulo seleccionado
	 */	
	function loadConfMenu($name){	
		if(is_file($this->getPathModule($name)."conf/".$name."_menu.php")){
			include($this->getPathModule($name)."conf/".$name."_menu.php");
			if(is_array($menu[$name])){
				$result=$this->addMenuElements($menu[$name]);
				if($result){
					return true;
				}else{
					return true;
				}
			}else{
				return true;
			}
		}else{
			return false;
		}
	}
	/**
	 * loadConfModule
	 * Se carga el menú del módulo seleccionado
	 */	
	function loadConfModule($name){	
		if(is_file($this->getPathModule($name)."conf/".$name."_conf.php")){
			include($this->getPathModule($name)."conf/".$name."_conf.php");
			return true;
		}else{
			return false;
		}
	}	
}
?>