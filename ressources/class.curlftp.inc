<?php


class curlftp{

    private $curl_handle;
    private $url;
    private $username=null;
    private $password=null;
    private $passive_mode=false;
    private $port=21;

    public function __construct($username=null, $password=null, $server, $port = 21,
                                $initial_path = '', $passive_mode = false){
        if(intval($port)==0){$port=21;}
        $this->port=$port;
        $this->username=$username;
        $this->password=$password;
        $this->passive_mode=$passive_mode;
        $this->url = "ftp://{$server}/{$initial_path}";


    }

    private function prepare(){
        $this->curl_handle = curl_init();
        // check for successful connection
        if (!$this->curl_handle) {
            $this->mysql_error = 'Could not initialize cURL.';
            return false;
        }
        $options = array(
            CURLOPT_SSLVERSION =>'all',
            CURLOPT_SSL_VERIFYPEER => false, // don't verify SSL
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FTP_SSL        => CURLFTPSSL_ALL, // require SSL For both control and data connections
            CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_DEFAULT, // let cURL choose the FTP authentication method (either SSL or TLS)
            CURLOPT_UPLOAD         => true,
            CURLOPT_PORT           => $this->port,
            CURLOPT_TIMEOUT        => 10,
        );
        // cURL FTP enables passive mode by default, so disable it by enabling the PORT command and allowing cURL to select the IP address for the data connection

        if($this->username<>null){
            $options[CURLOPT_USERPWD]     = $this->username . ':' . $this->password;
        }

        if ( ! $this->passive_mode ) {
            $options[CURLOPT_FTPPORT]       = '-';
        }

        foreach ( $options as $option_name => $option_value ) {
            curl_setopt( $this->curl_handle, $option_name, $option_value );
        }

    }


    public function curl_upload($sourcefile,$target){



    }



}
