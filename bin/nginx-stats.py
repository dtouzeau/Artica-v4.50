import socket
import threading
import sys,os,re,time,syslog
import memcache,hashlib
from phpserialize import serialize, unserialize
from datetime import date, timedelta, datetime
import traceback as tb
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *

global zsyslog

class Parser():
    def __init__(self,buffer):
        self.debug                  = False
        self.buffer                 = buffer
        self.hostname               = ""
        self.webserver              = ""
        self.ipsrc                  = ""
        self.ipsrc2                 = ""
        self.username               = ""
        self.srcdate                = ""
        self.proto                  = ""
        self.path                   = ""
        self.http_ver               = ""
        self.http_code              = 0
        self.body_bytes_sent        = 0
        self.http_referer           = ""
        self.http_user_agent        = ""
        self.http_x_forwarded_for   = ""
        self.upstream_cache_status  = ""
        syslog.openlog("nginx-stats", syslog.LOG_PID)

        if GET_INFO_INT("NginxStatsDebug"): self.debug=True


        try:
            self.memcache = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        except:
            self.memcache = None
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: FATAL: Loading Memcache Engine Failed")
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())

    def parse_data(self):
        if len(self.buffer) == 0:
            if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s nothing returned, aborting" % self.buffer)
            return False


        matches = re.search('nginxaccess:\s+ngx\[(.+?)\]\s+([0-9\.]+)\s+([0-9\.]+)\s+(.+?)\s+\[(.+?)\]\s+([A-Z]+)\s+(.*?)\s+(.*?)\s+"(.+?)"\s+([0-9]+)\s+"(.*?)"\s+"(.*?)"\s+"(.*?)"\s+\[(.+?)\]', self.buffer)
        if not matches:
            if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s not matches, aborting" % self.buffer)
            return False

        self.webserver  = matches.group(1)
        self.ipsrc      = matches.group(2)
        self.ipsrc2     = str(matches.group(3))
        self.username   = matches.group(4)
        self.srcdate    = matches.group(5)
        self.proto      = matches.group(6)
        self.path       = matches.group(7)
        self.http_ver   = matches.group(8)
        self.http_code  = matches.group(9)
        self.body_bytes_sent = matches.group(10)
        self.http_referer = matches.group(11)
        self.http_user_agent = matches.group(12)
        self.http_x_forwarded_for = matches.group(13)
        self.upstream_cache_status = matches.group(14)

        if self.ipsrc2=="0.0.0.0": self.ipsrc2=""
        if self.http_x_forwarded_for=="-": self.http_x_forwarded_for=""
        if len(self.ipsrc2) > 3: self.ipsrc=self.ipsrc2
        if len(self.http_x_forwarded_for) > 3: self.ipsrc = self.http_x_forwarded_for
        keytime = self.log_filename_key()
        mainkey = "NGINXSTATS:%s:%s" % (keytime, self.webserver)
        matches = re.search("^(.+?)\?",self.path)
        if matches: self.path = matches.group(1)

        if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s main key" % mainkey)

        keydata = self.memcache_get(mainkey)
        if keydata is None:

            try:
                key_array = {"paths": {
                    self.path: {
                        "COUNT": 1,
                         "SIZE":int(self.body_bytes_sent),
                         "HTTP_CODE": {self.http_code:1},
                         "SRC": {self.ipsrc:1}
                    }
                    }
                }

                self.memcache_set(mainkey,serialize(key_array),3600)
            except:
                syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())
                return False

            return True

        key_array = unserialize(keydata)
        print(key_array)




        if self.found_key(key_array["paths"],self.path):
            connections_count = int(key_array["paths"][self.path]["COUNT"])

            if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Connection %s " % connections_count)

            connections_count = connections_count + 1;
            if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Connection +1 %s " % connections_count)

            connections_size=int(key_array["paths"][self.path]["SIZE"])
            connections_count=connections_count+1;
            connections_size=connections_size+int(self.body_bytes_sent)
            key_array["paths"][self.path]["COUNT"]=connections_count
            key_array["paths"][self.path]["SIZE"]=connections_size

            if self.http_code in key_array["paths"][self.path]["HTTP_CODE"]:
                http_code_count=int(key_array["paths"][self.path]["HTTP_CODE"][self.http_code])
                http_code_count=http_code_count+1
                key_array["paths"][self.path]["HTTP_CODE"][self.http_code]=http_code_count
            else:
                key_array["paths"][self.path]["HTTP_CODE"].setdefault(self.http_code,1)


            if self.ipsrc in key_array["paths"][self.path]["SRC"]:
                ipsrc_count=int(key_array["paths"][self.path]["SRC"][self.ipsrc])
                ipsrc_count=ipsrc_count+1;
                key_array["paths"][self.path]["SRC"][self.ipsrc]=ipsrc_count
            else:
                key_array["paths"][self.path]["SRC"].setdefault(self.ipsrc,1)


            self.memcache_set(mainkey, serialize(key_array), 3600)
            return True
        else:
            if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s key did not exists" % self.path)

        key_array["paths"].setdefault(self.path, {
                "COUNT": 1,
                "SIZE": int(self.body_bytes_sent),
                "HTTP_CODE": {self.http_code: 1},
                "SRC": {self.ipsrc: 1}
            })

        if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: L.136 Create key %s" % serialize(key_array))
        self.memcache_set(mainkey, serialize(key_array), 3600)
        return True



    def found_key(self,dic,mustfound):
        for key, array in dic.iteritems():
            print(key, "--->", mustfound, " ???")
            if key == mustfound:
                print(key,"--->",mustfound," return True")
                return True
        return False


    def memcache_set(self,key, value, time):
        if type(value) is not int:
            if len(value) == 0: return True
        try:
            if time > 0: self.memcache.set(key, str(value), time)
            if time == 0: self.memcache.set(key, str(value))

        except:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: mc_set(%s,%s)" % (key, value))
            return False

        return True



    def log_filename_round(self,dt=None, dateDelta=timedelta(minutes=1)):
        roundTo = dateDelta.total_seconds()
        if dt == None : dt = datetime.now()
        seconds = (dt - dt.min).seconds
        rounding = (seconds+roundTo/2) // roundTo * roundTo
        return dt + timedelta(0,rounding-seconds,-dt.microsecond)

    def log_filename(self):
        return self.log_filename_round(datetime.now(),timedelta(minutes=10)).strftime('%Y%m%d%H%M')

    def log_filename_time(self):
        return self.log_filename_round(datetime.now(), timedelta(minutes=10)).strftime('%Y-%m-%d %H:%M:%S')

    def log_filename_key(self):
        return self.log_filename_round(datetime.now(), timedelta(minutes=10)).strftime('%Y.%m.%d.%H.%M')

    def memcache_get(self,key):
        if self.memcache is None: return None
        try:
            value = self.memcache.get(key)
            if value is not None: return value
        except:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: Memcache engine return false")
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())
        return None






