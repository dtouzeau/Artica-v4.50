<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}
js();


function uriext():string{
    $ext=array();
    if(isset($_GET["UseDN"])){$ext[]="UseDN=1";}
    if(isset($_GET["id"])){$ext[]="id={$_GET["id"]}";}
    if(isset($_GET["ReturnBack"])){$ext[]="ReturnBack={$_GET["ReturnBack"]}";}
    if(count($ext)==0){return "";}
    return @implode("&",$ext);
}

function js(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $ext=uriext();
    $_GET["id"]=urlencode($_GET["id"]);
    $tpl->js_dialog4("{search_ad_group}", "$page?popup=yes&$ext");

}

function popup(){
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ext=uriext();

    $html="<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>	
     </div>
     <div class='ibox-content' id='ad-$t-table'></div>
     
    <script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('ad-$t-table','$page?$ext&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

    echo $tpl->_ENGINE_parse_body($html);


}


function search(){
    $tpl=new template_admin();
    $q=new mysql_squid_builder();
    $groups=$tpl->_ENGINE_parse_body("{groups2}");
    $domain=$tpl->_ENGINE_parse_body("{domain}");
    $html=array();
    $html[]="<table id='table-wizardnew-objects' class=\"table table-hover\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>$groups</th>";
    $html[]="<th>$domain</th>";
    $html[]="<th></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $id=$_GET["id"];
    reset($q->acl_GroupType);
    $TRCLASS=null;
    $method=$tpl->_ENGINE_parse_body("{method}").":";
    $select=$tpl->_ENGINE_parse_body("{select}");
    if(!isset($_GET["search"])){$search="*";}else{$search=$_GET["search"];}
    if($search==null){$search="*";}
    if(strpos(" {$search}", "*")==0){$search="*{$search}*";}
    $search=str_replace("**", "*", $search);
    $search=str_replace("**", "*", $search);

    $ad=new external_ad_search();
    $Array=$ad->flexRTGroups($search,200);
    if(!is_array($Array)){$Array=array();}

    if(count($Array)==0){


        if(count($ad->TESTED_SERVERS)==0){
            $servers=" <span class='text-danger'>{error_no_server_specified}</span> ";
        }else{
            $servers=" {in} <strong>&laquo;".@implode(" {or} ",$ad->TESTED_SERVERS)."&raquo;</strong> ";
        }

        $html[]="<tr>";
        $html[]="<td colspan='4'>{your_search_results} <strong>&laquo;$search&raquo;</strong>{$servers}: <span class='text-danger'>{no_data}</span></td>";
        $html[]="</tr>";
        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='4'>";
        $html[]="<ul class='pagination pull-right'></ul>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</tfoot>";
        $html[]="</table>";

        echo $tpl->_ENGINE_parse_body($html);
        return;
    }

    ksort($Array);

    foreach ($Array as $dn => $samaccountname){
        $choose=null;
        $funct="Select".md5($dn);
        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"$funct();\">$select</button>";
        $trid=md5($dn);
        $dn_dom=array();
        $dn_temp=strtolower($dn);
        $dnexpl=explode(",",$dn_temp);
        foreach ($dnexpl as $item){
            if(!preg_match("#^dc=(.+)#", $item,$re)){continue;}
            $dn_dom[]=$re[1];
        }



        $domain=@implode(".", $dn_dom);
        $ValueToSend=$samaccountname;
        if(isset($_GET["UseDN"])){$ValueToSend=$dn;}
        $ValueToSendEnc=base64_encode($ValueToSend);


        if($id==null){
            $SelectFunction="function $funct(){  alert('No ID: defined');}";
        }else {
            $SelectFunction = "function $funct(){
        if(!document.getElementById('$id') ){
                alert('No ID: '+id);
                return false;
        }
        document.getElementById('$id').value=\"$ValueToSend\";
			dialogInstance4.close();
			NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
			}";
        }

        if(isset($_GET["ReturnBack"])){
            $ReturnBack=$_GET["ReturnBack"];
            $SelectFunction="function $funct(){
                $ReturnBack('$ValueToSendEnc');
			    dialogInstance4.close();
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			}";

        }

        $js[]=$SelectFunction;


        $html[]="<tr id='$trid'>";
        $html[]="<td class=\"center\" width=1% nowrap><i class='far fa-users'></i></td>";
        $html[]="<td nowrap>{$samaccountname}</span></td>";
        $html[]="<td nowrap>{$domain}</span></td>";
        $html[]="<td class=center width=1% nowrap>$choose</center></span></td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."";
    $html[]=@implode("\n", $js);
    //$html[]="$(document).ready(function() { $('#table-wizardnew-objects').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}