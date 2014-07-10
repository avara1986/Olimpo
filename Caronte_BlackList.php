<?
 /**
 * Caronte_BlackList
 * @version Beta 0.8.1
 * @author Alberto Vara (C) Copyright 2011 
 * @package gobalo.cronos_Time
 */
class Caronte_BlackList extends OlimpoBaseSystem{ 
	/** *
	* $in_learning_mode
	* Si está activado, el programa genera estadísticas completas para aprender a buscar, si no, sólo con encontrar una coincidencia da el aviso.
	*/	
	private $in_learning_mode="on";	
	/** *
	* $init_word_list
	* array con las palabras sacadas de la Base de Datos
	*/	
	private $blacklist = array();
	/** *
	* $list_replaces_letters
	* Array de las letras que pueden aparecer de diferentes variaciones
	*/	
	private $list_badWords = array(
		'lorem ipsum','viagra','hello world','hello world','4U','Accept credit cards','Act Now!','Additional Income','Affordable','All natural','All new','Amazing',
		'Apply online','Bargain','Best price','Billing address','Buy direct','Call','Call free','Can’t live without',
		'psoe','upid','partido popular','franco');		
	/** *
	* $list_replaces_letters
	* Array de las letras que pueden aparecer de diferentes variaciones
	*/	
	private $list_replaces_letters = array(
		array('c','k'),
		array('ch','x'),
		array('qu','k'),
		array('rd','ld'),
		array('ñ','n'),
		array('gu','w'),	
		array('gü','w'),
		array('ç','c'),
		array('os','as'),
		array('o','a'),
		array('o','os'),
		array('v','b'),
	);		
	/** *
	* $list_variants
	* array bidimensional que contiene todas las variaciones de la palabra pasada
	*/	
	private $list_variants=array();	
    /**
     * getInitialWordList
     * 
     */	
	function getInitialWordList(){
		$params=array();
		//echo "BLACK_LIST_WORDS AND VARIANTS ORIGEN:<br/>";
		//$this->showArray($this->blacklist);		
		$num_words=count($this->list_badWords);	
		if($num_words>0){
			for($i=0;$i<$num_words;$i++){
				$this->blacklist[$i]['WORD']=$this->list_badWords[$i];
				$this->getVariants($this->list_badWords[$i],0);
				$this->blacklist[$i]['VARIANTS']=$this->list_variants;
				$this->list_variants=array();					
			}
		}
		//echo "BLACK_LIST_WORDS AND VARIANTS:<br/>";
		//$this->showArray($this->blacklist);
	}
	/**
	 * getVariants
	 * @param $word
	 * @param $num_subwords
	 */    
	function getVariants($word,$num_subwords=0){
    	$new_word="";	
		$is_variant=false;	
		$num_search=count($this->list_replaces_letters);
		/*
		echo "<pre>";
		print_r($this->list_replaces_letters);
		echo "</pre>";
		*/
		/**/
		for ($i=0;$i<$num_search && !$is_variant;$i++){
			//echo "i: ".$i."<br>";
			$check_word=$word;
			$letter = $this->list_replaces_letters[$i][0];
			$letter_variant = $this->list_replaces_letters[$i][1];
			
			if($letter!=""){	
				/*
				echo "<pre>
				ORIGEN: ".$word."<br>
				LETRA A BUSCAR: ".$letter."<br>
				REEMPLAZAR POR: ".$letter_variant."<br>
				------------------------------------<br>
				</pre>";
				*/
			}
			/**/
			if($is_variant===false){
				$new_word=preg_replace('/'.$letter.'+/', $letter_variant, $word,1); 
				if($check_word!=$new_word){
					$is_variant=true;				
				}			
			}				
		}		
		if(strlen($new_word)>0 && $new_word!=$word && $num_subwords<20){
			$word_exist=false;
			$variants=$this->list_variants;
			
			//$this->showArray($variants);
			//echo "<br>------------------------------------<br>";
			/**/			
			$num_variants=count($variants);
			if($num_variants>0){
				for($i=0;$i<$num_variants;$i++){
					if($variants[$i]==utf8_decode($new_word)){
						$word_exist=true;
					}
				}
			}
			if($word_exist==false){
				$num_subwords++;
				$this->list_variants[]=$new_word;
				$this->getVariants($new_word,$num_subwords);
			}
		}
    }
    /**
     * check
     */	    
    function check($text){
    	$find_badword=false;
    	$num_words=count($this->blacklist);
    	for($i=0;$i<$num_words;$i++){
    		$pattern="/".$this->blacklist[$i]['WORD']."/i";
			if(preg_match($pattern,$text)) {
				$find_badword=true;
				$list_word_found[]=$this->blacklist[$i]['WORD'];
				//echo "ENTRA1 CHECK: ".$this->blacklist[$i]['WORD']."<br>";
			}else{
				$num_variantsWords=count($this->blacklist[$i]['VARIANTS']);
				for($j=0;$j<$num_variantsWords;$j++){
					$pattern="/".$this->blacklist[$i]['VARIANTS'][$j]."/i";
					if(preg_match($pattern,$text)) {
						$find_badword=true;
						//echo "ENTRA2 CHECK: ".$this->blacklist[$i]['VARIANTS'][$j]."<br>";
						$list_word_found[]=$this->blacklist[$i]['VARIANTS'][$j];
					}
				}					
			}
		}
		if($find_badword){
			return $list_word_found;
		}else{
			return true;
		}
		
    }             
    /**
     * getBlackList
     */	
    function getBlackList(){
    	return $this->blacklist;
    }
    /**
     * getBlackListElement
     */	    
    function getBlackListElement($key){
    	return $this->blacklist[$key];
    }    
    /**
     * getVariant
     */	          
    function getVariant($key){
    	return $this->blacklist[$key]['VARIANTS'];
    }        	                    
}

?>