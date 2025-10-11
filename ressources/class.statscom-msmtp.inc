<?php
include_once(dirname(__FILE__)."/class.smtpd.notifications.inc");
class statscom_msmtp{
	public $smtp_sender=null;
	public $recipient=null;
	public $Body=null;
	public $Subject=null;
	public $logs=null;
	public $smtp_error=null;
	public $debug=false;
	
	function __construct(){

	}
	

	
	public function Send($body=null,$attachment_path=null){
        if($body<>null){$this->Body=$body;}
        $this->recipient=str_replace(";",",",$this->recipient);
        $recipients=$this->recipient;

        $UfdbguardSMTPNotifs=smtp_defaults();
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->Host = '127.0.0.1';               //Adresse IP ou DNS du serveur SMTP
        $mail->Port = 2521;
        $mail->From       =  $UfdbguardSMTPNotifs["smtp_sender"];

        $zRecipients=explode(",",$recipients);
        foreach ($zRecipients as $To){
            $mail->AddAddress($To);
        }

        artica_smtpd_event("Delivering PDF report $mail->Subject",$recipients);
        $mail->FromName   = php_uname("n");
        $mail->Subject    = $this->Subject;
        $mail->WordWrap   = 50;
        $mail->isSMTP();
        $mail->Body= $this->Body;
        $mail->IsHTML(false);
        $mail->Debugoutput = function($str, $level) {$GLOBALS["SMTPDEBUG"][]=$str;};
        if($attachment_path<>null){
            if($attachment_path<>null) {
                if (is_file($attachment_path)) {
                    $attached_file = basename($attachment_path);
                    $mail->AddAttachment($attachment_path, $attached_file);
                }
            }
        }

        if (!$mail->send()) {
            $errors=@implode("\n",$GLOBALS["SMTPDEBUG"]);
            $this->smtp_error=$mail->ErrorInfo;
            squid_admin_mysql(0,"Unable to send PDF report",
                "\n$this->Subject\nTo:$recipients\n$mail->ErrorInfo\n$errors",__FILE__,__LINE__);
            return false;


        }
        return true;

	}

}