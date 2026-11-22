<?php


/**
 * Support-account identity used by fw.support.php.
 *
 * The decode logic (base64 + PHP-unserialize heuristics, license/wizard
 * fallback) now lives in the articarest `supporttool` module. This class
 * fetches the resolved identity from GET /system/support/db/account so PHP
 * and Go can never drift. A local decode fallback is kept for the rare case
 * where the daemon is unreachable, so the parameters/recover forms still
 * render.
 */
class SupportTracker{
    public $supportSecCode=0;
    public $supportName=null;
    public $supportEmail=null;
    public $supportMID=0;

    public $supportCustomerID=0;

    function __construct(){
        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/support/db/account")),true);
        if(is_array($resp) && !empty($resp["Status"]) && is_array($resp["data"] ?? null)){
            $d=$resp["data"];
            $this->supportName=strval($d["name"] ?? "");
            $this->supportEmail=strval($d["email"] ?? "");
            $this->supportSecCode=strval($d["secCode"] ?? "");
            $this->supportCustomerID=intval($d["customerID"] ?? 0);
            $this->supportMID=intval($d["mid"] ?? 0);
            return;
        }
        // Daemon unreachable — fall back to the legacy local decode so the
        // account forms keep working.
        $this->decodeLocal();
    }

    /**
     * Legacy fallback: decode the support* Valkey settings in PHP exactly as
     * before the articarest migration.
     */
    private function decodeLocal(){
        $this->supportName=base64_decode(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportName")));
        $this->supportEmail=base64_decode(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportEmail")));
        $this->supportSecCode=base64_decode(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportSecCode")));
        $this->supportCustomerID=base64_decode(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportCustomerID")));
        $this->supportMID=base64_decode(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportMID")));
        if(preg_match("#:[0-9]+#",$this->supportMID)){
            $this->supportMID=unserialize($this->supportMID);
        }
        if(preg_match("#s:[0-9]+:#",$this->supportName)){
            $this->supportName=unserialize($this->supportName);
        }
        if(preg_match("#s:[0-9]+:#",$this->supportEmail)){
            $this->supportEmail=unserialize($this->supportEmail);
        }
        if(preg_match("#s:[0-9]+:#",$this->supportSecCode)){
            $this->supportSecCode=unserialize($this->supportSecCode);
        }
        if(preg_match("#:[0-9]+#",$this->supportCustomerID)){
            $this->supportCustomerID=unserialize($this->supportCustomerID);
        }

        $LicenseInfos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $WizardSavedSettings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
        if(!isset($LicenseInfos["COMPANY"])){
            $LicenseInfos["COMPANY"]=null;
        }
        if(!isset($WizardSavedSettings["company_name"])){
            $WizardSavedSettings["company_name"]=null;
        }
        if(!isset($WizardSavedSettings["mail"])){
            $WizardSavedSettings["mail"]=null;
        }
        if(!isset($LicenseInfos["EMAIL"])){
            $LicenseInfos["EMAIL"]=null;
        }

        if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
        if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}

        if(is_null($this->supportName)){
            $this->supportName=$LicenseInfos["COMPANY"];
        }
        if(is_null($this->supportEmail)){
            $this->supportEmail=$LicenseInfos["EMAIL"];
        }

        $this->supportMID=intval($this->supportMID);
    }

}
