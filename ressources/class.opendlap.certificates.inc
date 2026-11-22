<?php


class openldap_ssl{
    private $certificate;
    public $private_key_path=null;
    public $certificate_path=null;

    public function __construct($certificate){
        $this->certificate=$certificate;
    }

    public function build(){
        $certificatename=md5($this->certificate);
        include_once(dirname(__FILE__)."/class.certificates-db.inc");
        $cdb=new CertificatesDB();
        $row=$cdb->getByCommonName($this->certificate);
        if(!is_object($row)){return false;}
        $ligne=(array)$row;
        @mkdir("/etc/unbound", 0755, true);
        $field = "privkey";
        if ($ligne["UsePrivKeyCrt"] == 0) {
            $field = "Squidkey";
            if (strlen($ligne[$field]) < 10) {
                if (strlen($ligne["privkey"]) > 10) {
                    $field = "privkey";
                }
            }
        }
        $Expose_content = $ligne[$field];
        $Expose_content = str_replace("\\n", "\n", $Expose_content);
        @file_put_contents("/etc/ldap/$certificatename.key", $Expose_content);
        $this->private_key_path="/etc/ldap/$certificatename.key";

        $field = "crt";
        if ($ligne["UsePrivKeyCrt"] == 0) {
            $field = "SquidCert";
        }
        $Expose_content = $ligne[$field];
        $Expose_content = str_replace("\\n", "\n", $Expose_content);
        @file_put_contents("/etc/ldap/$certificatename.pem", $Expose_content);
        $this->certificate_path="/etc/ldap/$certificatename.pem";

    }



}