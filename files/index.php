<?php
include("directoryinfo.inc.php");
$dirobj = new directory_info();
$files = $dirobj->get_sorted_filelist( false, true );
$num_files=count($files);
echo "<b>N&uacute;mero de ficheros: ".($num_files-2)."</b><br><br>";
for($i=0;$i<$num_files;$i++){
	if(strpos($files[$i], ".php")!==false){
		
	}else{
		echo "<a href='".$files[$i]."'>".$files[$i]."</a><br>";
	}
}
/*
function getFilesFromDir($dir) {

  $files = array();
  if ($handle = opendir($dir)) {
  	$i=0;
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != "..") {
            if(is_dir($dir.'/'.$file)) {
                $dir2 = $dir.'/'.$file;
                $files[]['NAME'] = getFilesFromDir($dir2);
            }
            else {
              $files[]['NAME'] = $dir.'/'.$file;
              $files[]['DATE'] = filectime($data_path . $file) . ',' . $file;
            }
        }
        $i++;
    }
    closedir($handle);
  }

  return array_flat($files);
}

function array_flat($array) {

  foreach($array as $a) {
    if(is_array($a)) {
      $tmp = array_merge($tmp, array_flat($a));
    }
    else {
      $tmp[] = $a;
    }
  }

  return $tmp;
}

// Usage
$dir = '.';
$foo = getFilesFromDir($dir);

print_r($foo);
*/
?>