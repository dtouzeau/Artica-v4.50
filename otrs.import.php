<?php
include_once("/usr/share/artica-postfix/ressources/class.mail.inc");
include_once("/usr/share/artica-postfix/ressources/smtp/class.smtp.loader.inc");
include_once("/usr/share/artica-postfix/ressources/smtp/class.smtp.loader.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

//$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);


$q=new mysql();

$sql="SELECT email FROM swuseremailstmp";
$results=$q->QUERY_SQL($sql,"articafr10");
@chdir("/home/otrs/bin");


$subject="[Artica]: support tracking system as changed";

while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
	if(isset($AL[$ligne["email"]])){continue;}
	$AL[$ligne["email"]]=true;
	if(CustomerID($ligne["email"])>0){continue;}
	
	$t=explode("@", $ligne["email"]);
	$username=$t[0];
	$password=md5($username);
	$cmd="./otrs.AddCustomerUser.pl -f $username -l $username -p $password -e {$ligne["email"]} $username";
	shell_exec($cmd);
	if(CustomerID($ligne["email"])==0){
		echo $ligne["email"]." FAILED\n";
		continue;}
	echo "{$ligne["email"]} DONE\n";
	$f=array();
	$f[]="Dear customer ($username)";
	$f[]="Our Website www.artica.fr have been blocked by ";
	$f[]="our Hosting Service Provider ( www.infomaniak.com ) for unjustified reasons.";
	$f[]="We have decided to move our tracking system on our servers instead.";
	$f[]="But during the migration, tracking data has been lost.";
	$f[]="We have imported your account but all tickets cannot be retreived.";
	$f[]="In this case, if you have still an open ticket, we ask you to reset your password using this form:";
	$f[]="";
	$f[]="\thttp://support.artica.fr/otrs/customer.pl#Reset";
	$f[]="";
	$f[]="And create the ticket trough a new tracker system.";
	$f[]="We apologize for the inconvenience";
	$f[]="Best regards";
	$f[]="";
	$f[]="\tArtica support team";
	$f[]="";
	$f[]="************************************************************";
	$f[]="";
	$f[]="Cher client,";
	$f[]="Notre hébergeur de site Internet ( www.infomaniak.com )";
	$f[]="a bloqué notre site web pour des raisons injustifées.";
	$f[]="Nous avons donc décidé de rappatrier le système de support sur nos serveurs.";
	$f[]="Lors de la migration, les données du système de support ont étés corrompues.";
	$f[]="Nous avons importé votre compte mais les tickets n'ont pas put être recupérés.";
	$f[]="Si vous disposez de tickets toujours ouverts, nous vous invitons à vous connecter sur:";
	$f[]="";
	$f[]="\thttp://support.artica.fr/otrs/customer.pl#Reset";
	$f[]="";
	$f[]="Afin de reinitialiser votre mot de passe et à entrer dans le système";
	$f[]="Une fois cette opération effectuée, vous serez en mesure de réécrire votre demande de support";
	$f[]="";
	$f[]="Nous nous excusons de ce désagrément.";
	$f[]="";
	$f[]="Cordialement,";
	$f[]="L'équipe de support Artica.";
	sendEmail($subject, @implode("\n", $f),$ligne["email"]);
	
	
}



function CustomerID($email){
	
	
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT id FROM customer_user WHERE `email`='$email'","otrs"));
	if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
	return $ligne["id"];
}

function sendEmail($subject,$content=null,$recipient){
	$unix=new unix();

	$hostname="ks220503.kimsufi.com";
	$mailfrom="support@articatech.com";
	
	$TargetHostname="37.187.142.164";
	$params["helo"]=$hostname;
	$params["host"]=$TargetHostname;
	$params["do_debug"]=true;
	$params["debug"]=true;
	$params["auth"]=true;
	$params["user"]="david";
	$params["pass"]="123David456Touzeau";

	$smtp=new smtp($params);

	if(!$smtp->connect($params)){
		smtp::events("Error $smtp->error_number: Could not connect to `$TargetHostname` $smtp->error_text",__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	$random_hash = md5(date('r', time()));
	$boundary="$random_hash/$hostname";
	$content=str_replace("\r\n", "\n", $content);
	$content=str_replace("\n", "\r\n", $content);
	$body[]="Return-Path: <$mailfrom>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $mailfrom (robot)";
	$body[]="Subject: $subject";
	$body[]="To: $recipient";
	$body[]="MIME-Version: 1.0";
	$body[]="Content-Type: text/plain; charset=\"UTF-8\"";
	$body[]="Content-Transfer-Encoding: 8bit";
	$body[]="Envelope-To: <$recipient>";
	$body[]="";
	$body[]=$content;
	$body[]="";
	$finalbody=@implode("\r\n", $body);

	if(!$smtp->send(array(
			"from"=>"$mailfrom",
			"recipients"=>$recipient,
			"body"=>$finalbody,"headers"=>null)
	)
	){
		smtp::events("Error $smtp->error_number: Could not send to `$TargetHostname` $smtp->error_text",__FUNCTION__,__FILE__,__LINE__);
		$smtp->quit();
		return;
	}

	smtp::events("Success sending message trough [{$TargetHostname}:25]",__FUNCTION__,__FILE__,__LINE__);
	$smtp->quit();


}

