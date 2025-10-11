<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["delete-ad"])){delete_ad_confirm();exit;}
if(isset($_POST["delete-ad"])){delete_ad_perform();exit;}

page();
function delete_ad_confirm(){
    $ID=intval($_GET["delete-ad"]);
    $DN=$_GET["dn"];
    $md=$_GET["md"];
    $tpl=new template_admin();
    $tpl->js_confirm_delete($DN ,"delete-ad",$ID,"$('#$md').remove()");

}

function delete_ad_perform(){
    $ID=intval($_POST["delete-ad"]);
    $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
    $q->QUERY_SQL("DELETE FROM adgroupsprivs WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error."\n";return;}
}

function page(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
    $sql="CREATE TABLE IF NOT EXISTS `adgroupsprivs` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, `DN` text UNIQUE, `content` TEXT NOT NULL )";
    $q->QUERY_SQL($sql);



    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:20px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{groups2}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $results=$q->QUERY_SQL("SELECT * FROM adgroupsprivs");

    $ldap=new clladp();
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $DN=$ligne["DN"];
        $DNEnc=urlencode($DN);$xprives=array();
        $content=base64_decode($ligne["content"]);
        $privs=$ldap->_ParsePrivieleges($content);
        foreach ($privs as $spriv=>$yes){

            if($yes<>"yes"){continue;}
            $xprives[]="{{$spriv}}";
        }


        $md=md5(serialize($ligne));

        $link="Loadjs('fw.groups.ad.php?dn=$DNEnc&function=blur')";
        $DN=$tpl->td_href($DN,null,$link);

        $del=$tpl->icon_delete("Loadjs('$page?delete-ad=$ID&md=$md&dn=$DNEnc')","AsSystemAdministrator");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"left\" nowrap>$DN<br><small>".@implode("<br>",$xprives)."</small></td>";
        $html[]="<td class=\"center\" width='1%'>$del</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);







}