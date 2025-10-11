<?php
//Rev 2 - Add strip_tags to remove html tags on $_POST['format'] - (23/11/2016)
//Rev 1 - Release version
$data = json_decode($_POST['filters'], true);
if (strip_tags($_POST['format']) == 'CSV') {
$file_name = 'artica_' . date('Ymd_His') . '.csv';
convert_to_csv($data, $file_name);
} 
else if (strip_tags($_POST['format']) == 'Excel') {
$file_name = 'artica_' . date('Ymd_His') . '.xls';
convert_to_excel($data, $file_name);
}

function convert_to_csv($input_array, $output_file_name, $delimiter = ',') {
    $temp_memory = fopen('php://memory', 'w');
	$firstLineKeys = false;
	foreach ($input_array as $line) {
	 if (empty($firstLineKeys)) {
		$firstLineKeys = array_keys($line);
		fputcsv($temp_memory, $firstLineKeys);
		$firstLineKeys = array_flip($firstLineKeys);
	 }
	 fputcsv($temp_memory, array_merge($firstLineKeys, $line));
	}
    fseek($temp_memory, 0);
    header('Content-Type: application/csv');
    header('Content-Disposition: attachement; filename="' . $output_file_name . '";');
    fpassthru($temp_memory);
}
    

function convert_to_excel($input_array, $output_file_name, $delimiter = ',') {
    $temp_memory = fopen('php://memory', 'w');
    $firstLineKeys = false;
    fwrite($temp_memory, "sep=,\n");
	foreach ($input_array as $line) {
     if (empty($firstLineKeys)) {
        $firstLineKeys = array_keys($line);
        fputcsv($temp_memory, $firstLineKeys);
        $firstLineKeys = array_flip($firstLineKeys);
     }
     fputcsv($temp_memory, array_merge($firstLineKeys, $line));
    }
    fseek($temp_memory, 0);
	$xls = stream_get_contents($temp_memory);
	$xls = mb_convert_encoding($xls, 'UTF-16LE', 'UTF-8');
	header('Content-type: application/vnd.ms-excel;charset=UTF-16LE');
    header('Content-Disposition: attachment; filename='.$output_file_name);
    header("Cache-Control: no-cache");
	echo $xls;
}
?>
