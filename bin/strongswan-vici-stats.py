#!/usr/bin/env python
import socket
import threading
import sys,os,re,time,syslog
import memcache,hashlib
from phpserialize import serialize, unserialize
import traceback as tb
from datetime import date, timedelta,datetime
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from postgressql import *
global zsyslog

class Parser():
    def __init__(self,buffer):
        self.debug                  = False
        self.buffer                 = buffer
        self.conn_name=''
        self.username=''
        self.remote_host=''
        self.vips=''
        self.spi_in=0
        self.spi_out=0
        self.bytes_in=0
        self.bytes_out=0
        self.packets_in=0
        self.packets_out=0
        self.time=0
        self.zdate=''
        syslog.openlog("strongswan-vici", syslog.LOG_PID)

        if GET_INFO_INT("NginxStatsDebug"): self.debug=True


        try:
            self.memcache = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        except:
            self.memcache = None
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: FATAL: Loading Memcache Engine Failed")
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())

    def ceil_dt(dt, delta):
        return dt + (datetime.min - dt) % delta

    def parse_data(self):
        pgsql=Postgres()
        if len(self.buffer) == 0:
            if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s nothing returned, aborting" % self.buffer)
            return False


        matches=re.search('[A-Za-z]+\s+.*?\]:\s+(.+)',self.buffer)
        if not matches:
            print('NO DATA')
            return False
        newline=matches.group(1)
        Exploded=newline.split(',')

        self.conn_name  = Exploded[0]
        self.remote_host      = Exploded[1]
        self.username     = Exploded[2]
        self.vips   = Exploded[3]
        self.spi_in    = Exploded[4]
        self.spi_out      = Exploded[5]
        self.bytes_in       = Exploded[6]
        self.bytes_out   = Exploded[7]
        self.packets_in  = Exploded[8]
        self.packets_out = Exploded[9]
        self.time = Exploded[10]
        self.zdate = Exploded[11]
        TableCreate='CREATE TABLE  IF NOT EXISTS strongswan_stats(ID SERIAL,zdate TIMESTAMP,spi_in VARCHAR(255),spi_out VARCHAR(255),conn_name VARCHAR(255),username VARCHAR(255),remote_host inet,local_vip inet,time bigint default 0 ,bytes_in bigint default 0,bytes_out bigint default 0,packets_in bigint default 0,packets_out bigint default 0)'
        pgsql.QUERY_SQL(TableCreate)
        if not pgsql.ok: print(pgsql.sql_error)
        #print(self.zdate)
        now = datetime.now()
        date2query=now.strftime("%Y-%m-%d %H:%M:%S")
        date2query=datetime.strptime(date2query,"%Y-%m-%d %H:%M:%S")
        #sqlSearch="SELECT COUNT(*) as tcount FROM strongswan_stats where spi_in='"+self.spi_in+"' AND spi_out='"+self.spi_out+"' AND  zdate between date_trunc('hour', TIMESTAMP '"+self.zdate+"') and date_trunc('hour', TIMESTAMP '"+self.zdate+"' + interval '1 hour')"
        sqlSearch="SELECT date_trunc('minute', zdate) as last FROM strongswan_stats where spi_in='"+self.spi_in+"' AND spi_out='"+self.spi_out+"' order by last desc LIMIT 1"
        result = pgsql.QUERY_SQL_FETCH_ONE(sqlSearch)
        if result is None:
            print("No result --> INSERT")
            sql="INSERT INTO strongswan_stats (zdate,spi_in,spi_out,conn_name,username,remote_host,local_vip,time,bytes_in,bytes_out,packets_in,packets_out) VALUES ('"+self.zdate+"','"+self.spi_in+"','"+self.spi_out+"','"+self.conn_name+"','"+self.username+"','"+self.remote_host+"','"+self.vips+"','"+self.time+"','"+self.bytes_in+"','"+self.bytes_out+"','"+self.packets_in+"','"+self.packets_out+"')"
            pgsql.QUERY_SQL(sql)
            if not pgsql.ok:
               print(tb.format_exc())
               print(pgsql.sql_error)
               print (sql)
               return False
        else:
            dateInDB = datetime.strptime(str(result[0]),"%Y-%m-%d %H:%M:%S")
            difference = (date2query - dateInDB)
            total_seconds = difference.total_seconds()
            if total_seconds >= 300:
                print ("DIFF IS >= 300s --> INSERT")
                sql="INSERT INTO strongswan_stats (zdate,spi_in,spi_out,conn_name,username,remote_host,local_vip,time,bytes_in,bytes_out,packets_in,packets_out) VALUES ('"+self.zdate+"','"+self.spi_in+"','"+self.spi_out+"','"+self.conn_name+"','"+self.username+"','"+self.remote_host+"','"+self.vips+"','"+self.time+"','"+self.bytes_in+"','"+self.bytes_out+"','"+self.packets_in+"','"+self.packets_out+"')"
                pgsql.QUERY_SQL(sql)
                if not pgsql.ok:
                   print(tb.format_exc())
                   print (pgsql.sql_error)
                   print (sql)
                   return False
            else:
               print ("DIFF IS < 300s  --> UPDATE")
               sql="UPDATE  strongswan_stats set conn_name='"+self.conn_name+"',username='"+self.username+"',remote_host='"+self.remote_host+"',local_vip='"+self.vips+"',time='"+self.time+"',bytes_in='"+self.bytes_in+"',bytes_out='"+self.bytes_out+"',packets_in='"+self.packets_in+"',packets_out='"+self.packets_out+"' WHERE spi_in='"+self.spi_in+"' AND spi_out='"+self.spi_out+"'"
               pgsql.QUERY_SQL(sql)
               if not pgsql.ok:
                  print(tb.format_exc())
                  print (pgsql.sql_error)
                  print (sql)
                  return False


