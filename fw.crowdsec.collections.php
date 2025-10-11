<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["collection-remove"])){collection_remove_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["collection-remove"])){collection_remove_perform();exit;}

table_start();

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='crowdsec-collections'></div><script>LoadAjax('crowdsec-collections','$page?table=yes');</script>";
    return true;
}
function collection_remove_js():bool{
    $page=CurrentPageName();
    $name=$_GET["collection-remove"];
    $tpl=new template_admin();
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("{collection}:$name","collection-remove",$name,"$('#$md').remove()");
}
function collection_remove_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $nameEncoded=urlencode($_POST["collection-remove"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?collection-remove=$nameEncoded");
    return admin_tracks("Remove CrowdSec collection name {$_POST["collection-remove"]}");
}
function collection_enabled_js():bool{
    $CROWDSEC_COLLECTIONS=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_COLLECTIONS"));
    $results=json_decode($CROWDSEC_COLLECTIONS);
    $name=$_GET["name"];
    $nameEncoded=urlencode($name);
    $status=0;
    foreach ($results->collections as $index=>$collection){
        $nameSrc=$collection->name;
        if(strtolower($nameSrc)==strtolower($name)){
            $status=$collection->status;
            break;
        }
    }

    if($status==0){
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?collection-enable=$nameEncoded");
    }else{
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?collection-disable=$nameEncoded");
    }
    return true;
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();



    $html[]="			<table class='table table-striped' style='margin-top:20px'>";
    $html[]="				<thead>";
    $html[]="					<tr>";
    $html[]="						<th nowrap>{name}</th>";
    $html[]="                        <th>{version}</th>";
    $html[]="                        <th>{description}</th>";
    $html[]="						<th nowrap>{delete}</th>";
    $html[]="						<th></th>";
    $html[]="					</tr>";
    $html[]="				</thead>";
    $html[]="				<tbody>";

    $results=array();
    $CROWDSEC_COLLECTIONS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_COLLECTIONS");
    $CROWDSEC_COLLECTIONS_ERROR=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_COLLECTIONS_ERROR");

     if(strlen($CROWDSEC_COLLECTIONS_ERROR)>2){
         echo $tpl->div_error($CROWDSEC_COLLECTIONS_ERROR);
     }

    if(strlen($CROWDSEC_COLLECTIONS)<10){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/collection/list");
        $CROWDSEC_COLLECTIONS_ERROR=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_COLLECTIONS_ERROR");
        if(strlen($CROWDSEC_COLLECTIONS_ERROR)>2){
            echo $tpl->div_error($CROWDSEC_COLLECTIONS_ERROR);
        }
    }

    if(strlen($CROWDSEC_COLLECTIONS)>10){
        $results=json_decode($CROWDSEC_COLLECTIONS);
        if (json_last_error()> JSON_ERROR_NONE) {
            echo $tpl->div_error(json_last_error_msg());
            return true;
        }
        if(!property_exists($results,"collections")){
            echo $tpl->div_error("Collection, no property");
            return true;
        }
    }
    $TRCLASS=null;
    $translate_name["crowdsecurity/sshd"]="{APP_OPENSSH}";
    $translate_name["crowdsecurity/linux"]="{OS}";
    $translate_name["crowdsecurity/suricata"]="{IDS}";
    $translate_name["crowdsecurity/nginx"]="{APP_NGINX}";
    $translate_name["crowdsecurity/base-http-scenarios"]="{APP_NGINX} (Base HTTP)";
    $translate_name["crowdsecurity/http-cve"]="{APP_NGINX} (CVE)";
    foreach ($results->collections as $index=>$collection){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $StatusInt=0;
        $md=md5(serialize($collection));
        $name=$collection->name;
        $nameencode=urlencode($name);
        $description=$collection->description;
        $description=str_ireplace("suricata","{IDS}",$description);
        $description=str_ireplace("nginx","{APP_NGINX}",$description);
        $status=$collection->status;
        if($status=="enabled"){$StatusInt=1;}
        $version=$collection->local_version;
        $nameText=$name;
        $remove=$tpl->icon_delete("Loadjs('$page?collection-remove=$nameencode&md=$md')","AsFirewallManager");
        if(isset($translate_name[$name])){
            $nameText=$translate_name[$name];
            $remove=$tpl->icon_nothing();
        }



        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' class='left' nowrap><li class='".ico_file_zip."'></li>&nbsp;$nameText</td>";
        $html[]="<td style='width:1%' class='text-right' nowrap>$version</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$description</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$remove</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";

    $topbuttons=array();
    $TINY_ARRAY["TITLE"]="{collections}";
    $TINY_ARRAY["ICO"]=ico_books;
    $TINY_ARRAY["EXPL"]="{crowdsec_collections}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>$jstiny</script>";


    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
