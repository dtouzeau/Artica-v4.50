<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
$GLOBALS["NO_COMPILE_POSTFIX"]=true;
if($argv[1]=="--count"){count_members_ldap();exit;}
if($argv[1]=="--export"){export_members();exit;}

import_members_ldap($argv[1],$argv[2],$argv[3]);


function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/ldap.import.members";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function import_members_ldap($ou,$gpid,$filename){
    $unix=new unix();
    $gpid=intval($gpid);

    if(!preg_match("#\.(csv|txt)$#i",$filename)){
        echo "Unable to open $filename\n";
        build_progress(110,"{importing} $filename {failed} NOT A *.csv or a *.txt file");
        return false;
    }


    $filepath=dirname(__FILE__)."/ressources/conf/upload/$filename";
    if(!is_file($filepath)){
        build_progress(110,"$filepath no such file");
        return;
    }

    if($ou==null){
        build_progress(110,"{organization} is null...");
        @unlink($filepath);
        return;
    }
    if($gpid==0){
        import_members_ldap_nogroup($ou,$filename);
        @unlink($filepath);
        return;
    }

    build_progress(10,"Open $filepath");
    $lines=$unix->COUNT_LINES_OF_FILE($filepath);
    echo "$lines lines\n";
    $datas=explode("\n",file_get_contents($filepath));
    @unlink($filepath);
    $Max=count($datas);
    echo "$Max lines exploded\n";
    $c=0;
    $count_user=0;
    $good=0;
    $bad=0;

    if(!is_array($datas)){
        build_progress(110,"Corrupted file...");
        @unlink($filepath);
        return;
    }

    foreach ($datas as $ligne){
        $c++;
        $prc=$c/$Max;
        $prc=$prc*100;
        $prc=round($prc);
        if($prc<10){$prc=10;}
        if($prc>98){$prc=98;}

        if(trim($ligne)==null){continue;}
        $ligne=str_replace('"','',$ligne);
        $ligne=str_replace("\r\n", "", $ligne);
        $ligne=str_replace("\r", "", $ligne);
        $ligne=str_replace("\n", "", $ligne);
        $ligne=str_replace("'","`",$ligne);
        $table=explode(";",$ligne);
        if($table[2]==null){
            build_progress($prc,"Entry2 is null");
            continue;}
        $count_user=$count_user+1;
        $user=new user();
        $user->SIMPLE_SCHEMA=true;
        $user->uid=$table[2];
        $user->ou=$ou;
        $user->group_id=$gpid;
        $user->DisplayName=$table[0];
        build_progress($prc,"{importing} $user->uid ($c/$Max)");
        if(strpos(trim($user->DisplayName), " ")>0){
            $splituser=explode(" ", $user->DisplayName);
            $user->givenName=$splituser[0];
            unset($splituser[0]);
            $user->sn=@implode(" ", $splituser);
        }

        $user->mail=$table[1];
        $user->password=$table[3];
        $user->PostalCode=$table[4];
        $user->postalAddress=$table[5];
        $user->mobile=$table[6];
        $user->telephoneNumber=$table[7];
        if($user->add_user()){
            if($table[8]<>null){
                $aliases=explode(',',$table[8]);
                if(is_array($aliases)){
                    foreach ($aliases as $mail_ali){
                        if(trim($mail_ali)==null){continue;}
                        $user->add_alias($mail_ali);
                    }
                }
            }


            $good=$good+1;}

        else{$bad=$bad+1;
        }
    }

    build_progress(100,"{importing} $good {users} {done}");
    count_members_ldap();



}

