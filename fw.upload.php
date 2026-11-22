<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
if(isset($_GET["btid"])){out();exit;}
if(isset($_FILES["files"]["name"])){$upload_handler = new UploadHandler(null,true,upload_error_messages());exit;}

// Messages traduits pour le gestionnaire. SweetAlert echappe le HTML du texte :
// on decode les entites (&#233;) pour ne pas les afficher telles quelles.
function upload_error_messages():array{
    $tpl=new template_admin();
    $msg=html_entity_decode($tpl->_ENGINE_parse_body("{upload_no_disk_space}"),ENT_QUOTES,"UTF-8");
    if(strlen(trim($msg))==0 || strpos($msg,"{upload_no_disk_space}")!==false){return array();}
    return array("disk_full"=>$msg);
}


function out(){
    $btid=$_GET["btid"];
    $Uploading=$_GET["up"];
    $currentpage=$_GET["page"];
    $ffname=$_GET["ffname"];
    $label=$_GET["label"];
    $tpl=new template_admin();
    $UploadFailed=$tpl->javascript_parse_text("{failed}");
    $bts="
    if(!document.getElementById('fileupload$btid')){
        alert('fileupload$btid not found!');
    }
    
    
    $('#fileupload$btid').fileupload({
    	url: '/fw.upload.php',
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
        	document.getElementById('$btid').innerHTML='$label';
        	// files[0].name est renvoye meme quand l'enregistrement a echoue
        	// (disque plein -> error:\"Failed to write file to disk\"): sans ce test
        	// on enchaine sur un fichier inexistant.
        	var uploaded=(data.result && data.result.files) ? data.result.files[0] : null;
        	if(uploaded==null || uploaded.error){
        		swal({title:'$UploadFailed',text:(uploaded && uploaded.error)?uploaded.error:'$UploadFailed',type:'error',confirmButtonColor:'#ed5565'});
        		return;
        	}
        	// encodeURIComponent: a '+', '&', '#' or '%' in the file name used to reach the page altered.
        	Loadjs('$currentpage?$ffname='+encodeURIComponent(uploaded.name));
    	}
    });
	";
    echo $bts;
}