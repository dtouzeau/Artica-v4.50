<?php
include_once(dirname(__FILE__)."/class.acls.backend.inc");

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
        // acls.db is API-only — find an existing "all" group via the acls backend.
        $groups=acls_api_get("/proxy/acls/sqlite/groups/all?type=all");
        $groupid=0;
        if(is_array($groups)){foreach($groups as $g){if(is_array($g)){$groupid=intval($g["id"] ?? 0);break;}}}
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
        // acls.db is API-only — create the netbiosname group, then store the domain in params.
        $groupid=$this->add_group($GroupName,"netbiosname");
        if($groupid==0){return false;}
        list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/group/$groupid",["params"=>$domain]);
        if(!$ok){
            $this->mysql_error=is_string($data)?$data:"unable to set group params";
            return false;
        }
        return $this->LinkGroupToAcl($groupid);

    }

    private function add_group($GroupName,$GroupType):int{
        // acls.db is API-only — create the group via the acls backend (returns the new id).
        list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/group",["name"=>$GroupName,"type"=>$GroupType]);
        if(!$ok){
            $this->mysql_error=is_string($data)?$data:"unable to create group";
            return 0;
        }
        $groupid=is_array($data)?intval($data["id"] ?? 0):0;
        if($groupid==0){
            $this->mysql_error="Unable to get new group id";
            return 0;
        }
        return $groupid;
    }

    private function LinkGroupToAcl($groupid):bool{
        // acls.db is API-only — create the ACL link via the acls backend.
        list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/link",["table"=>$this->TableLink,"aclid"=>$this->aclid,"gpid"=>intval($groupid)]);
        if(!$ok){
            $this->mysql_error=is_string($data)?$data:"unable to link group";
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
        // acls.db is API-only — replace the group items via the acls backend bulk endpoint
        // (mode replace_all = DELETE-then-INSERT, mirroring the original).
        $tb=explode(",",$list);
        $items=array();
        foreach ($tb as $category_id) {
            $category_id=trim($category_id);
            if($category_id===""){continue;}
            $items[]=array("pattern"=>$category_id,"enabled"=>1,"uid"=>"DNS Wizard");
        }
        list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",["gpid"=>intval($gpid),"items"=>$items,"mode"=>"replace_all"]);
        if(!$ok){
            $this->mysql_error=is_string($data)?$data:"unable to add items";
            return false;
        }
        return true;
    }


}