function import_members_ldap_nogroup($ou,$filename=null){
    $unix=new unix();

    if(!preg_match("#\.csv$#i",$filename)){
        echo "Unable to open $filename\n";
        build_progress(110,"{importing} $filename {failed} NOT A *.csv file");
        return false;
    }

    $filepe=dirname(__FILE__)."/ressources/conf/upload/$filename";
    $row = 1;



    $handle = fopen($filepe, "r");
    if(!$handle){
        echo "Unable to open $filepe\n";
        build_progress(110,"{importing} $filepe {users} {failed}");
        return false;
    }

    $MaxRows=$unix->COUNT_LINES_OF_FILE($filepe);
    $c=0;
    $oldprc=0;
    $e=0;
    build_progress(1, "{importing} $filename");
    echo "Looping trough data\n";
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $countOFRows = count($data);
        $c++;

        $prc=$c/$MaxRows;
        $prc=round($prc*100);

        if($prc<99) {
            if ($prc > $oldprc) {
                $oldprc = $prc;
                build_progress($prc, "{importing} {member} $c/$MaxRows");
            }
        }


        if($countOFRows<7){
            echo "Skip {$data[0]} {$data[1]} {$data[2]}\n";
            $e++;
            continue;
        }
        $GivenName=null;
        $Surname=null;
        $NameSet=null;
        $StreetAddress=null;
        $City=null;
        $ZipCode=null;
        $TelephoneNumber=null;
        $MobilePhone=null;

        $Username=$data[0];
        $Password=$data[1];
        $EmailAddress=$data[2];
        $Group=$data[3];
        $Title=$data[4];


        $group=new groups(null,"ou:$ou:$Group");
        if(intval($group->group_id)==0){
            if(!$group->add_new_group($Group,$ou)){
                echo "Failed to create group $Group\n";
                $e++;
                continue;
            }
            $group->group_id=$group->generated_id;
        }

        if(isset($data[5])){$GivenName=$data[5];}
        if(isset($data[6])){$Surname=$data[6];}
        if(isset($data[7])){$StreetAddress=$data[7];}
        if(isset($data[8])){$City=$data[8];}
        if(isset($data[9])){$ZipCode=$data[9];}
        if(isset($data[10])){$TelephoneNumber=$data[10];}
        if(isset($data[11])){$MobilePhone=$data[11];}

        $user=new user();
        $user->SIMPLE_SCHEMA=true;
        $user->uid=$Username;
        $user->ou=$ou;
        $user->givenName=$GivenName;
        $user->sn=$Surname;
        $user->mail=$EmailAddress;
        $user->password=$Password;
        $user->PostalCode=$ZipCode;
        $user->postalAddress=$StreetAddress;
        $user->town=$City;
        $user->mobile=$MobilePhone;
        $user->telephoneNumber=$TelephoneNumber;
        $user->title=$Title;
        $user->group_id=$group->group_id;
        $user->DisplayName=trim("$Title $GivenName $Surname");

        if(!$user->add_user()){
            "echo failed to add $Username ( $GivenName $Surname)\n";
            $e++;
        }


    }
    fclose($handle);
    build_progress(100, "{importing} $MaxRows {success} $e {errors}");
    count_members_ldap();

}