#         if self.ipsrc2=="0.0.0.0": self.ipsrc2=""
#         if self.http_x_forwarded_for=="-": self.http_x_forwarded_for=""
#         if len(self.ipsrc2) > 3: self.ipsrc=self.ipsrc2
#         if len(self.http_x_forwarded_for) > 3: self.ipsrc = self.http_x_forwarded_for
#         keytime = self.log_filename_key()
#         mainkey = "NGINXSTATS:%s:%s" % (keytime, self.webserver)
#         matches = re.search("^(.+?)\?",self.path)
#         if matches: self.path = matches.group(1)
#
#         if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s main key" % mainkey)
#
#         keydata = self.memcache_get(mainkey)
#         if keydata is None:
#
#             try:
#                 key_array = {"paths": {
#                     self.path: {
#                         "COUNT": 1,
#                          "SIZE":int(self.body_bytes_sent),
#                          "HTTP_CODE": {self.http_code:1},
#                          "SRC": {self.ipsrc:1}
#                     }
#                     }
#                 }
#
#                 self.memcache_set(mainkey,serialize(key_array),3600)
#             except:
#                 syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())
#                 return False
#
#             return True
#
#         key_array = unserialize(keydata)
#         print(key_array)
#
#
#
#
#         if self.found_key(key_array["paths"],self.path):
#             connections_count = int(key_array["paths"][self.path]["COUNT"])
#
#             if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Connection %s " % connections_count)
#
#             connections_count = connections_count + 1;
#             if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Connection +1 %s " % connections_count)
#
#             connections_size=int(key_array["paths"][self.path]["SIZE"])
#             connections_count=connections_count+1;
#             connections_size=connections_size+int(self.body_bytes_sent)
#             key_array["paths"][self.path]["COUNT"]=connections_count
#             key_array["paths"][self.path]["SIZE"]=connections_size
#
#             if self.http_code in key_array["paths"][self.path]["HTTP_CODE"]:
#                 http_code_count=int(key_array["paths"][self.path]["HTTP_CODE"][self.http_code])
#                 http_code_count=http_code_count+1
#                 key_array["paths"][self.path]["HTTP_CODE"][self.http_code]=http_code_count
#             else:
#                 key_array["paths"][self.path]["HTTP_CODE"].setdefault(self.http_code,1)
#
#
#             if self.ipsrc in key_array["paths"][self.path]["SRC"]:
#                 ipsrc_count=int(key_array["paths"][self.path]["SRC"][self.ipsrc])
#                 ipsrc_count=ipsrc_count+1;
#                 key_array["paths"][self.path]["SRC"][self.ipsrc]=ipsrc_count
#             else:
#                 key_array["paths"][self.path]["SRC"].setdefault(self.ipsrc,1)
#
#
#             self.memcache_set(mainkey, serialize(key_array), 3600)
#             return True
#         else:
#             if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s key did not exists" % self.path)
#
#         key_array["paths"].setdefault(self.path, {
#                 "COUNT": 1,
#                 "SIZE": int(self.body_bytes_sent),
#                 "HTTP_CODE": {self.http_code: 1},
#                 "SRC": {self.ipsrc: 1}
#             })
#
#         if self.debug: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: L.136 Create key %s" % serialize(key_array))
#         self.memcache_set(mainkey, serialize(key_array), 3600)
#         return True
#
#
#
#     def found_key(self,dic,mustfound):
#         for key, array in dic.iteritems():
#             print(key, "--->", mustfound, " ???")
#             if key == mustfound:
#                 print(key,"--->",mustfound," return True")
#                 return True
#         return False
#
#
#     def memcache_set(self,key, value, time):
#         if type(value) is not int:
#             if len(value) == 0: return True
#         try:
#             if time > 0: self.memcache.set(key, str(value), time)
#             if time == 0: self.memcache.set(key, str(value))
#
#         except:
#             syslog.syslog(syslog.LOG_INFO, "[ERROR]: mc_set(%s,%s)" % (key, value))
#             return False
#
#         return True



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

#     def memcache_get(self,key):
#         if self.memcache is None: return None
#         try:
#             value = self.memcache.get(key)
#             if value is not None: return value
#         except:
#             syslog.syslog(syslog.LOG_INFO, "[ERROR]: Memcache engine return false")
#             syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())
#         return None






class ThreadedServer():
    def __init__(self):
        syslog.openlog("strongswan-vici", syslog.LOG_PID)
        self.host = "127.0.0.1"
        self.port = 2823
        self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)  # the SO_REUSEADDR flag tells the kernel to
        self.s.bind((self.host, self.port))
        syslog.closelog()



    def listen(self):
        self.s.listen(5)
        pid = os.getpid()
        file_put_contents("/var/run/strongswan-vici-stats.pid", str(pid))
        syslog.syslog(syslog.LOG_INFO, "[INFO]: Started pid %s" % pid)
        while True:
            c, addr = self.s.accept()
            # c.settimeout(60) No timeout, syslog use a keep alive
            threading.Thread(target=self.listenToClient, args=(c, addr)).start()

    def listenToClient(self, c, addr):
        syslog.openlog("strongswan-vici", syslog.LOG_PID)
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
