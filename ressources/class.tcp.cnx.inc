<?php

class tcp_connections {


    function __construct(){

    }
    private function readFileLines(string $filename) : array{
        if (is_file($filename) and is_readable($filename)) {
            $file = @file_get_contents($filename);
            if (!empty($file)) {
                $results = explode("\n", $file);
            }
        }

        return $results ?? [];
    }

    private function convertTcpIpFormat($input) {
        $ip = '';
        $ip .= hexdec(substr($input, 6, 2)). '.';
        $ip .= hexdec(substr($input, 4, 2)). '.';
        $ip .= hexdec(substr($input, 2, 2)). '.';
        $ip .= hexdec(substr($input, 0, 2));
        $port = hexdec(substr($input, 9, 4));

        return ['ip' => $ip, 'port' => $port];
    }

    private function getProcTcpConnections() : array
    {
        $tcp = $this->readFileLines("/proc/net/tcp");
        if (!empty($tcp)) {
            $first = true;
            $counter = 0;
            foreach ($tcp as $row) {
                if (!empty($row)) {
                    if ($first) {
                        $headers = explode(' ', preg_replace('!\s+!', ' ', trim($row)));
                        $first = false;
                    } else {
                        $values = explode(' ', preg_replace('!\s+!', ' ', trim($row)));
                        if (!empty($headers) and !empty($values)) {
                            $subcounter = 0;
                            foreach ($headers as $header) {
                                $results[$counter][$header] = $values[$subcounter];
                                $subcounter++;
                            }
                        }
                        $counter++;
                    }
                }
            }
        }
        return $results ?? [];
    }

    public function tcpConnections(bool $OnLyLocalPorts = false) : array{
        $tcp = $this->getProcTcpConnections();
        if (!empty($tcp)) {
            foreach ($tcp as $row) {
                $local = $this->convertTcpIpFormat($row['local_address']);
                $newrow['local_ip'] = $local['ip'];
                $newrow['local_port'] = $local['port'];
                if($OnLyLocalPorts){
                    $results[ $local['port']]=true;
                    continue;
                }

                $remote = $this->convertTcpIpFormat($row['rem_address']);
                $newrow['remote_ip'] = $remote['ip'];
                $newrow['remote_port'] = $remote['port'];
                $results[] = $newrow;

            }
        }

        return $results ?? [];
    }

}