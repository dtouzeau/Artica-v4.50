<?php
if(is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
    echo header('location:/fw.login.php');
    exit;
}
include_once("/usr/share/artica-postfix/ressources/class.upload.handler.inc");
if(isset($_GET["btid"])){out();exit;}
if(isset($_FILES["files"]["name"])){$upload_handler = new UploadHandler();exit;}


function out():bool{
    $btid=$_GET["btid"];
    $Uploading=$_GET["up"];
    $currentpage=$_GET["page"];
    $ffname=$_GET["ffname"];
    $label=$_GET["label"];
    $bts="
    if(!document.getElementById('fileupload$btid')){
        alert('fileupload$btid not found!');
    }
    
    
    $('#fileupload$btid').fileupload({
    	url: '/wizard/wiz.upload.php',
        dataType: 'json',
        add: function (e, data) {
        	document.getElementById('$btid').innerHTML='$Uploading....';
            data.submit()
            
        },
    progressall: function (e, data) {
        var progress = parseInt(data.loaded / data.total * 100, 10);
        document.getElementById('$btid').innerHTML='$Uploading '+progress+'%';
 
    },        
        done: function (e, data) {
        	document.getElementById('$btid').innerHTML='$label'
        	Loadjs('$currentpage?$ffname='+data.result.files[0].name);
        	document.getElementById('fileupload$btid').value=data.result.files[0].name;
    	}
    });
	";
    echo $bts;
    return true;
}