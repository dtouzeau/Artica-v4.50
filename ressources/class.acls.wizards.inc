<?php

class acls_wizards{
    public $TableLink="";
    public $mysql_error="";
    private $aclid=0;

    public function __construct($TableLink="webfilters_sqacllinks",$aclid=0){
        if($TableLink==null){$TableLink="webfilters_sqacllinks";}
        $this->TableLink=$TableLink;
        $this->aclid=intval($aclid);
    }

    public function add_group_all():bool{
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE GroupType='all' ORDER BY ID");
        $groupid=intval($ligne["ID"]);
        if($groupid==0){
            $groupid=$this->add_group("Everynone","all");
        }
        if($groupid==0){return false;}
        return $this->LinkGroupToAcl($groupid);

    }

    public function add_group_adv():bool{
        $GroupName="Web browsing clean";
        $GroupType="categories";
        $gpid=$this->add_group($GroupName,$GroupType);
        if($gpid==0){return false;}
        if(!$this->add_adv_items($gpid)){
            return false;
        }
        return $this->LinkGroupToAcl($gpid);
    }

    public function add_group_threats():bool{
        $GroupName="Web browsing protection";
        $GroupType="categories";
        $gpid=$this->add_group($GroupName,$GroupType);
        if($gpid==0){return false;}
        if(!$this->add_threats_items($gpid)){
            return false;
        }
        return $this->LinkGroupToAcl($gpid);
    }
    public function add_group_dstdomain($GroupName,$values):bool{
        $GroupType="dstdomain";
        if(!is_array($values)){return false;}
        if(count($values)==0){return false;}
        $gpid=$this->add_group($GroupName,$GroupType);
        if($gpid==0){return false;}
        $items=@implode(",",$values);
        if(!$this->add_items($gpid,$items)){
            return false;
        }
        return $this->LinkGroupToAcl($gpid);
    }

    public function add_group_dst($GroupName,$values):bool{
        $GroupType="dst";
        if(!is_array($values)){return false;}
        if(count($values)==0){return false;}
        $gpid=$this->add_group($GroupName,$GroupType);
        if($gpid==0){return false;}
        $items=@implode(",",$values);
        if(!$this->add_items($gpid,$items)){
            return false;
        }

        return $this->LinkGroupToAcl($gpid);
    }
    public function add_group_ptr($GroupName,$values):bool{
        $GroupType="ptr";
        if(!is_array($values)){return false;}
        if(count($values)==0){return false;}
        $gpid=$this->add_group($GroupName,$GroupType);
        if($gpid==0){return false;}
        $items=@implode(",",$values);
        if(!$this->add_items($gpid,$items)){
            return false;
        }
        return $this->LinkGroupToAcl($gpid);
    }

    public function add_group_netbiosname($GroupName,$domain):bool{
        $md=md5("$GroupName,netbiosname".time());
        $sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`) VALUES ('$GroupName','netbiosname','1','$md','$domain','0',0);";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL($sqladd);
        if(!$q->ok) {
            $this->mysql_error=$q->mysql_error;
            return false;
        }

        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE acltpl='$md'");
        $groupid=intval($ligne["ID"]);
        if($groupid==0) {
            $this->mysql_error = "Unable to find group id from $md";
            return 0;
        }
        return $this->LinkGroupToAcl($groupid);

    }

    private function add_group($GroupName,$GroupType):int{
        $params=md5("$GroupName$GroupType".time());
        $sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`) VALUES ('$GroupName','$GroupType','1','0','$params','0',0);";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL($sqladd);
        if(!$q->ok) {
            $this->mysql_error=$q->mysql_error;
            return false;
        }

        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='$params'");
        $groupid=intval($ligne["ID"]);
        if($groupid==0) {
            $this->mysql_error = "Unable to find group id from $params";
            return 0;
        }
        return $groupid;
    }

    private function LinkGroupToAcl($groupid):bool{
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $md5 = md5($this->aclid . $groupid);
        $sql = "INSERT OR IGNORE INTO $this->TableLink (zmd5,aclid,gpid,zOrder) VALUES('$md5','$this->aclid','$groupid',1)";

        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            $this->mysql_error = $q->mysql_error;
            return false;
        }
        return true;
    }
    private function add_threats_items($gpid):bool{
        $cats="46,135,92,105,111,5027,5010,5024,5094";
        return $this->add_items($gpid,$cats);
    }
    private function add_adv_items($gpid):bool{
        $cats="91,5,143,5026,5066";
        return $this->add_items($gpid,$cats);
    }

    private function add_items($gpid,$list):bool{
        $date=date("Y-m-d H:i:s");
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'");

        $tb=explode(",",$list);
        $SQ=array();
        foreach ($tb as $category_id) {
            $SQ[]="('$gpid','$category_id','$date','DNS Wizard',1)";
        }

        $sql="INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) VALUES ".@implode(",", $SQ);
        $q->QUERY_SQL($sql);
        if(!$q->ok) {
            $this->mysql_error=$q->mysql_error;
            return false;
        }
        return true;
    }


}
