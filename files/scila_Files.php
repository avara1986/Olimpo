<?
/**
 * scila_Files
 * @version Beta 0.8.1
 * @author Alberto Vara (C) Copyright 2011
 * @package gobalo.scila_Files
 */
require_once (ROOT_DIR_CONF.'modulos/directoryinfo/directoryinfo.inc.php');
class scila_Files {
	/** *
	 * $dir_info
	 * Objeto donde se instancia la clase directory_info
	 */
	private $dir_info;
	/**
	 * __construct
	 * Constructor de la clase
	 */
	public function __construct(){
		$this->dir_info = new directory_info();
	}
	/**
	 * getFilesFromDir
	 * Devuelve un array con los ficheros del directorio
	 * @param string $name nombre de la variable a recoger
	 */
	public function getFilesFromDir(){
		$files=$this->dir_info->get_sorted_filelist( false, true );
		return $files;
	}
}

?>