<?php



class spf_builder{
    public $domain_name;
    public $error_messages;
    public $ipv4_record;
    public $mx_records;

    public function __construct(){
    }


    public function GetSPF_record(){

        $domain_name = $this->domain_name;
        $a_records = $this->ipv4_record;
        $mx_records = $this->mx_records;
        $ipv4_records = $this->ipv4_record;
        $ipv6_records = null;
        $includespf = null;
        $othersources = null;
        $arecords_any=1;
        $mxrecords_any=1;

        // Create an array for the values of each field
        $a_records_array = $this->extractValues($a_records);
        $mx_records_array = $this->extractValues($mx_records);
        $ipv4_records_array = $this->extractValues($ipv4_records);
        $ipv6_records_array = $this->extractValues($ipv6_records);
        $includespf_array = $this->extractValues($includespf);

        $record_generated = $domain_name."\tIN TXT or SPF\t \"v=spf1 ";
        $some_match = 0;
        $this->error_messages = "";

        if($arecords_any == 1) {
            $some_match = 1;
            $record_generated .= "a ";

            // if this is not the case, check if the field has been filled
        } elseif($a_records_array[0] != NULL AND count($a_records_array) > 0) {
            // Some field has been entered, so set this to 1
            $some_match = 1;
            foreach($a_records_array as $a_records_array_item) {
                $record_generated .= "a:".$a_records_array_item." ";
            }


        }

        // The part for MX Records.

        if($mxrecords_any == 1) {

            $some_match = 1;
            $record_generated .= "mx ";
        } elseif($mx_records_array[0] != NULL AND count($mx_records_array) > 0) {

            $some_match = 1;
            foreach($mx_records_array as $mx_records_array_item) {
                $record_generated .= "mx:".$mx_records_array_item." ";
            }


        }

        // The part for IPv4 addresses.
        if($ipv4_records_array[0] != NULL AND count($ipv4_records_array) > 0) {
            $some_match = 1;
            foreach($ipv4_records_array as $ipv4_records_array_item) {
                $record_generated .= "ip4:".$ipv4_records_array_item." ";
            }
        }

        // The part for IPv6 addresses.
        if($ipv6_records_array[0] != NULL AND count($ipv6_records_array) > 0) {
            $some_match = 1;
            foreach($ipv6_records_array as $ipv6_records_array_item) {
                $record_generated .= "ip6:".$ipv6_records_array_item." ";
            }
        }

        // The INCLUDE part
        if($includespf_array[0] != NULL AND count($includespf_array) > 0) {
            $some_match = 1;
            foreach($includespf_array as $includespf_array_item) {
                $record_generated .= "include:".$includespf_array_item." ";
            }
        }

        // Check the field for other sources
        switch($othersources) {
            case "allow":
                $record_generated .= "+all";
                break;
            case "warn":
                $record_generated .= "~all";
                // If 'all' is suspicous and no other entries have been entered, give a warning
                if($some_match == 0) {
                    $this->error_messages.= "You have not entered any other record and chose do mark all other sources suspicious. This will mark all e-mail from this domain as suspicious / spam.<br>";
                }
                break;
            case "deny":
                $record_generated .= "-all";
                // If 'all' is denied and no other entries have been entered, give a warning
                if($some_match == 0) {
                    $this->error_messages.= "You have not entered any other record and chose to deny all other sources. This will basically deny all e-mail from this domain.<br>";
                }
                break;
        }
        // End of the SPF record
        $record_generated .= "\"";
        if($this->error_messages<>null){return false;}
        return $record_generated;
    }

    private function extractValues($inputstring) {
		$break_values = explode(",",$inputstring);
		$break_values_clean = array();
		foreach($break_values as $index => $value) {
		    if($value != NULL) {
		        $break_values_clean[] = trim($value);
		    }
        }
        return $break_values_clean;
    }


}