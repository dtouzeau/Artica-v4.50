<?php


class ssh_client{

    private $username;
    private $password;
    private $hostname;
    private $port;
    public $connection;
    private $fingerprint;
    private $log=array();

    public function __construct($hostname,$port=22,$username=null,$password=null){
        $this->username=$username;
        $this->password=$password;
        $this->hostname=$hostname;
        $this->port=intval($port);
        if($this->port==0){$this->port=22;}


    }

    public function connect(){
        if (!function_exists("ssh2_connect")){
            $this->connection=false;
            $this->log[]= "ssh2_connect no module loaded";
            return false;
        }

        $callbacks = array('disconnect' => 'xdisconnect');
        $this->connection = @ssh2_connect($this->hostname, $this->port, NULL, $callbacks);
        if($this->connection === FALSE){ $this->log[]= "Connection failed !";return false;}
        $this->fingerprint = @ssh2_fingerprint($this->connection);

        if( !@ssh2_auth_password( $this->connection, $this->username, $this->password ) ) {
            $this->log[]="Authorization failed !";
            return false;
        }

        return true;
    }

    public function exec($cmd) {
        if (!($stream = ssh2_exec($this->connection, $cmd))) {
           $this->log[]="SSH command $cmd failed";
           return false;
        }
        stream_set_blocking($stream, true);
        $data = "";
        while ($buf = fread($stream, 4096)) {
            $data .= $buf;
        }
        fclose($stream);
        return $data;
    }

    public function disconnect() {
        $this->exec('echo "EXITING" && exit;');
        $this->connection = false;
    }

    public function copyfile($src,$destination,$chmod=0644){
        if(!ssh2_scp_send($this->connection, $src, $destination)){
            $this->log[]="Failed to copy $src to $destination";
            return false;
        }
        if($chmod>0){
            $sftp = ssh2_sftp($this->connection);
            if(!@ssh2_sftp_chmod($sftp, $destination, $chmod)){
                $this->log[]="Failed to apply permission $chmod to $destination";
                return false;
            }
        }

        return true;
    }

    public function GetLogs(){
        return @implode("\n",$this->log);
    }

    final function xdisconnect($reason, $message) {
        $this->log[]="Server disconnected with reason code $reason and message: $message";
    }


}