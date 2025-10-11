<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET");
if(isset($_GET['site'])){
    getCatz($_GET['site']);
}
function getCatz($cat){
exec("php exec.api.categories.php --cat $cat",$result);
echo $result[0];
}
