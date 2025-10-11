<?php


class sidentity{
    private $LicenseInfos=array();
    private $WizardSavedSettings=array();

    public function __construct(){
        $q=new lib_sqlite("/home/artica/SQLITE/identity.db");
        if(!$q->TABLE_EXISTS("sidentity")) {
            $sql = "CREATE TABLE IF NOT EXISTS `sidentity` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT,`skey` TEXT UNIQUE NOT NULL, `svalue` TEXT NOT NULL )";
            $q->QUERY_SQL($sql);
        }
        $this->Load();

    }



    public function GET($key):string{
        if(isset($this->WizardSavedSettings[$key])){
            if($this->WizardSavedSettings[$key]<>null){
                return $this->WizardSavedSettings[$key];
            }
        }
        $q=new lib_sqlite("/home/artica/SQLITE/identity.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM sidentity WHERE skey='$key'");
        if(!isset($ligne["ID"])){return "";}
        if(intval($ligne["ID"])==0){return "";}
        return trim(base64_decode($ligne["svalue"]));
    }

    private function Load():bool{
        $this->LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        if(!is_array($this->LicenseInfos)){$LicenseInfos=array();}
        $this->WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
        if(!is_array($this->WizardSavedSettings)){$this->WizardSavedSettings=array();}
        return true;
    }
    public function Save():bool{
        $LicenseInfos_enc=base64_encode(serialize($this->LicenseInfos));
        $WizardSavedSettings_enc=base64_encode(serialize($this->WizardSavedSettings));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseInfos",$LicenseInfos_enc);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardSavedSettings",$WizardSavedSettings_enc);
        return true;
    }


    public function SET($key,$val):bool{
        $q=new lib_sqlite("/home/artica/SQLITE/identity.db");

        $this->LicenseInfos[$key]=$val;
        $this->WizardSavedSettings[$key]=$val;
        if(is_array($val)){$val=serialize($val);}
        $val=base64_encode($val);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM sidentity WHERE `skey`='$key'");
        if(intval($ligne["ID"])>0){
            $ID=$ligne["ID"];
            $q->QUERY_SQL("UPDATE sidentity SET svalue='$val' WHERE ID='$ID'");
            return true;
        }
        $q->QUERY_SQL("INSERT INTO sidentity (skey,svalue) VALUES ('$key','$val')");
        if(!$q->ok){echo $q->mysql_error;}
        return true;
    }

    public function RepairSidentity($savedsettings):array{
        $fields[]="myhostname";
        $fields[]="timezones";
        $fields[]="netbiosname";
        $fields[]="domain";
        $fields[]="DNS1";
        $fields[]="DNS2";
        $fields[]="smtp_domainname";
        $fields[]="mail";
        $fields[]="organization";
        $fields[]="administrator";
        $fields[]="administratorpass2";
        $fields[]="telephone";
        $fields[]="city";
        $fields[]="country";
        $fields[]="company_www";
        $fields[]="company_name";

        foreach ($fields as $field){
            if(!isset($savedsettings[$field])){$savedsettings[$field]=null;}
            if($savedsettings[$field]<>null){continue;}
            $savedsettings[$field]=$this->GET($field);
        }
        return $savedsettings;

    }

}

