<?php

class doh_client{
    public $CURL_ALL_INFOS;
    public $CURLINFO_HTTP_CODE;
    public $curl_errno;
    private $ContentType="application/dns-message";
    public $HTTP_URL=null;

    private function doh_domain2raw($domainname){
        $raw = "";
        $domainpieces = explode('.', $domainname);
        foreach($domainpieces as $domainbit)
        {
            $raw = $raw.chr(strlen($domainbit)).$domainbit;
        }
        $raw = $raw.chr(0);
        return($raw);
    }

    public function set_contenttype($contenttyppe="application/dns-message"){
        $this->ContentType=$contenttyppe;
    }


        /* DNS raw qname to domain str */
        private function doh_raw2domain($qname)
        {
            $mylenght = ord($qname[0]);
            $domainname = "";
            $i = 1;
            while(1)
            {
                while($mylenght)
                {
                    $domainname = $domainname.$qname[$i++];
                    $mylenght--;
                }
                $mylenght = ord($qname[$i]);
                $i++;

                if($mylenght == 0)
                {
                    break;
                }
                else if($mylenght == 192)
                {
                    /* cname pointing to itself */
                    break;
                }
                $domainname = $domainname.".";
            }
            return($domainname);
        }


        private function doh_get_qtypes($requesttype = "A"){
            if($requesttype === "AAAA")
            {
                $rawtype = 28;
            }
            else if($requesttype === "CNAME")
            {
                $rawtype = 5;
            }
            else if($requesttype === "NS")
            {
                $rawtype = 2;
            }
            else
            {
                $rawtype = 1;
            }
            return($rawtype);
        }


        /* Generate a DNS raw query */
        private function doh_generate_dnsquery($domainname, $requesttype="A")
        {
            $rawtype = $this->doh_get_qtypes($requesttype);
            $dns_query  = sprintf("\xab\xcd").chr(1).chr(0).
                chr(0).chr(1).  /* qdc */
                chr(0).chr(0).  /* anc */
                chr(0).chr(0).  /* nsc */
                chr(0).chr(0).  /* arc */
                $this->doh_domain2raw($domainname).
                chr(0).chr($rawtype).
                chr(0).chr(1);  /* qclass */
            return($dns_query);
        }


        /* base64url encode the request */
        private function doh_encoderequest($request){
            return(str_replace("=", "", base64_encode($request)));
        }

        /* Connects via HTTPS to remote DoH servers */
        private function doh_connect_https($dnsquery,$httpservice){
            //https://cloudflare-dns.com/dns-query // https://dns.google.com/experimental?ct
            // https://cloudflare-dns.com/dns-query?ct=application/dns-udpwireformat&dns=%s
            // https://doh.cleanbrowsing.org/doh/family-filter/?ct&dns=%s
            // ContentType application/dns-udpwireformat or application/dns-message
            $ch = curl_init();
            if(strpos($httpservice,"%s")>0) {
                $httpservice = str_replace("%s", $dnsquery, $httpservice);
            }
            if(strpos($httpservice,"/dns-query")>0){
                if(strpos($httpservice,"?dns=")==0){
                    $httpservice=$httpservice."?dns=$dnsquery";
                }
            }

            $this->HTTP_URL=$httpservice;
            $headers = array('Accept: '.$this->ContentType, 'Content-type: '.$this->ContentType);
            curl_setopt($ch, CURLOPT_POSTFIELDS, base64_decode($dnsquery));
            curl_setopt($ch, CURLOPT_URL, $httpservice);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'DOH-Client-PHP');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // true (or 1) removed in curl 7.28.1
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $output = curl_exec($ch);
            $this->CURL_ALL_INFOS=curl_getinfo($ch);
            $this->CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
            $this->curl_errno=curl_errno($ch)." (".curl_error($ch).")";


            if($output === FALSE)
            {
                return(null);
            }

            return($output);
        }


        private function doh_read_dnsanswer($raw, $requesttype)
        {
            $results = array();
            $raw_counter = 0;

            $rawtype = $this->doh_get_qtypes($requesttype);

            /* Getting header. */
            $qst_header = unpack("nid/nspec/nqdcount/nancount/nnscount/narcount", substr($raw, $raw_counter, 12));
            $raw_counter += 12;

            if($qst_header['ancount'] == 0)
            {
                return($results);
            }

            $domainresp = $this->doh_raw2domain(substr( $raw, $raw_counter));


            $raw_counter += strlen($domainresp) + 2;
            $rawtype = ord($raw[$raw_counter + 7]);


            $ans_header = unpack("ntype/nclass/Nttl/nlength", substr( $raw, $raw_counter, 10 ) );
            $raw_counter += 13;

            /* Jumping to the IP address */
            $raw_counter += 3;

            $iplength = 4;
            if($rawtype === 28)
            {
                $iplength = 16;
            }

            if($rawtype == 1 || $rawtype == 28)
            {
                $result_ip = inet_ntop(substr( $raw, $raw_counter, $iplength ));
                if($rawtype == 1)
                {
                    $results['ipv4'][] = $result_ip;
                }
                else
                {
                    $results['ipv6'][] = $result_ip;
                }

                if($qst_header['ancount'] > 1) {
                    $i = 1;
                    while($i < $qst_header['ancount'])
                    {
                        $raw_counter += $iplength;
                        $raw_counter += 12;
                        if($rawtype == 1)
                        {
                            $results['ipv4'][] = inet_ntop(substr( $raw, $raw_counter , $iplength ));
                        }
                        else
                        {
                            $results['ipv6'][] = $result_ip;
                        }
                        $i++;
                    }
                }
            }
            else if($rawtype == 5)
            {
                $domainresp = $this->doh_raw2domain(substr( $raw, $raw_counter));
                $results['cname'][] = $domainresp;
            }
            return($results);
        }

        public function query($domainname=null,$requesttype="A",$dohservice){
            $dnsquery = $this->doh_encoderequest($this->doh_generate_dnsquery($domainname, $requesttype));
            $dnsrawresults = $this->doh_connect_https($dnsquery,$dohservice);
            $dnsresults = $this->doh_read_dnsanswer($dnsrawresults, $requesttype);
            if(empty($dnsresults)){
                return false;
            }

            if(isset($dnsresults['ipv4'])) {
                foreach($dnsresults['ipv4'] as $ipv4) {
                    return $ipv4;
                }
            }

            if(isset($dnsresults['ipv6'])) {
                foreach($dnsresults['ipv6'] as $ipv6) {
                    return $ipv6;
                }
            }

            if(isset($dnsresults['cname'])){
                foreach($dnsresults['cname'] as $cname) {
                    return $cname;
                }
            }


        }
}