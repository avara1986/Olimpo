<?
 /**
 * OlimpoBaseSystem
 * @version 2.7.1
 * @author Alberto Vara (C) Copyright 2012
 * @package gobalo.OlimpoBaseSystem
 */
interface  datesClass {	
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
	/**
	 * formatDateToTPL
	 * Da formato a una fecha para insertarla en TPLs/HTMLs
	 * @param string formatDateToTPL
	 */	
	function formatDateToTPL($date) {
		$f_dia=substr($date, 8,2);
		$f_mes=substr($date, 5,2);
		$f_anio=substr($date, 0,4);
		$fecha_formated=$f_dia."/".$f_mes."/".$f_anio;
		return $fecha_formated;
	}
	/**
	 * formatDateToTPL_beauty
	 * Da formato a una fecha para insertarla en TPLs/HTMLs con un formato "bonito"
	 * @param string formatDateToTPL
	 */	
	function formatDateToTPL_beauty($date,$s_dayWeek=true,$s_monthText=true,$sep=" de "){
		//echo "<br>fecha:".$date."<br>";
		$dia = date('j', strtotime($date));
		$date_beautifull = date('d', strtotime($date));
		//echo "<br>Día:".$date_beautifull;
		$dia_semana_num = date('N', strtotime($date));
		$dia_mes_num = date('n', strtotime($date));
		//echo "<br>día de la semana".$date_beautifull."<br>";
		if($s_dayWeek===true){
			switch ($dia_semana_num) {
			    case 1:
			        $dia_semana_text="Lunes";
			        break;
			    case 2:
			        $dia_semana_text="Martes";
			        break;
			    case 3:
			        $dia_semana_text="Miércoles";
			        break;
			    case 4:
			        $dia_semana_text="Jueves";
			        break;
			    case 5:
			        $dia_semana_text="Viernes";
			        break;
			    case 6:
			        $dia_semana_text="Sábado";
			        break;
			    case 7:
			        $dia_semana_text="Domingo";
			        break;		        		        		        		        
			}		
		}else{
			$dia_semana_text="";
		}
		if($s_monthText===true){
			switch ($dia_mes_num) {
			    case 1:
			        $dia_mes_text="Enero";
			        break;
			    case 2:
			        $dia_mes_text="Febrero";
			        break;
			    case 3:
			        $dia_mes_text="Marzo";
			        break;
			    case 4:
			        $dia_mes_text="Abril";
			        break;
			    case 5:
			        $dia_mes_text="Mayo";
			        break;
			    case 6:
			        $dia_mes_text="Junio";
			        break;
			    case 7:
			        $dia_mes_text="Julio";
			        break;
			    case 8:
			        $dia_mes_text="Agosto";
			        break;		
			    case 9:
			        $dia_mes_text="Septiembre";
			        break;		
			    case 10:
			        $dia_mes_text="Octubre";
			        break;		
			    case 11:
			        $dia_mes_text="Noviembre";
			        break;		
			    case 12:
			        $dia_mes_text="Diciembre";
			        break;				        
			}			
		}else{
			$dia_mes_text="";
		}
		return 	$dia_semana_text." ".$dia.$sep.$dia_mes_text;
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
}
?>