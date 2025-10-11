#!/usr/bin/env python
# This python script is *not* required to setup and run a tunnel,
# rather it shows how an external python script can bring a tunnel up / down and monitor its status.

import vici
import multiprocessing
import collections
import traceback as tb
import sys,os,re,time,syslog
from datetime import datetime
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from postgressql import *
# NOTE: unless you are root you will need to do the following: sudo chmod 777 /var/run/charon.vici

# Edit target_connections in the VState to include the VPN connections you would like to keep alive
# if this connection is dropped for some reason it will be re-started automatically by the python script

class VState(object):
    """holds the VPN state"""
    def __init__(self):
        self.alive = True
        self.session = vici.Session()
        self.possible_connections = []
        self.target_connections = []
        self.active_connections = []
        syslog.openlog("strongswan-vici", syslog.LOG_PID)

    
class StrongSwan(object):
    def __init__(self,  queue = None):
        self.state = VState()
        self.get_possible_connections()
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
    
    def process_control_connection_in(self):
        '''handle incoming mavlink packets'''
        pass
    
    def check_interfaces(self):
        state = self.state
        pgsql=Postgres()

        for vpn_conn in state.session.list_sas():
            for key in state.active_connections:
		try:
                   #print 'key', key
                   #print vpn_conn[key]
                   #print vpn_conn[key]['established']
                   #print vpn_conn[key]['state']
                   #print vpn_conn[key]['local-host']
                   #print vpn_conn[key]['remote-host']

                   self.remote_host=vpn_conn[key]['remote-host']

                   if 'remote-eap-id' in vpn_conn[key]:
                      self.username=vpn_conn[key]['remote-eap-id']
                   else:
                      self.username=vpn_conn[key]['remote-id']
                   if 'remote-vips' in vpn_conn[key]:
                      self.vips=vpn_conn[key]['remote-vips'][0]
                   #print("Remote host on vpn_conn is ",vpn_conn[key]['remote-eap-id']," and now is ",self.remote_host)
                   #print vpn_conn[key]['remote-eap-id']
                   #print self.remote_host

		except:
		    print(tb.format_exc())

		pass

                try:
                    child = vpn_conn[key]['child-sas']
                    if child == {}:
                        child = None
                except:
                    print 'tunnel not connected at child level!'
                    child = None

                if child is not None:
                    for child_key in child:
                        self.conn_name=child[child_key]['name']
                        self.spi_in=child[child_key]['spi-in']
                        self.spi_out=child[child_key]['spi-out']
                        self.bytes_in=child[child_key]['bytes-in']
                        self.bytes_out=child[child_key]['bytes-out']
                        self.packets_in=child[child_key]['packets-in']
                        self.packets_out=child[child_key]['packets-out']
                        if not self.vips:
                           self.vips= child[child_key]['remote-ts'][0]
                        self.time=child[child_key]['install-time']
                        now = datetime.now()
                        self.zdate=now.strftime("%Y-%m-%d %H:%M:%S")
                        #print self.vips[0]
                        #print 'time: ', time.time(), 'child key', child_key, child[child_key]['bytes-in'], child[child_key]['bytes-out']

                        #print 'packets'
                        #print 'in: ', child[child_key]['packets-in']
                        #print 'out: ', child[child_key]['packets-out']

                        #print 'bytes'
                        #print 'in: ', child[child_key]['bytes-in']
                        #print 'out: ', child[child_key]['bytes-out']

                        #print child[child_key]['mode']
                        #print 'ip: ', child[child_key]['local-ts']
                        #print child[child_key]['remote-ts']
                        #print 'key: ', child[child_key]['rekey-time']
                        #print 'life: ', child[child_key]['life-time']
                        syslog.syslog(syslog.LOG_INFO,"%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s" % (self.conn_name,self.remote_host,self.username,self.vips,self.spi_in,self.spi_out,self.bytes_in,self.bytes_out,self.packets_in,self.packets_out,self.time,self.zdate))
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

                if key in state.target_connections and child is None:
                    self.connection_down(key)
                    self.connection_up(key)

        for key in state.target_connections:
            if key not in state.active_connections:
                #the connection is inactive
                self.connection_up(key)


    def connection_up(self, key):
        state = self.state
        print 'up: ', key
	sa = collections.OrderedDict()
	sa['child'] = key
	sa['timeout'] = '2000'
	sa['loglevel'] = '0'
	rep =state.session.initiate(sa)
	rep.next()
	rep.close()

        #TODO: handle errors, log?

    def connection_down(self, key):
        state = self.state
        print 'down: ', key
	sa = collections.OrderedDict()
	sa['ike'] = key
	sa['timeout'] = '2000'
	sa['loglevel'] = '0'
	rep =state.session.terminate(sa)
	rep.next()
	rep.close()

	#TODO: handle errors, log?

    def get_possible_connections(self):
        '''reset and repopulate possible connections based on /etc/ipsec.conf'''
        state = self.state
        state.possible_connections = []
        for conn in state.session.list_conns():
            for key in conn:
                state.possible_connections.append(key)

        print 'p',state.possible_connections

    def get_active_connections(self):
        state = self.state
        state.active_connections = []

        for conn in state.session.list_sas():
            for key in conn:
                state.active_connections.append(key)

        print 'a', state.active_connections
                
    def is_alive(self):
        return self.state.alive

def main_loop():
    '''main processing loop'''
    #make a strongSwan control object
    VPN = StrongSwan()
    pid = os.getpid()
    file_put_contents("/var/run/strongswan-stats.pid", str(pid))
    while VPN.is_alive():
        VPN.process_control_connection_in()
        VPN.get_possible_connections()
        VPN.get_active_connections()
        VPN.check_interfaces()
        time.sleep(5.0)
    


if __name__ == '__main__':
    #run main loop as a process
    main = multiprocessing.Process(target=main_loop)
    main.start()
    main.join()
