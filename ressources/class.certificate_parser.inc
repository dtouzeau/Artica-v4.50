<?php
//$cer=new certgetter();
//echo $cer->Get_sha1_fingherprint("www.google.com") ."\n";
class certgetter{
    public $error="";
    public function __construct(){


    }

    private function addColonSeparators($str){
        $ret = "";
        for ($i = 0; $i < strlen($str); $i++) {
            $ret .= substr($str, $i, 1) . (($i % 2 == 1) ? ":" : "");
        }
        return rtrim($ret, ":");
    }

    private function extractSignature($der = false){

        if (strlen($der) < 5) {
            return false;
        }
        $der = substr($der, 4);
        while (strlen($der) > 1) {

            $class = ord($der[0]);
            $classHex = dechex($class);

            switch ($class) {
                case 0x03:
                    $len = ord($der[1]);
                    $bytes = 0;
                    if ($len & 0x80) {
                        $bytes = $len & 0x0f;
                        $len = 0;
                        for ($i = 0; $i < $bytes; $i++) {
                            $len = ($len << 8) | ord($der[$i + 2]);
                        }
                    }
                    return substr($der, 3 + $bytes, $len);
                    break;

                case 0x30:
                    $len = ord($der[1]);
                    $bytes = 0;
                    if ($len & 0x80) {
                        $bytes = $len & 0x0f;
                        $len = 0;
                        for ($i = 0; $i < $bytes; $i++) {
                            $len = ($len << 8) | ord($der[$i + 2]);
                        }
                    }
                    $contents = substr($der, 2 + $bytes, $len);
                    $der = substr($der, 2 + $bytes + $len);
                    break;

                default:
                    return false;
                    break;
            }

        }

        return false;

    }

    private function pemToDer($pem = null):string{
        if (!is_string($pem)) {return "";}
        $cert_split = preg_split('/(-----((BEGIN)|(END)) CERTIFICATE-----)/', $pem);
        if (!isset($cert_split[1])) {return false;}
        return base64_decode($cert_split[1]);
    }

    private function getCertificateInfo($server, $port = 443, $timeout = 5):array{

        $context = stream_context_create(
            array(
                'ssl' => array(
                    'capture_peer_cert' => true,
                )
            )
        );

        $timeout = $timeout ?: ini_get("default_socket_timeout");
        $errno = $errstr = 0;

        $fp = stream_socket_client(
            "ssl://$server:$port",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($fp === false) {
            $this->error="failed to connect to ssl://$server:$port ($errno $errstr)";
            return array("ERROR"=>true,"ERROR_TEXT"=>"Stream_socket_client failed to connect to ssl://$server:$port ($errno $errstr)");
        }

        $params = stream_context_get_params($fp);
        fclose($fp);

        $cp = $params['options']['ssl']['peer_certificate'];

        $cert = '';
        openssl_x509_export($cp, $cert);
        $certArray = openssl_x509_parse($cp);
        openssl_x509_free($cp);

        $certArray1 = array();
        $certArray1['ERROR']            = false;
        $certArray1['x-server-port']    = "$server:$port";
        $certArray1['x-server']         = $server;
        $certArray1['x-port']           = $port;
        $certArray1['x-retrieval-time'] = array(
                                        'utc' => gmdate("YmdHis\Z"),
                                        'unix' => gmdate("U"),
                                        );

        $cleanedCert = preg_replace('/\-+(BEGIN|END) CERTIFICATE\-+/', '', $cert);
        $cleanedCert = str_replace(array("\n\r", "\n", "\r"), '', trim($cleanedCert));
        $decCert = base64_decode($cleanedCert);

        $sha1 = sha1($decCert);
        $md5 = md5($decCert);
        $sha256 = hash('sha256', $decCert);

        $certArray1['x-serialNumber'] = array(
            'dec' => $certArray['serialNumber'],
            'hex' => dechex($certArray['serialNumber'])
        );

        $certArray1['x-fingerprints'] = array(
            "x-sha1" => $this->addColonSeparators($sha1),
            "x-md5" => $this->addColonSeparators($md5),
            "x-sha256" => $this->addColonSeparators($sha256),
            "sha1" => $sha1,
            "md5" => $md5,
            "sha256" => $sha256
        );

        if (isset($certArray['extensions']['subjectAltName'])) {
            $certArray['extensions']['x-subjectAltName'] = explode(",", $certArray['extensions']['subjectAltName']);
        }


        $hex=$this->extractSignature($this->pemToDer($cert));
        $certArray['x-certificate']['$'] = "Certificate -> Certificate Signature Value";
        $certArray['x-certificate']['base64'] = $cert;
        $certArray['x-certificate']['hex'] = wordwrap(bin2hex($hex), 64, "\r\n", true);

        $publicKey = openssl_pkey_get_public($cert);
        $publicKeyDetailsRaw = openssl_pkey_get_details($publicKey);

        $publicKeyDetails['subject-public-key-info']['rsa']['$'] = "Certificate -> Subject Public Key Info -> Subject's Public Key";
        $publicKeyDetails['subject-public-key-info']['rsa']['base64'] = wordwrap(base64_encode($publicKeyDetailsRaw['rsa']['n']), 64, "\r\n", true);
        $publicKeyDetails['subject-public-key-info']['rsa']['hex'] = wordwrap(bin2hex($publicKeyDetailsRaw['rsa']['n']), 64, "\r\n", true);
        unset($publicKeyDetailsRaw);

        return $certArray1 + $certArray + $publicKeyDetails;

    }

    public function Get_sha1_fingherprint($domain):string{
            $port=443;
            if(preg_match("#^(.+?):([0-9]+)#",$domain,$re)) {
                $port=$re[2];
                $domain=$re[1];
            }

            $certificateInfo = $this->getCertificateInfo($domain, $port);
            if($certificateInfo['ERROR']){
                return "";
            }

            $fingerPrints = $certificateInfo['x-fingerprints'];
            $sha1=$fingerPrints['x-sha1'];
            return strval($sha1);

    }


}
