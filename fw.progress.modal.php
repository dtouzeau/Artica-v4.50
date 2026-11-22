<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');
include_once(dirname(__FILE__).'/ressources/class.progress.sign.inc');

if(isset($_GET["serialize"])){popup();exit;}

zdefault();
# v10/10/2026
// Same task links as fw.progress.php: signed by fw_progress_args(), checked here BEFORE
// decoding, then signed again once AFTER/AFTER-FAILED are completed.
function zdefault(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $content=fw_progress_b64_normalize(isset($_GET["content"])?$_GET["content"]:"");
    $sig=isset($_GET["sig"])?strtolower(trim((string)$_GET["sig"])):"";
    if($content==="" || !fw_progress_verify($content,$sig,"task")){
        fw_progress_refuse("modal content");
        return;
    }
    echo "
    $('#modal-windows').load('$page?serialize=$content&sig=$sig',function(){
        $('#modal-windows').modal({clickClose: false});
    });";


}

function popup(){
    $id=md5(time());
    $content=fw_progress_b64_normalize($_GET["serialize"]);
    $sig=isset($_GET["sig"])?strtolower(trim((string)$_GET["sig"])):"";
    if($content==="" || !fw_progress_verify($content,$sig,"task")){
        fw_progress_refuse("modal serialize");
        return;
    }
    $ARRAY=fw_progress_decode($content);
    if(!isset($ARRAY["AFTER"])){$ARRAY["AFTER"]="";}
    if(!isset($ARRAY["TITLE"])){$ARRAY["TITLE"]="";}
    $ARRAY["AFTER"]="{$ARRAY["AFTER"]};$('#modal-windows').modal('toggle');";
    $ARRAY["AFTER-FAILED"]="$('#modal-windows').modal('toggle');jsProgressAfterFailed();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=$id')";
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