<?
/**
 * cronos_Time
 * @version Beta 0.8.1
 * @author Alberto Vara (C) Copyright 2011
 * @package gobalo.cronos_Time
 */
class cronos_Time {
	/** *
	 * $start_time
	 * Hora en la que se ha empezado a calcular
	 */
	private $start_time;
	/** *
	 * $times
	 * Array de las horas en las que se ha ido marcando puntos
	 */
	private $times;
	/** *
	 * $round_time
	 * Número de décimas de unidad para calcular la diferencia de tiempos
	 */
	private $round_time=4;
	/**
	 * getActualTime
	 * Empieza a contar los hitos de cálculo de tiempo.
	 */
	private function getActualTime(){
		$time = 0;
		$time = round(microtime(true),$this->round_time);
		return $time;
	}
	/**
	 * getActualTime
	 * Empieza a contar los hitos de cálculo de tiempo.
	 */
	private function getActualDate($format='sql'){
		$date = getdate(mktime(0,0,0,$month,1,$year));
		return $date;
	}
	/**
	 * startTimeCount
	 * Empieza a contar los hitos de cálculo de tiempo.
	 */
	function startTimeCount(){
		$this->start_time = $this->getActualTime();
		$this->times[]=array(
       		'TIME'		=>	$this->getActualTime(),
			'NAME'		=>	'[CRONO_START] Inicio de Cálculo de tiempo.',
			'DIFF'		=>	0,
       		'MEMORY'	=>	memory_get_usage()
		);
	}
	/**
	 * setTimeCount
	 */
	function setTimeCount($name_milestone=""){
		$last_time=array();
		if(is_array($this->times)){
			$last_time=end($this->times);
		}
		$diferencia_ultimo_hito = round($this->getActualTime() - $last_time['TIME'], $this->round_time);
		if($name_milestone=="" || strlen($name_milestone)==0){
			$name_milestone = "[CRONO_START] Hito sin definir desde: ".$_SERVER ["REQUEST_URI"].".";
		}
		$this->times[]=array(
			'TIME'		=>	$this->getActualTime(),
			'NAME'		=>	$name_milestone,
			'DIFF'		=>	$diferencia_ultimo_hito,
			'MEMORY'	=>	memory_get_usage()
		);
	}
	/**
	 * getTotalTime
	 */
	function getTotalTime($format="array"){
		$diferencia_ultimo_hito = round($this->getActualTime() - $this->start_time, $this->round_time);
		$this->times[]=array(
       		'TIME'		=>	microtime(true),
			'NAME'		=>	'[CRONO_END] Fin de Cálculo de tiempo.',
			'DIFF'		=>	$diferencia_ultimo_hito,
       		'MEMORY'	=>	memory_get_usage()
		);
		if($format=="array"){
			$result = $this->times;
		}elseif ($format=="print"){
			$times=$this->times;
			$num_milestone = $this->getNumMilestone();
			$result = "<pre>";
			for($i=0;$i<$num_milestone;$i++){
				$result .= "<p>* [".($i+1)."] [".$times[$i]['TIME']."]<b>HITO</b> ".$times[$i]['NAME']." || <b>DIFERENCIA CON HITO ANTERIOR: </b> ".$times[$i]['DIFF']." || <b>USO DE MEMORIA: </b> ".$times[$i]['MEMORY']."</p>";
			}
			$result .= "</pre>";
		}else{
			$result = false;
		}
		return $result;
	}
	/**
	 * getTotalTime
	 */
	function getNumMilestone(){
		if(is_array($this->times)){
			$result = count($this->times);
		}else{
			$result=false;
		}
		return $result;
	}
}

?>