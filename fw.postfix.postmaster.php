<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");

if(isset($_GET["template-edit"])){template_js();exit;}
if(isset($_GET["template-popup"])){template_popup();exit;}

if(isset($_POST["luser_relay"])){save();exit;}
if(isset($_POST["template"])){save_template();exit;}

page();

function template_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tplkey=$_GET["template-edit"];
    $instance_id=intval($_GET["instance-id"]);
    $tpl->js_dialog1("{template} {{$tplkey}}","$page?template-popup=$tplkey&instance-id=$instance_id");
}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instance_id=intval($_POST["instance-id"]);
    $main=new maincf_multi($instance_id);

    $not[]="bounce";
    $not[]="2bounce";
    $not[]="policy";
    $not[]="protocol";
    $not[]="resource";
    $not[]="software";
    $FNOT=array();
    foreach ($not as $class){
        if($_POST[$class]==1){$FNOT[]=$class;}
        unset($_POST[$class]);
    }

    foreach ($_POST as $index=>$val){
        $main->SET($index,$val);

    }

    if(count($FNOT)==0){$FNOT[]="resource";}

    $main->SET("notify_classes",@implode(",",$FNOT));

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $main=new maincf_multi($instance_id);


    $PostfixPostmaster=trim($main->GET("PostfixPostmaster"));
    if($PostfixPostmaster==null){$PostfixPostmaster="MAILER-DAEMON";}
    $luser_relay=trim($main->GET("luser_relay"));
    $double_bounce_sender=$main->GET("double_bounce_sender");
    $address_verify_sender=$main->GET("address_verify_sender");
    $twobounce_notice_recipient=$main->GET("2bounce_notice_recipient");
    $error_notice_recipient=$main->GET("error_notice_recipient");
    $delay_notice_recipient=$main->GET("delay_notice_recipient");
    $empty_address_recipient=$main->GET("empty_address_recipient");

    if($double_bounce_sender==null){$double_bounce_sender="double-bounce";};
    if($address_verify_sender==null){$address_verify_sender="\$double_bounce_sender";}
    if($twobounce_notice_recipient==null){$twobounce_notice_recipient="postmaster";}
    if($error_notice_recipient==null){$error_notice_recipient=$PostfixPostmaster;}
    if($delay_notice_recipient==null){$delay_notice_recipient=$PostfixPostmaster;}
    if($empty_address_recipient==null){$empty_address_recipient=$PostfixPostmaster;}

    $notify_classes=$main->GET("notify_classes");
    if($notify_classes==null){$notify_classes="resource,software";}

    $notify_classes_td=explode(",",$notify_classes);
    foreach ($notify_classes_td as $class){
        $notify_classes_main[$class]=1;
    }


    $form[]=$tpl->field_hidden("instance-id",$instance_id);
    $form[]=$tpl->field_email("PostfixPostmaster", "{postmaster}", $PostfixPostmaster,false,"{postmaster_text}");
    $form[]=$tpl->field_email("luser_relay","{unknown_users}",$luser_relay,false,"{postfix_unknown_users_tinytext}");
    $form[]=$tpl->field_email("double_bounce_sender","{double_bounce_sender}",$double_bounce_sender,false,"{double_bounce_sender_text}");
    $form[]=$tpl->field_email("address_verify_sender","{address_verify_sender}",$address_verify_sender,false,"{address_verify_sender_text}");
    $form[]=$tpl->field_email("2bounce_notice_recipient","{2bounce_notice_recipient}",$twobounce_notice_recipient,false,"{2bounce_notice_recipient_text}");
    $form[]=$tpl->field_email("error_notice_recipient","{error_notice_recipient}",$error_notice_recipient,false,"{error_notice_recipient_text}");
    $form[]=$tpl->field_email("delay_notice_recipient","{delay_notice_recipient}",$delay_notice_recipient,false,"{delay_notice_recipient_text}");
    $form[]=$tpl->field_email("empty_address_recipient","{empty_address_recipient}",$empty_address_recipient,false,"{empty_address_recipient_text}");

    $form[]=$tpl->field_section("{notify_class}");

    $not[]="bounce";
    $not[]="2bounce";
    $not[]="policy";
    $not[]="protocol";
    $not[]="resource";
    $not[]="software";

    foreach ($not as $class){
        $form[]=$tpl->field_checkbox($class,"{notify_class_{$class}}",$notify_classes_main[$class],false,"{notify_class_{$class}_text}");


    }



    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/build_progress_postmaster";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/build_progress_postmaster.txt";
	$ARRAY["CMD"]="postfix.php?postmaster=yes";
	$ARRAY["TITLE"]="{reconfigure}";
	$ARRAY["AFTER"]="";
	$prgress=base64_encode(serialize($ARRAY));
	$reconfigure="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-mainconf')";



	$form_final=$tpl->form_outside("{postmaster}", $form,"{POSTFIX_SMTP_NOTIFICATIONS_TEXT}","{apply}",$reconfigure,"AsPostfixAdministrator");


	$html[]="<table style='width:100%;margin-top:15px'>";

	$html[]="<tr>";
    $html[]="<td valign='top' style='width:2%;vertical-align:top'>";
    $html[]="<table style='width:100%;margin-top:0px'>";

    $main=new bounces_templates();
	foreach ($main->templates_array as $key_template=>$none){


        $tplts=$tpl->td_href("{template}:&nbsp;{{$key_template}}",null,"Loadjs('$page?template-edit=$key_template&instance-id=$instance_id')");
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><i class=\"fas fa-envelope-open-text\"></i>&nbsp;&nbsp;</td>";
        $html[]="<td style='width:99%' nowrap>$tplts</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="</td>";
    $html[]="<td valign='top' style='padding-left:20px'>$form_final</td>";
    $html[]="</tr>";
    $html[]="</table>";

    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }

    //$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{postmaster}/{templates} <small>$instancename</small>";
    $TINY_ARRAY["ICO"]="fa-solid fa-file-code";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_TEXT}";
    $TINY_ARRAY["URL"]="instance-postffix-settings-$instance_id";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo "<script>$jstiny</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function template_popup(){
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $template=$_GET["template-popup"];
    $mainTPL=new bounces_templates();
    $main=new maincf_multi($instance_id);
    $array=unserializeb64($main->GET_BIGDATA($template));
    if(!is_array($array)){$array=$mainTPL->templates_array[$template];}

    $form[]=$tpl->field_hidden("instance-id",$instance_id);
    $form[]=$tpl->field_hidden("template",$template);
    $form[]=$tpl->field_charset("zCharset","Charset",$array["Charset"],true);
    $form[]=$tpl->field_text("From","{mail_from}",$array["From"],true);
    $form[]=$tpl->field_text("Subject","{subject}",$array["Subject"],true);
    $form[]=$tpl->field_text("PostmasterSubject","Postmaster-Subject",$array["Postmaster-Subject"],true);
    $form[]=$tpl->field_textareacode("Body","{content}",$array["Body"]);



    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/build_progress_postmaster";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/build_progress_postmaster.txt";
    $ARRAY["CMD"]="postfix.php?postmaster=yes&instance-id=$instance_id";
    $ARRAY["TITLE"]="{reconfigure}";
    $ARRAY["AFTER"]="";
    $prgress=base64_encode(serialize($ARRAY));
    $reconfigure="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-mainconf')";

    $form_final=$tpl->form_outside("{template}: {{$template}}", $form,null,"{apply}","dialogInstance1.close();$reconfigure","AsPostfixAdministrator");

    echo $form_final;
}

function save_template(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $template=$_POST["template"];
    $instance_id=intval($_POST["instance-id"]);
    $_POST["Charset"]=$_POST["zCharset"];
    $_POST["Postmaster-Subject"]=$_POST["PostmasterSubject"];
    $main=new maincf_multi($instance_id);
    $main->SET_BIGDATA($template,base64_encode(serialize($_POST)));

}