class ThreadedServer():
    def __init__(self):
        syslog.openlog("nginx-stats", syslog.LOG_PID)
        self.host = "127.0.0.1"
        self.port = 1823
        self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)  # the SO_REUSEADDR flag tells the kernel to
        self.s.bind((self.host, self.port))
        syslog.closelog()



    def listen(self):
        self.s.listen(5)
        pid = os.getpid()
        file_put_contents("/var/run/nginx-stats.pid", str(pid));
        syslog.syslog(syslog.LOG_INFO, "[INFO]: Started pid %s" % pid)
        while True:
            c, addr = self.s.accept()
            # c.settimeout(60) No timeout, syslog use a keep alive
            threading.Thread(target=self.listenToClient, args=(c, addr)).start()

    def listenToClient(self, c, addr):
        syslog.openlog("nginx-stats", syslog.LOG_PID)
        data = c.recv(16)
        tmp_buffer = ""
        if len(data) == 0:
            #print('Closing connection ')
            c.close()

        if len(data)>0:
            while True:
                data = c.recv(64)
                if len(data) == 0: break
                tmp_buffer=tmp_buffer+data
                if chr(10) in data:
                    p=Parser(tmp_buffer)
                    try:
                        p.parse_data()
                    except:
                        syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())

                    tmp_buffer=""

        c.close()








if __name__ == "__main__":
    ThreadedServer().listen()
