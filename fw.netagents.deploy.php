<?php
// Network Agents Management Page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["step2"])){step2();exit;}
if(isset($_POST["remote"])){step_deploy();exit;}
if(isset($_GET["progress"])){build_progress();exit;}
js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog10("{deploy_agent}","$page?step1=yes&function=$function",650);
}
function step1():bool{
    $page=CurrentPageName();
    $t=time();
    $function=$_GET["function"];
    echo "<div id='step1-$t'></div>\n";
    echo "<script>LoadAjax('step1-$t','$page?step2=$t&function=$function');</script>";
    return true;
}
function step2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=$_GET["step2"];
    $function=$_GET["function"];
    $html[]="<div id='progress-deploy-agent'></div>";
    $form[]=$tpl->field_text("remote","{remote_server}","");
    $form[]=$tpl->field_numeric("sshport","{ssh_port}","22");
    $form[]=$tpl->field_numeric("agentport","{agent_port} ({optional})","28811");
    $form[]=$tpl->field_text("root","{username}","root");
    $form[]=$tpl->field_password("rootpassword","{password}","");
    $form[]=$tpl->field_text("network","{TrustMyNetwork} ({optional})","");
    $js=$tpl->RefreshInterval_Loadjs("progress-deploy-agent",$page,"progress=$t&function=$function");

    $html[]=$tpl->form_outside("",$form,"{deploy_agent_ssh_explain}","{deploy}","","AsSystemAdministrator");
    $html[]="<script>$js</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function build_progress():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();


    $html=array();
    $sock=new sockets();
    $jsons=json_decode($sock->REST_API("/netagents/deploy/list"));
    if (!is_object($jsons)) {
        echo "$('#progress-deploy-agent').html( '' );\n";
        echo "// invalid json response\n";
        return false;
    }

    if (!property_exists($jsons, "deployments") || !is_array($jsons->deployments)) {
        echo "$('#progress-deploy-agent').html( '' );\n";
        echo "// deployments no such attr or not array\n";
        return false;
    }

    $Count = isset($jsons->count) ? (int)$jsons->count : count($jsons->deployments);
    if ($Count === 0) {
        echo "$('#progress-deploy-agent').html( '' );\n";
        echo "// Count=$Count\n";
        return false;
    }

    $faileds=array();
    $Count=$jsons->count;
    if($Count==0) {
        echo "// Count=$Count\n";
        echo "$('#progress-deploy-agent').html( '' );\n";
        return false;
    }

    foreach ($jsons->deployments as $json){

        if (!is_object($json)) {
            continue;
        }
        if(!property_exists($json,"progress")){
            continue;
        }
        $prc=$json->progress;
        $target_ip=$json->target_ip;
        $message="$target_ip: $json->message";
        $state=$json->state;
        $completed_at=$tpl->time_to_date($tpl->GoToTimestamp($json->completed_at),true);
        if($prc==100){
            $sock->REST_API("/netagents/deploy/remove/$json->id");
            if($state=="failed"){
                squid_admin_mysql_netagents(0,"{failed} {deploy_agent} $target_ip","Time:$completed_at<br>$message",__FUNCTION__,__FILE__,__LINE__);
            }else{
                squid_admin_mysql_netagents(1,"{success} {deploy_agent} $target_ip","Time:$completed_at<br>$message",__FUNCTION__,__FILE__,__LINE__);
            }
        }



        if($state=="failed"){
            $faileds[]=$tpl->div_error($target_ip."||".$json->error);
            continue;
        }

        $html[]=$tpl->GetProgressBarr($message,$prc);


    }
    if(count($html)==0 && count($faileds)==0){
        echo "$('#progress-deploy-agent').remove();\n";
        echo "dialogInstance10.close();\n";
        echo "$function();";
        return true;
    }
    if(count($faileds)>0){
        $final=@implode("\n",$faileds).@implode("\n",$html);
    }else{
        $final=@implode("\n",$html);
    }

    $b64=base64_encode($final);
    echo "$('#progress-deploy-agent').html( base64_decode('$b64') );\n";
    return true;
}

function step_deploy():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array["target_ip"]=$_POST["remote"];
    $array["target_port"]=intval($_POST["sshport"]);
    $array["username"]=$_POST["root"];
    $array["password"]=$_POST["rootpassword"];
    $array["agent_port"]=intval($_POST["agentport"]);
    $sock=new sockets();
    $json=json_decode($sock->REST_API_POST("/netagents/deploy",$array));
    if(!property_exists($json,"id")){
        if(property_exists($json,"Status")){
            if(!$json->Status){
                $tpl->post_error($json->Error);
                return false;
            }
        }
    }

    return admin_tracks("Deploy debian-agent on target {$array["target_ip"]} via SSH");
}
function squid_admin_mysql_netagents($severity, $subject, $text,$function,$file,$line){
    $zdate=time();
    $q2=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $text=str_replace("'","`",$text);
    $subject=$q2->sqlite_escape_string2($subject);
    $text=$q2->sqlite_escape_string2($text);


    $file=basename($file);
    $q2->QUERY_SQL("INSERT OR IGNORE INTO `squid_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
			('$zdate','$text','$subject','$function','$file','$line','$severity')","artica_events");
    if(!$q2->ok){writelogs("SQL ERROR $q2->mysql_error",__FUNCTION__,__FILE__,__LINE__);}

}