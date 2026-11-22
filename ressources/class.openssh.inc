<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.tcpip.inc');

/**
 * REST facade over the articarest `openssh` module.
 *
 * This class used to do the work itself: parse /etc/ssh/sshd_config in the web
 * process, keep its own table of defaults, rebuild sshd_config, write
 * /etc/pam.d/sshd and /etc/ssh/authorized_keys, and reload the daemon. All of
 * that now lives in golang and is reached over the Unix socket:
 *
 *   GET  /ssh/config              settings + allowed groups + HostKey lines
 *   POST /ssh/config              merge settings into OpenSSHDArray
 *   GET  /ssh/reconfigure         rebuild sshd_config (also rebuilds
 *                                 authorized_keys and /etc/pam.d/sshd)
 *   GET  /ssh/reconfigure-restart same, then restart the daemon
 *   GET  /ssh/2fa/qrcode          regenerate the google-authenticator secret
 *
 * The public surface the pages use — $main_array, $AllowGroups, $HostKey and
 * SaveInterface() — is unchanged, so call sites did not have to move.
 */
class openssh{

    public $AllowGroups=array();
    public $HostKey=array();
    public $main_array=array();

    /** Null when the settings were read successfully, the reason otherwise. */
    public $lastError=null;

    function __construct(){
        $this->LoadConfig();
    }

    /**
     * Reads the settings from the daemon.
     *
     * On failure everything stays empty AND $lastError is set — which is what
     * SaveInterface() checks. Without that flag a page whose read failed would
     * render blank fields and then write those blanks back over a working
     * configuration.
     */
    private function LoadConfig():bool{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/config"));
        if(!is_object($json)){
            $this->lastError="no answer from the openssh service";
            return false;
        }
        if(!isset($json->Status) || !$json->Status){
            $this->lastError="openssh service refused the request";
            if(isset($json->Error) && strlen($json->Error)>0){$this->lastError=$json->Error;}
            return false;
        }
        if(!isset($json->Config) || !is_object($json->Config)){
            $this->lastError="the openssh service returned no configuration";
            return false;
        }

        $this->main_array=(array)$json->Config;
        $this->AllowGroups=array();
        if(isset($json->AllowGroups) && is_array($json->AllowGroups)){
            $this->AllowGroups=$json->AllowGroups;
        }
        $this->HostKey=array();
        if(isset($json->HostKey) && is_array($json->HostKey)){
            $this->HostKey=$json->HostKey;
        }
        $this->lastError=null;
        return true;
    }

    /**
     * Persists $main_array through the daemon.
     *
     * Refuses to write when the initial read failed — see LoadConfig(). Returns
     * false on refusal or on a failed write; callers that report errors should
     * surface LastError().
     */
    function SaveInterface():bool{
        if($this->lastError<>null){
            writelogs("Refusing to save OpenSSH settings: {$this->lastError}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        if(!is_array($this->main_array) || count($this->main_array)==0){
            $this->lastError="refusing to save an empty configuration";
            writelogs($this->lastError,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return false;
        }

        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/ssh/config",$this->main_array));
        if(!is_object($json) || !isset($json->Status) || !$json->Status){
            $this->lastError="unable to save the OpenSSH settings";
            if(is_object($json) && isset($json->Error) && strlen($json->Error)>0){
                $this->lastError=$json->Error;
            }
            writelogs($this->lastError,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        return true;
    }

    /** Last read/write failure, or null. */
    public function LastError(){
        return $this->lastError;
    }

    /** True when the settings were read and are safe to display and save. */
    public function ok():bool{
        return $this->lastError==null;
    }

    /**
     * Rebuilds sshd_config from the stored settings — the golang replacement
     * for the old save() + pamd() + sshd_authorizedkeys() trio, which the
     * daemon now performs in one pass.
     */
    public function Reconfigure($restart=false):bool{
        $endpoint=$restart?"/ssh/reconfigure-restart":"/ssh/reconfigure";
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint));
        if(!is_object($json) || !isset($json->Status) || !$json->Status){
            $this->lastError="unable to reconfigure OpenSSH";
            if(is_object($json) && isset($json->Error) && strlen($json->Error)>0){
                $this->lastError=$json->Error;
            }
            return false;
        }
        return true;
    }
}
