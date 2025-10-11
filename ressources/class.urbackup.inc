<?php
include_once(dirname(__FILE__)."/class.sqlite.inc");
class urbackup{


    public function __construct(){
    }

    public function GET_SERVER_SETTINGS($Key):string{
        $q=new lib_sqlite("/var/urbackup/backup_server_settings.db");
        $ligne=$q->mysqli_fetch_array("SELECT value FROM settings WHERE Key='$Key'");
        return strval($ligne["value"]);
    }


}