<?php
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');

if(isset($_GET["serialize"])){popup();exit;}

zdefault();

function zdefault(){
    $page=CurrentPageName();

    $content=$_GET["content"];
    header("content-type: application/x-javascript");
    echo "
    $('#modal-windows').load('$page?serialize=$content',function(){
        $('#modal-windows').modal({clickClose: false});
    });";


}

function popup(){
    $id=md5(time());
    $ARRAY=unserialize(base64_decode($_GET["serialize"]));
    $ARRAY["AFTER"]="{$ARRAY["AFTER"]};$('#modal-windows').modal('toggle');";
    $ARRAY["AFTER-FAILED"]="$('#modal-windows').modal('toggle');jsProgressAfterFailed();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=$id')";
    $html=
    "<div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <i class='".ico_cd." modal-icon center'></i><hr>
                <H3>{$ARRAY["TITLE"]}</H3>
            </div>
            <div class='modal-body'>
           
            <div id='$id' style='width: 100%;'></div>
                
            </div>
        </div>
    </div>
<script>
$jsrestart
    
    
</script>";
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);

}