function export_members(){
    $unix=new unix();
    $file_temp=$unix->FILE_TEMP().".csv";
    $file_path=dirname(__FILE__)."/ressources/logs/ldap_members.gz";
    if(is_file($file_path)){@unlink($file_path);}
    $filter=array("gidnumber","cn","uid","mail","displayname","sn","postalcode","postaladdress","street","givenname","userpassword","telephonenumber","title","l","displayname","mobile");

    $ldap=new clladp();
    $dn="dc=organizations,$ldap->suffix";
    $ld =$ldap->ldap_connection;
    build_progress(5, "{exporting} {member}");
    $sr = ldap_search($ld,$dn,'objectclass=userAccount',$filter,null);
    if(!$sr){
        echo "Failed to search users\n";
        build_progress(110, "{exporting} {member} {failed}");
        return;
    }

    $out = fopen($file_temp, 'w');
    if(!$out){
        echo "$file_temp permission denied or disk full\n";
        build_progress(110, "{exporting} {member} {failed}");
        return;
    }

    $heads=explode(",","Username,Password,EmailAddress,Group,Title,GivenName,Surname,StreetAddress,City,ZipCode,TelephoneNumber,Mobile");

    foreach ($heads as $field){
        $line[]=$field;
    }

    if (fputcsv($out, $line) === false) {
        echo "Failed to write headers\n";
        fclose($out);
        @unlink($file_temp);
        build_progress(110, "{exporting} {member} {failed}");
        return false;
    }

    $hash=ldap_get_entries($ld,$sr);
    $Max=count($hash);
    $c=0;
    $OutPutPrc=5;
    $d=0;
    if(!is_array($hash)){return array();}
    foreach ($hash as $num=>$ligne){
        $c++;
        $d++;
        $prc=$c/$Max;
        $prc=round($prc*100);


        $Username=$ligne["uid"][0];
        if($Username==null){continue;}

        if($prc > 5) {
            if ($prc < 95) {
                if ($prc > $OutPutPrc) {
                    $OutPutPrc = $prc;
                    build_progress($OutPutPrc, "{exporting} {member} $Username $c/$Max");
                }
            }
        }


        $Password=$ligne["userpassword"][0];
        $EmailAddress=$ligne["mail"][0];
        $Groupid=$ligne["gidnumber"][0];
        if(!isset($GRPS[$Groupid])){
            $group=new groups($Groupid);
            $GRPS[$Groupid]=$group->groupName;
        }



        $Group=$GRPS[$Groupid];
        $Title=$ligne["title"][0];
        $GivenName=$ligne["givenname"][0];
        $Surname=$ligne["sn"][0];
        $StreetAddress=$ligne["street"][0];
        $City=$ligne["l"][0];
        $ZipCode=$ligne["postalcode"][0];
        $TelephoneNumber=$ligne["telephonenumber"][0];
        $MobilePhone=$ligne["mobile"][0];

        if($d>150){
            build_progress($OutPutPrc, "{exporting} {member} $Username $c/$Max/$prc");
            $d=0;
        }

        //echo "$Username,$Password,$EmailAddress,$Group,$Title,$GivenName,$Surname,$StreetAddress,$City,$ZipCode,$TelephoneNumber,$MobilePhone\n";
        $line=array($Username,$Password,$EmailAddress,$Group,$Title,$GivenName,$Surname,$StreetAddress,$City,$ZipCode,$TelephoneNumber,$MobilePhone);

        if (fputcsv($out, $line) === false) {
            echo "Failed to write line $c\n";
            fclose($out);
            @unlink($file_temp);
            build_progress(110, "{exporting} {member} {failed}");
            return false;
        }

    }

    build_progress(95, "{exporting} {member} {compressing}");

    fclose($out);
    $gzip=$unix->find_program("gzip");
    shell_exec("$gzip -c $file_temp >$file_path");
    @unlink($file_temp);
    build_progress(100, "{exporting} {member} {success}");

}

function count_members_ldap(){

    $pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__;
    $unix=new unix();

    $timexec=$unix->file_time_min($pidtime);
    if($timexec<5){return false;}
    @unlink($pidtime);
    @file_put_contents($pidtime,time());

    $filter=array("gidnumber");
    $ldap=new clladp();
    $dn="dc=organizations,$ldap->suffix";
    $ld =$ldap->ldap_connection;

    if(!$ldap->ExistsDN($dn)){
        return false;
    }

    $sr = @ldap_search($ld,$dn,'objectclass=userAccount',$filter,null);
    if(!$sr) {
        $ldap_error = ldap_errno($ldap->ldap_connection);
        if($ldap_error==32){return false;}
        $ldap_err2str = ldap_err2str($ldap_error);
        writelogs("Error $ldap_error $ldap_err2str", __FUNCTION__, __FILE__, __LINE__);
        return false;
    }
    $hash= ldap_get_entries($ld,$sr);
    $Max=count($hash);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CountOfLDAPMembers",$Max);
    return true;
}