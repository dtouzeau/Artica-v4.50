<?php

header("content-type: application/x-javascript");
$id=$_GET["id"];
$leave=0;
if(isset($_GET["leave"])){
    $leave=1;
}

echo "
var leave=$leave;
if(leave==1){
    // Switch ON/OFF button List
    var element = document.getElementById('$id');
    if (element.classList.contains('open')) {
        element.classList.remove('open');
    }
}
";
if(!isset($_GET["clean"])) {
        echo "var element = document.getElementById('$id');
    if (element.classList.contains('open')) {
        $('div.input-group').hide();
    }else{
     $('div.input-group').show();
     }
    ";
}

