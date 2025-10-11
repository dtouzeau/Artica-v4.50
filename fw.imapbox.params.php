<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ImapBoxSchedules"])){Save();exit;}

page();


function page(){
$page=CurrentPageName();
$html="
<div class=\"row border-bottom white-bg dashboard-header\">
<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_IMAPBOX} {parameters}</h1>
<p>{APP_IMAPBOX_EXPLAIN}</p>
</div>
</div>
<div class='row'><div id='progress-imapbox-restart'></div>
    <div class='ibox-content'>
        <div id='table-imapbox-parameters'></div>
    </div>
</div>
<script>
    $.address.state('/');
    $.address.value('/imapbackup-parameters');
    LoadAjax('table-imapbox-parameters','$page?table=yes');
</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_IMAPBOX} {parameters}",$html);
        echo $tpl->build_firewall();
        return;
    }

$tpl=new template_admin();
echo $tpl->_ENGINE_parse_body($html);

}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Timez[5]="5 {minutes}";
    $Timez[10]="10 {minutes}";
    $Timez[15]="15 {minutes}";
    $Timez[30]="30 {minutes}";
    $Timez[60]="1 {hour}";
    $Timez[120]="2 {hours}";
    $Timez[180]="3 {hours}";
    $Timez[360]="6 {hours}";
    $Timez[720]="12 {hours}";
    $Timez[1440]="1 {day}";
    $Timez[2880]="2 {days}";

    $ImapBoxSchedules=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxSchedules"));
    if($ImapBoxSchedules==0){$ImapBoxSchedules=120;}
    $ImapBoxDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDirectory");
    $ImapBoxDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBoxDays"));
    if($ImapBoxDays==0){$ImapBoxDays=365;}
    if($ImapBoxDirectory==null){$ImapBoxDirectory="/home/backup/imapbox";}

    $form[]=$tpl->field_array_hash($Timez,"ImapBoxSchedules","{fetchmails_back_each}","$ImapBoxSchedules");

    $form[]=$tpl->field_numeric("ImapBoxDays","{history_days_to_backup}",$ImapBoxDays);

   echo $tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",null,"AsMailBoxAdministrator");
}

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("imapbox?reconfigure=yes");

}