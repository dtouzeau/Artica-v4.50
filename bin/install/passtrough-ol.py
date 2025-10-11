#!/usr/bin/python -O
# -*- coding: utf-8 -*-
##
# Author(s): Meng Tan
# Module description:  Passthrough ACL
# Allows RDPProxy to connect to any server RDP.
##
import re
import random
import os
import signal
import traceback
import sys
from datetime import datetime
from datetime import timedelta
from time import mktime
from time import gmtime
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from adclass import *
import ldap
import logging
import sqlite3
import hashlib
import traceback as tb
from netaddr    import IPNetwork, IPAddress
from struct     import unpack
from struct     import pack
from select     import select
import socket

MAGICASK = u'UNLIKELYVALUEMAGICASPICONSTANTS3141592926ISUSEDTONOTIFYTHEVALUEMUSTBEASKED'
DEBUG = True

if DEBUG:
    import pprint

class AuthentifierSocketClosed(Exception):
    pass

class AuthentifierSharedData():
    def __init__(self, conn):
        self.proxy_conx = conn
        self.Debug      = False
        self.shared = {
            u'module':                  u'login',
            u'selector_group_filter':   u'',
            u'selector_device_filter':  u'',
            u'selector_proto_filter':   u'',
            u'selector':                u'False',
            u'selector_current_page':   u'1',
            u'selector_lines_per_page': u'0',

            u'target_login':    MAGICASK,
            u'target_device':   MAGICASK,
            u'target_host':     MAGICASK,
            u'login':           "admin",
            u'ip_client':       MAGICASK,
            u'target_protocol': MAGICASK,
        }

        LOG_LEVEL = logging.DEBUG
        logging.basicConfig(format='%(asctime)s [%(levelname)s] [%(process)d] %(message)s',
                            filename='/var/log/rdpproxy/auth.log', filemode='a', level=LOG_LEVEL)
        logging.raiseExceptions = False
        self.logging=logging

    def send_data(self, data):
        u""" NB : Strings sent to the ReDemPtion proxy MUST be UTF-8 encoded """

        self.logging.debug(u'================> send_data (update) =\n%s' % (pprint.pformat(data)))

        # replace MAGICASK with ASK and send data on the wire
        _list = []
        for key, value in data.items():
            self.shared[key] = value
            if value != MAGICASK:
                _pair = u"%s\n!%s\n" % (key, value)
            else:
                _pair = u"%s\nASK\n" % key
            _list.append(_pair)

        self.logging.debug(u'send_data (on the wire) =\n%s' % (pprint.pformat(_list)))


        _r_data = u"".join(_list)
        _r_data = _r_data.encode('utf-8')
        _len = len(_r_data)

        _chunk_size = 1024 * 64 - 1
        _chunks = _len // _chunk_size

        if _chunks == 0:
            self.proxy_conx.sendall(pack(">L", _len))
            self.proxy_conx.sendall(_r_data)
        else:
            if _chunks * _chunk_size == _len:
                _chunks -= 1
            for i in range(0, _chunks):
                self.proxy_conx.sendall(pack(">H", 1))
                self.proxy_conx.sendall(pack(">H", _chunk_size))
                self.proxy_conx.sendall(_r_data[i*_chunk_size:(i+1)*_chunk_size])
            _remaining = _len - (_chunks * _chunk_size)
            self.proxy_conx.sendall(pack(">L", _remaining))
            self.proxy_conx.sendall(_r_data[_len-_remaining:_len])

    def receive_data(self):
        u""" NB : Strings coming from the ReDemPtion proxy are UTF-8 encoded """

        _status, _error = True, u''
        _data = ''
        try:
            # Fetch Data from Redemption
            try:
                _packet_size, = unpack(">L", self.proxy_conx.recv(4))
                _data = self.proxy_conx.recv(_packet_size)
            except Exception as e:
                import traceback
                if self.Debug: self.logging.info(u"Socket Closed : %s" % traceback.format_exc(e))
                raise AuthentifierSocketClosed()
            _data = _data.decode('utf-8')
        except AuthentifierSocketClosed as e:
            raise
        except Exception as e:
            raise AuthentifierSocketClosed()

        if _status:
            _elem = _data.split('\n')

            if len(_elem) & 1 == 0:
                self.logging.info(u"Add number of items in authentication protocol")
                _status = False

        if _status:
            try:
                _data = dict(zip(_elem[0::2], _elem[1::2]))
            except Exception as e:
                import traceback
                if self.Debug: self.logging.info(u"Error while parsing received data %s" % traceback.format_exc(e))
                _status = False

            if self.Debug: self.logging.info("received_data (on the wire) =\n%s" % (pprint.pformat(_data)))


        # may be actual socket error, or unpack or parsing failure
        # (because we got partial data). Whatever the case socket connection
        # with rdp proxy is now broken and must be terminated
        if not _status:
            raise socket.error()

        if _status:
            for key in _data:
                if (_data[key][:3] == u'ASK'):
                    _data[key] = MAGICASK
                elif (_data[key][:1] == u'!'):
                    _data[key] = _data[key][1:]
                    self.logging.debug("received_data: (%s, %s)" % (key, _data[key]))
                else:
                    # _data[key] unchanged
                    pass
            self.shared.update(_data)
            self.logging.debug("receive_data (is asked): =\n%s" % (pprint.pformat([e[0] for e in self.shared.items()])))


        return _status, _error

    def get(self, key, default=None):
        self.logging.debug("shared().get(): "+str(key))
        return self.shared.get(key, default)

    def is_asked(self, key):
        return self.shared.get(key) == MAGICASK


class ACLPassthrough():
    def __init__(self, conn, addr):
        self.proxy_conx = conn
        self.addr       = addr
        self.shared = AuthentifierSharedData(conn)
        self.Debug       = False
        self.LdapIndex   = 0
        self.LDAP_SUFFIX = ''
        self.object_id   = 0
        self.username    = ''
        self.password    = ''
        self.EnableActiveDirectoryFeature=GET_INFO_INT("EnableActiveDirectoryFeature")
        RDPProxyAuthookDebug = GET_INFO_INT("RDPProxyAuthookDebug")
        RDPProxyAuthookDebug=1
        LOG_LEVEL = logging.INFO
        if RDPProxyAuthookDebug==1:
            LOG_LEVEL=logging.DEBUG
            self.Debug = True
        logging.basicConfig(format='%(asctime)s [%(levelname)s] [%(process)d] %(message)s',
                            filename='/var/log/rdpproxy/auth.log', filemode='a', level=LOG_LEVEL)
        logging.raiseExceptions = False
        self.logging=logging


        if self.Debug: self.logging.debug('Starting authentication plugin...')
        self.sqliteExec("CREATE TABLE IF NOT EXISTS events ( zdate INTEGER, username TEXT, ipclient TEXT, stype INTEGER, subject TEXT)")


    def interactive_target(self, data_to_send):
        data_to_send.update({ u'module' : u'interactive_target' })
        self.shared.send_data(data_to_send)
        _status, _error = self.shared.receive_data()
        if self.shared.get(u'display_message') != u'True':
            _status, _error = False, u'Connection closed by client'
        return _status, _error

    def receive_data(self):
        status, error = self.shared.receive_data()
        if not status:
            raise Exception(error)

        self.logging.info('[INFO] receive_data(): status ' + str(status)+' error:'+str(error))


    def selector_target(self, data_to_send):
        self.shared.send_data({
            u'module': u'selector',
            u'selector': '1',
            u'login': self.shared.get(u'target_login')
        })
        self.receive_data()
        self.shared.send_data(data_to_send)
        self.receive_data()

        if self.shared.is_asked(u'proto_dest'):
            target = self.shared.get(u'login').split(':')
            target_device = target[0]
            target_login = target[1]
            # login = target[2]
            self.shared.shared[u'target_login'] = target_login
            self.shared.shared[u'target_host'] = target_device
            self.shared.shared[u'target_device'] = target_device
            # self.shared.shared[u'target_password'] = '...'
            # self.shared.shared[u'proto_dest'] = 'RDP'
        else:
            # selector_current_page, .....
            pass

    def historySave(self,text,username,ipclient,level):
        timestamp0 = self.CurrentTime()
        sql="INSERT INTO events (zdate,username,ipclient,stype,subject) VALUES ("+str(timestamp0)+",'"+username+"','"+ipclient+"',"+str(level)+",'"+text+"')"
        self.sqliteExec(sql)


    def sqliteExec(self,sql):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        c.execute(sql)
        if conn: conn.close()

    def NumberOfAdGroups(self):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        c.execute("SELECT count(*) as tcount FROM members WHERE ADGROUP = '1'")
        results = c.fetchone()
        return int(results[0])

    def is_activedirectory(self):
        if self.EnableActiveDirectoryFeature==0:
            self.logging.debug("is_activedirectory(): EnableActiveDirectoryFeature == 0")
            return False

        if self.NumberOfAdGroups()==0:
            self.logging.debug("is_activedirectory(): NumberOfAdGroups == 0")
            return False

        return True

    def is_auth_to_ad(self,username,password):
        ActiveDirectoryConnections = unserialize(GET_INFO_STR("ActiveDirectoryConnections"))
        try:
            for Index in ActiveDirectoryConnections:
                ad = ADLDAP(self.logging)
                self.logging.debug("is_auth_to_ad(): " + username + " Testing connection index ["+str(Index)+"]")
                if not ad.TestAuthenticate(Index,username, password): continue
                userdn=ad.get_user_dn(username,Index)
                self.LDAP_SUFFIX=ad.ldap_suffix
                self.LdapIndex=Index
                if self.Debug: self.logging.debug('Authenticated [OK] '+username+ ' DN:['+userdn+']')
                if self.Debug: self.logging.debug('Get Groups from ACLs with'+self.LDAP_SUFFIX)
                if not self.GetObjectsFromSuffix(userdn): continue
                self.username=username
                self.password=password
                return True
        except:
            self.logging.error('Crash while iterate connections...'+tb.format_exc())
        return False

    def GetObjectsFromSuffix(self,userdn):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        suffix=self.LDAP_SUFFIX
        c.execute("SELECT ID,username,endoflife FROM members WHERE username LIKE '%"+suffix+"'")
        results=c.fetchall()

        if results is None: return False

        adldap=ADLDAP(self.logging)
        timestamp0 = self.CurrentTime()
        for line in results:
            ID=line[0]
            username=line[1]
            endoflife=line[2]
            if self.Debug: self.logging.debug("GetObjectsFromSuffix(): Found "+username)

            if timestamp0 > endoflife:
                self.logging.debug("GetObjectsFromSuffix(): " + username+ " End of life, continue")
                continue

            if not adldap.dngroupMatchesDN(self.LdapIndex,username,userdn):
                self.logging.debug("GetObjectsFromSuffix(): " + userdn+ " not inside "+username)
                continue

            self.object_id=ID
            return True

        self.object_id =0
        return False



    def login_to_ad(self,username,password):
        if not self.is_activedirectory(): return 0
        if not self.is_auth_to_ad(username, password): return 0
        if self.Debug: self.logging.debug('Main suffix is "'+self.LDAP_SUFFIX+'"');
        return self.object_id



    def login_ok(self,username,password,ip_client):

        self.logging.debug("login_ok(): Authenticate user:"+username+" with ip %s" % ip_client)
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        timestamp0 = self.CurrentTime()
        c.execute("SELECT ID,password,endoflife FROM members WHERE username = '%s'" % username)
        results=c.fetchone()

        if results is None:
            self.logging.debug("login_ok(): " + username + " Not a local user trying Active Directory")
            ID=self.login_to_ad(username,password)
            if ID > 0: return ID
            self.logging.error('[AUTH]: ' + username + ' [' + ip_client + '] no such member')
            self.historySave("No such member",username,ip_client,0)
            return 0

        ID=int(results[0])
        if ID==0:
            ID=self.login_to_ad(username,password)
            if ID > 0: return ID
            self.logging.error('[AUTH]: '+username +' ['+ip_client+'] no such member')
            self.historySave("No such member", username, ip_client, 0)
            return 0

        endoflife=results[2]
        if timestamp0 > endoflife:
            self.logging.error('[AUTH]: ' + username + ' [' + ip_client + '] End of life')
            self.historySave("Member End of Life", username, ip_client, 0)
            return 0


        password2=results[1]
        password1 = hashlib.md5(password).hexdigest()

        if password1==password2: return ID
        self.historySave("Bad password", username, ip_client, 0)
        self.logging.error('[AUTH]: ' + password1 + '<>' + password2 + '] bad password')
        self.logging.error('[AUTH]: ' + username + ' [' + ip_client + '] bad password')
        return 0

    def IsRuleMatchesUserid(self,ruleid,userid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        c.execute("SELECT ID FROM link_members WHERE userid = '%s' AND gpid = '%s'" % (userid,ruleid))
        results = c.fetchone()
        if results == None:
            if conn: conn.close()
            return False

        if conn: conn.close()
        return True
        pass


    def IsRuleMatchesTime(self,ruleid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        weekDays = ("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")
        thisXMas = datetime.datetime.now()
        CurrentDay=thisXMas.weekday()
        CurrentDayText=weekDays[CurrentDay]
        self.logging.debug('[TIME]: Current day %s' % CurrentDayText)
        RulesCount=0
        FinalResult=False
        timestamp0 = self.CurrentTime()


        for row in c.execute("SELECT tday,thour1,thour2 FROM timeline WHERE gpid = '%s'" % ruleid):
            RulesCount+=1
            tday=row[0]
            thour1=row[1]
            thour2=row[2]
            tdayText=weekDays[tday]
            if tday!=CurrentDay:
                self.logging.debug('[TIME]: FAILED: Current day %s <> %s' % (CurrentDayText,tdayText))
                continue

            self.logging.debug('[TIME]: OK day %s == %s' % (CurrentDayText, tdayText))
            timestamp1 =self.HourToTimeStamp(thour1)
            timestamp2 =self.HourToTimeStamp(thour2)
            self.logging.debug('[TIME]: %s < %s > %s' % (timestamp1, timestamp0, timestamp2))

            if timestamp0 <  timestamp1:
                self.logging.debug('[TIME]: FAILED:%s < %s' % (timestamp0,timestamp1))
                continue
            if timestamp0 >  timestamp2:
                self.logging.debug('[TIME]: FAILED: %s > %s' % (timestamp0, timestamp2))
                continue;
            self.logging.debug('[TIME]:  SUCCESS !')
            return True

        if RulesCount==0:return True
        return FinalResult
        pass

    def RuleEndTime(self,ruleid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        thisXMas = datetime.datetime.now()
        CurrentDay=thisXMas.weekday()
        timestamp0 = self.CurrentTime()


        for row in c.execute("SELECT tday,thour1,thour2 FROM timeline WHERE gpid = '%s'" % ruleid):
            tday=row[0]
            thour1=row[1]
            thour2=row[2]
            if tday!=CurrentDay:continue

            timestamp1 =self.HourToTimeStamp(thour1)
            timestamp2 =self.HourToTimeStamp(thour2)
            self.logging.debug('[TIME]: %s < %s > %s' % (timestamp1, timestamp0, timestamp2))

            if timestamp0 <  timestamp1: continue;
            if timestamp0 >  timestamp2: continue;

            return timestamp2

        return 99999999999
        pass

    def UserEndTime(self,userid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        c.execute("SELECT endoflife FROM members WHERE ID = '%s'" % userid)
        results = c.fetchone()
        endoflife=int(results[0])
        conn.close()
        return endoflife



    def CurrentTime(self):
        thisXMas = datetime.datetime.now()
        strtime = datetime.datetime.strftime(thisXMas, "%Y-%m-%d %H:%M:%S")
        return self.strtime2TimeStamp(strtime)

    def HourToTimeStamp(self,strhour):
        thisXMas = datetime.datetime.now()
        TimePrefix = datetime.datetime.strftime(thisXMas, "%Y-%m-%d")
        strtime = TimePrefix + " " + strhour
        return self.strtime2TimeStamp(strtime)

    def strtime2TimeStamp(self,strtime):
        tt = datetime.datetime.strptime(strtime, "%Y-%m-%d %H:%M:%S").timetuple()
        return int(mktime(tt))


    def RulesFromIP(self,ip_client,userid):
        zRules=[]
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        for row in c.execute('SELECT gpid,pattern FROM networks'):
            ruleid=row[0]
            Network=row[1]

            if Network=="0.0.0.0/0":
                if not self.IsRuleMatchesUserid(ruleid, userid):
                    self.logging.debug('[AUTH]: Check: userid:' + str(userid) + ' not in policy [' + str(ruleid) + ']')
                    continue

                if not self.IsRuleMatchesTime(ruleid):
                    self.logging.debug('[AUTH]: Check: userid:' + str(userid) + ' not in Time policy [' + str(ruleid) + ']')
                    continue

                zRules.append(ruleid)
                continue





            self.logging.debug('[AUTH]: Check: ' + ip_client + ' in [' + Network + ']')
            if not IPAddress(ip_client) in IPNetwork(Network):
                self.logging.debug('[AUTH]: Check: ' + ip_client + ' in [' + Network + '] NO MATCH')
                continue

            if not self.IsRuleMatchesUserid(ruleid,userid):
                self.logging.debug('[AUTH]: Check: userid:' + str(userid) + ' not in policy [' + str(ruleid) + ']')
                continue

            if not self.IsRuleMatchesTime(ruleid):
                self.logging.debug('[AUTH]: Check: userid:' + str(userid) + ' not in Time policy [' + str(ruleid) + ']')
                continue

            self.logging.debug('[AUTH]: Append rule id:'+str(ruleid))
            zRules.append(ruleid)

        if conn: conn.close()
        return zRules

    def ip_resolveComputer(self,computername):
        try:
            CompIP = socket.gethostbyname(computername)
            return CompIP
        except Exception as e:
            self.logging.error('Error resolving DNS for {}: {}'.format(computername, e))
            return None

    def ip_CheckRDPPort(self,ipaddr,target_port):
        a_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        a_socket.settimeout(1.0)
        location = (ipaddr, target_port)
        try:
            result_of_check = a_socket.connect_ex(location)
        except:
            self.logging.error('Error connection {}: {}'.format(ipaddr, e))
            return False


        if result_of_check == 0:
            self.logging.debug('[{}]: Port {} is Open'.format(ipaddr,target_port))
            a_socket.close()
            return True

        self.logging.debug('[{}]: Port {} is Closed'.format(ipaddr,target_port))
        a_socket.close()
        return False


    def BuildSelector(self,Rulez):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        d = conn.cursor()
        target_login=[]
        target_device=[]
        proto_dest=[]
        Already={}


        for ruleid in Rulez:
            self.logging.debug('[BUILD]: ' + str(ruleid) )
            try:
                for row in c.execute("SELECT targetid FROM link_target WHERE gpid = '%s'" % ruleid):
                    targetid=row[0]
                    if targetid in Already: continue
                    Already[targetid]=True
                    self.logging.debug('[BUILD]: open target id '+str(targetid))
                    d.execute("SELECT alias,designation,proto_dest,enabled,target_port,DontResolve FROM targets WHERE ID = '%s'" % targetid);
                    ligne = d.fetchone()
                    enabled=ligne[3]
                    target_port=ligne[4]
                    DontResolve=ligne[5]
                    alias =ligne[0]
                    designation = ligne[1]
                    RdpORVNC=ligne[2]
                    enabled = int(enabled)
                    if enabled == None: enabled = 0
                    if enabled == 0: continue
                    if designation == None: designation="designation"
                    if alias == None: alias="Alias"

                    matches = re.match('(CN|OU|DC)=.*?,', alias)
                    if matches:
                        self.logging.debug("Found Active Directory DN an alias ["+alias+"]")
                        ad=ADLDAP(self.logging)
                        List=ad.ListComputerFromGroupDN(alias)
                        if List is None: continue
                        for tempComp in List:
                            matches=re.match('(.*?)@(.*)',tempComp)
                            CompName=matches.group(1)
                            CompDesc=matches.group(2)
                            if DontResolve == 0:
                                CompIP=self.ip_resolveComputer(CompName)
                                if CompIP is None: continue
                                if CompIP =='127.0.0.1': continue
                                if not self.ip_CheckRDPPort(CompIP,target_port): continue

                            prefix = u'%s.%s.%s' % (ruleid, targetid,CompName)
                            proto_dest.append(RdpORVNC)
                            target_login.append(prefix + ") " + CompName)
                            target_device.append(CompDesc)


                        continue



                    prefix=u'%s.%s' % (ruleid,targetid)

                    proto_dest.append(RdpORVNC)
                    self.logging.debug('[BUILD]: open target ' + str(alias)+" proto:"+ligne[2])
                    target_login.append(prefix+") "+alias)
                    target_device.append(designation)
            except sqlite3.Error as e:
                self.logging.error("Database error: %s" % e)
            except Exception as e:
                self.logging.error("Exception in _query: %s" % e)




        implode_target_login=u"\x01".join(target_login)
        implode_target_device=u"\x01".join(target_device)
        implode_proto_dest=u"\x01".join(proto_dest)
        if conn: conn.close()

        selector_data = {
            u'target_login': implode_target_login,
            u'target_device': implode_target_device,
            u'proto_dest': implode_proto_dest,
        }

        return selector_data

    def IsRuleRec(self,ruleid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        try:
            c.execute("SELECT user_rec FROM groups WHERE ID = '%s'" % ruleid)
        except sqlite3.Error as e:
            self.logging.error("Database error: %s" % e)
            if conn: conn.close()
            return 0
        except Exception as e:
            self.logging.error("Exception in _query: %s" % e)
            if conn: conn.close()
            return 0

        results = c.fetchone()
        if conn: conn.close()
        if results == None: return 0
        return int(results[0])


    def UserExists(self,username,ip_client):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        timestamp0 = self.CurrentTime()
        c.execute("SELECT ID,password,endoflife FROM members WHERE username = '%s'" % username)
        results=c.fetchone()
        if conn: conn.close()
        self.username=username

        if results==None:
            if self.Debug: self.logging.debug("UserExists(): " + username + " [" + ip_client + "] no such member (None)")
            if self.is_activedirectory():
                if self.Debug: self.logging.debug("UserExists(): Active Directory Enabled, try to found  " + username)
                ad=ADLDAP(self.logging)
                if ad.UserExistsInAll(username):
                    if self.Debug: self.logging.debug("UserExists(): " + username+" [OK]")
                    return True


            self.logging.error('[AUTH]: ' + username + ' [' + ip_client + '] no such member (None)')
            self.historySave("No such member", username, ip_client, 0)
            return False

        ID=int(results[0])
        if ID==0:
            if self.is_activedirectory():
                if self.Debug: self.logging.debug("UserExists(): Active Directory Enabled, try to found  " + username)
                ad=ADLDAP(self.logging)
                if ad.UserExistsInAll(username):
                    if self.Debug: self.logging.debug("UserExists(): " + username+" [OK]")
                    return True

            self.logging.error('[AUTH]: '+username +' ['+ip_client+'] no such member (Index)')
            self.historySave("No such member", username, ip_client, 0)
            return False

        endoflife=results[2]
        if timestamp0 > endoflife:
            self.logging.error('[AUTH]: ' + username + ' [' + ip_client + '] End of life')
            self.historySave("End of life", username, ip_client, 0)
            return False

        return True

    def cut_message(self,message, width=75, in_cr='\n', out_cr='<br>', margin=6):
        result = []
        for line in message.split(in_cr):
            while len(line) > width:
                end = line[width:].split(' ')

                if len(end[0]) <= margin:
                    result.append((line[:width] + end[0]).rstrip())
                    end = end[1:]
                else:
                    result.append(line[:width] + end[0][:margin] + '-')
                    end[0] = '-' + end[0][margin:]

                line = ' '.join(end)

            result.append(line.rstrip())

        return out_cr.join(result)



    def BuildConnection(self,ruleid,targetid,userid,CompName,AD):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c=conn.cursor();
        c.execute("SELECT session_time FROM groups WHERE ID = '%s'" % ruleid);
        ligne = c.fetchone()
        if ligne[0]==None:
            session_time_params=0
        else:
            if ligne[0]=='':
                session_time_params=0
            else:
                session_time_params=int(ligne[0])

        strdecon = u"2099-12-31 23:59:59"
        DefaultEndTime=self.strtime2TimeStamp(strdecon)
        EndTime=self.RuleEndTime(ruleid)
        EndTimeUser=self.UserEndTime(userid)

        if DefaultEndTime<EndTime:
            decon = datetime.datetime.fromtimestamp(EndTime)
            strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")


        if session_time_params>0:
            decon = datetime.datetime.now() + timedelta(minutes=session_time_params)
            strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")
            CutSession=self.strtime2TimeStamp(strdecon)
            if CutSession>EndTime:
                decon=datetime.datetime.fromtimestamp(EndTime)
                strdecon = datetime.datetime.strftime(decon, "%Y-%m-%d %H:%M:%S")


        CalculatedStamp=self.strtime2TimeStamp(strdecon)
        self.logging.debug("Target: %s Normally disconnect at <%s>, End of Life <%s>" % (targetid,CalculatedStamp, EndTimeUser))
        if EndTimeUser<CalculatedStamp:
            strdecon = datetime.datetime.strftime(datetime.datetime.fromtimestamp(EndTimeUser), "%Y-%m-%d %H:%M:%S")



        self.logging.info("Target: %s Deconnection time: %s" % (targetid,strdecon))
        tt = datetime.datetime.strptime(strdecon, "%Y-%m-%d %H:%M:%S").timetuple()

        c.execute("SELECT target_login,target_password,target_host,target_device,target_port,proto_dest,mode_console,session_probe FROM `targets` WHERE ID = '%s'" % targetid);
        ligne = c.fetchone()
        if conn: conn.close()
        if AD==0:
            target_login=ligne[0]
            target_password=ligne[1]
            target_host=ligne[2]
            target_device=ligne[3]
            target_port=int(ligne[4])
            proto_dest=ligne[5]
        else:
            target_login=self.username
            target_password=self.password
            target_host=CompName
            target_device = CompName
            target_port=int(ligne[4])
            proto_dest=ligne[5]


        if ligne[6]==None:
            mode_console=0
        else:
            mode_console = int(ligne[6])

        if ligne[7] == None:
            session_probe=0
        else:
            session_probe=int(ligne[7])

        kv = {}

        matches=re.search('^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$',target_device)
        if matches:
            if target_device =="0.0.0.0":
                target_device=""
            else:
                target_host=target_device
                target_device=""

        timestamp0 = self.CurrentTime()
        session_id=datetime.datetime.now().strftime("%Y-%m-%d-%H-%M")+":"+str(ruleid)+"-"+str(targetid)+"-"+str(userid)
        is_rec=self.IsRuleRec(ruleid)
        if is_rec==1:
            kv[u'is_rec'] = u'1'
            kv['record_filebase'] = str(ruleid)+"-"+str(targetid)+"-"+str(userid)
            kv['record_subdirectory'] = str(timestamp0)

        if is_rec==0:
            kv[u'is_rec'] = u'0'

        kv[u'login'] = target_login
        kv[u'proto_dest'] = proto_dest
        kv[u'target_port'] = target_port
        kv[u'session_id'] = session_id
        kv[u'module'] = proto_dest
        if mode_console == 1: kv[u'mode_console'] = u"allow"
        if mode_console == 0: kv[u'mode_console'] = u"forbid"
        kv[u'timeclose'] = int(mktime(tt))
        kv[u'target_password']=target_password
        kv[u'target_login'] = target_login
        kv[u'target_host']=target_host
        kv[u'target_device'] = target_device
        #kv[u'session_log_path']=None
        #kv[u'session_log_path'] = u'/var/log/rdpproxy'
        if session_probe == 0: kv[u'session_probe'] = u'0'
        if session_probe == 1: kv[u'session_probe'] = u'1'

        return kv


    def sqlite_delete_session(self,database_id,psid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        try:
            c.execute("DELETE FROM rdpproxy_sessions WHERE psid ='%s'" % psid)
            c.execute("DELETE FROM rdpproxy_sessions WHERE ID ='%s'" % database_id)
            self.logging.debug("sqlite_delete_session() DELETE FROM rdpproxy_sessions WHERE....")
            conn.commit()
            conn.close()
        except:
            self.logging.error("SQLite Error 842")

    def sqlite_update_session(self,psid,intTime,database_id):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        try:
            c.execute("UPDATE rdpproxy_sessions SET psid ='%s', xtime ='%s' WHERE ID='%s'" % (psid, intTime, database_id))
            self.logging.debug(self.username + " UPDATE rdpproxy_sessions <%s>...." % database_id)
            conn.commit()
            conn.close()
        except sqlite3.Error as e:
            self.logging.error("DELETE FROM rdpproxy_sessions Database error: %s" % e)
            if conn: conn.close();
        except Exception as e:
            self.logging.error("DELETE FROM rdpproxy_sessions in _query: %s" % e)
            if conn: conn.close();

    def sqlite_create_session(self,intTime, session_id, psid, ip_client, userid, target_login):

        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()

        try:
            self.historySave("Open RDP session", self.username, ip_client, 1)
            c.execute(
                "INSERT OR IGNORE INTO rdpproxy_sessions (created,xtime,sessionid,psid,ip_client,userid,target_login,pkill) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s')" % (
                intTime, intTime, session_id, psid, ip_client, userid, target_login, 0))
            self.logging.debug(self.username + " INSERT rdpproxy_sessions <%s>...." % psid)
            conn.commit()
            conn.close()
        except sqlite3.Error as e:
            self.logging.error("INSERT rdpproxy_sessions Database error: %s" % e)
            if conn: conn.close()
        except Exception as e:
            self.logging.error("INSERT rdpproxy_sessions Exception in _query: %s" % e)
            if conn: conn.close()


    def sqlite_get_database_id(self,psid):
        conn = sqlite3.connect('/home/artica/SQLITE/rdpproxy.db')
        c = conn.cursor()
        c.execute("SELECT ID,pkill FROM rdpproxy_sessions WHERE psid ='%s'" % psid)
        row = c.fetchone()
        try:
            database_id = int(row[0])
        except:
            database_id = 0

        try:
            pkill = row[1]
        except:
            pkill = 0

        pkill = int(pkill)

        return database_id,pkill



    def start(self):
        _status, _error = self.shared.receive_data()
        isAD=0
        database_id=0
        ComputerName=''
        UserName=self.shared.get(u'login')
        Password=self.shared.get(u'password')
        if Password == MAGICASK: Password=''
        ip_client=self.shared.get(u'ip_client')
        self.logging.debug(UserName+' Password lenght '+str(len(Password)))

        if (len(Password) > 0): self.logging.debug(UserName+ 'Password: "' + Password+'"')

        if(len(Password)==0):

            if len(UserName)>1:
                if not self.UserExists(UserName,ip_client):
                    self.logging.debug('Closing connection because UserExists() return false')
                    self.shared.send_data({u'module': u'close'})
                    return False
            else:
                UserName=MAGICASK


            interactive_data = {
                u'target_password': self.shared.get(u'password', MAGICASK),
                u'target_login': UserName,
                u'target_device': ip_client,
                u'target_host': ip_client

                }

            _status, _error = self.interactive_target(interactive_data)

            Password = self.shared.get(u'target_password')
            target_device=self.shared.get(u'target_device')

            if UserName==MAGICASK:
                UserName=self.shared.get(u'target_login')
                self.logging.info('User <%s>' % UserName)
                if not self.UserExists(UserName,ip_client):
                    self.logging.debug('Closing connection because UserExists() return false')
                    self.shared.send_data({u'module': u'close'})
                    return False



            self.logging.debug('New password after interactive_target "'+Password+'"')
            self.logging.debug('Device after interactive_target "' + target_device + '"')

        if (len(Password) == 0):
            self.logging.info('[AUTH]: ' + UserName + ' [' + ip_client + '] no password typed.')
            self.shared.send_data({u'module': u'close'})
            return False



        userid=self.login_ok(UserName,Password,ip_client)
        if userid==0:
            self.logging.debug('Closing connection because login_ok() return 0')
            self.shared.send_data({u'module': u'close'})
            return False


        Rules=self.RulesFromIP(ip_client,userid)
        self.logging.debug('RulesFromIP() return '+str(len(Rules))+" Rule(s)")
        if len(Rules)==0:

            self.logging.error('AUTH]: ' + UserName + ' [' + ip_client + '] No policy associated to this account')
            self.shared.send_data({u'module': u'close'})
            return False

        selector_data = self.BuildSelector(Rules)
        self.selector_target(selector_data)
        selected_device=self.shared.get(u'target_login')
        matches = re.search('^([0-9]+)\.([0-9]+)\)', selected_device)

        if not matches:
            self.logging.debug(UserName + " Try with ^([0-9]+)\.([0-9]+)\.(.*?)\) Selected <%s>" % selected_device)
            matches = re.search('^([0-9]+)\.([0-9]+)\.(.+?)\)', selected_device)
            if not matches:
                self.logging.error(UserName + " Err 913: Selected <%s> is not understood" % selected_device)
                self.shared.send_data({u'module': u'close'})
                return False
            ruleid       = int(matches.group(1))
            targetid     = int(matches.group(2))
            ComputerName = matches.group(3)
            isAD         = 1
            self.logging.debug(UserName + " Selected <%s> rule.%s target.%s" % (selected_device, ruleid, targetid))


        ruleid=int(matches.group(1))
        targetid=int(matches.group(2))
        self.logging.debug(UserName + " Selected <%s> rule.%s target.%s" % (selected_device,ruleid,targetid))
        kv=self.BuildConnection(ruleid,targetid,userid,ComputerName,isAD)
        self.shared.send_data(kv)

        try_next = False
        signal.signal(signal.SIGUSR1, self.kill_handler)
        try:
            self.shared.send_data(kv)

            # Looping on keepalived socket
            if self.Debug:  self.logging.info(u"Starting Loop")
            while True:
                r = []
                if self.Debug:  self.logging.info(u"Waiting on proxy")
                got_signal = False
                try:
                    r, w, x = select([self.proxy_conx], [], [], 60)
                except Exception as e:

                    if self.Debug: self.logging.info("exception: '%s'" % e)
                    import traceback
                    self.logging.debug("<<<<%s>>>>" % traceback.format_exc(e))
                    if e[0] != 4:
                        raise
                    self.logging.info("Got Signal %s" % e)

                    got_signal = True
                if self.proxy_conx in r:
                    _status, _error = self.shared.receive_data()
                    FOUND_STATUS=False
                    reporting = self.shared.get(u'reporting')
                    session_id = self.shared.get(u'session_id')
                    UserName = self.shared.get(u'login')
                    psid = self.shared.get(u'psid')
                    ip_client=self.shared.get(u'ip_client')
                    target_login=self.shared.get(u'target_login')
                    disconnect_reason_ack=self.shared.get(u'disconnect_reason_ack')
                    module = self.shared.get(u'module')

                    self.logging.debug(UserName + " reporting <%s>" % reporting)
                    self.logging.debug(UserName + " session_id <%s>" % session_id)
                    self.logging.debug(UserName + " psid <%s>" % psid)
                    self.logging.debug(UserName + " ip_client <%s>" % ip_client)
                    self.logging.debug(UserName + " target_login <%s>" % target_login)
                    self.logging.debug(UserName + " disconnect_reason_ack <%s>" % disconnect_reason_ack)
                    self.logging.debug(UserName + " module <%s>" % module)

                    self.logging.info("Reporting: '%s'" % reporting)
                    matches=re.search("OPEN_SESSION_SUCCESSFUL",reporting)
                    if matches:
                        FOUND_STATUS=True
                        database_id,pkill = self.sqlite_get_database_id(psid)
                        self.logging.debug(UserName + " pkill = <%s>...." % pkill)

                        if pkill == 1:
                            self.logging.info("Got order to remove Session %s/%s" % (psid, database_id))
                            self.sqlite_delete_session(database_id,psid)
                            self.proxy_conx.close()
                            break


                        if pkill == 0:
                            strtime=datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                            intTime=self.strtime2TimeStamp(strtime)
                            if database_id > 0:
                                self.sqlite_update_session(psid,intTime,database_id)
                            if database_id == 0:
                                self.logging.info("Create new session %s" % psid)
                                self.sqlite_create_session(intTime, session_id, psid, ip_client, userid, target_login)

                    self.logging.debug("Continue reporting <%s>",reporting)

                    matches = re.search("CLOSE_SESSION_SUCCESSFUL",reporting)
                    if matches:
                        FOUND_STATUS = True
                        database_id, pkill = self.sqlite_get_database_id(psid)
                        self.historySave("Close RDP session", UserName, ip_client, 2)
                        self.sqlite_delete_session(database_id, psid)
                        self.logging.info("Session %s/%s was stopped" % (psid, database_id))

                    if not FOUND_STATUS:
                        self.logging.error("CANNOT UNDERSTAND REPORTING [%s]" % reporting)

                    if self.shared.is_asked(u'keepalive'):
                        self.shared.send_data({u'keepalive': u'True'})
                # r can be empty
                else: # (if self.proxy_conx in r)
                    self.logging.info(u'Missing Keepalive')
                    self.logging.error(u'break connection')
                    release_reason = u'Break connection'
                    break
            self.logging.debug(u"End Of Keep Alive")


        except AuthentifierSocketClosed as e:
            if DEBUG:
                import traceback
                self.logging.info(u"RDP/VNC connection terminated by client")
                self.logging.info("<<<<%s>>>>" % traceback.format_exc(e))
        except Exception as e:
            if DEBUG:
                import traceback
                self.logging.info(u"RDP/VNC connection terminated by client")
                self.logging.info("<<<<%s>>>>" % traceback.format_exc(e))

        try:
            self.logging.info(u"Close connection ...")

            self.proxy_conx.close()

            self.logging.info(u"Close connection done.")
        except IOError:
            if DEBUG:
                self.logging.info(u"Close connection: Exception")
                self.logging.info("<<<<%s>>>>" % traceback.format_exc(e))
    # END METHOD - START


    def kill_handler(self, signum, frame):
        # self.logging.info("KILL_HANDLER = %s" % signum)
        if signum == signal.SIGUSR1:
            self.kill()

    def kill(self):
        try:
            self.logging.info(u"Closing a RDP/VNC connection")
            self.proxy_conx.close()
        except Exception:
            pass




import logging
from socket import fromfd
from socket import AF_UNIX
from socket import AF_INET
from socket import SOCK_STREAM
from socket import SOL_SOCKET
from socket import SO_REUSEADDR
from select import select


socket_path = '/var/run/rdpproxy/auth.sock'

def writePidFile():
    pid = str(os.getpid())
    f = open('/var/run/rdpproxy/auth.pid', 'w')
    f.write(pid)
    f.close()

def standalone():
    writePidFile()
    RDPProxyAuthookDebug = GET_INFO_INT("RDPProxyAuthookDebug")
    RDPProxyAuthookDebug =1
    LOG_LEVEL = logging.INFO
    if RDPProxyAuthookDebug==1: LOG_LEVEL = logging.DEBUG
    logging.basicConfig(format='%(asctime)s [%(levelname)s] [%(process)d] %(message)s',filename='/var/log/rdpproxy/auth.log', filemode='a', level=LOG_LEVEL)
    if os.path.exists(socket_path): os.unlink(socket_path)
    logging.info('Open socket at %s' % socket_path)
    if RDPProxyAuthookDebug == 1: logging.info('Service is turned in debug mode')
    signal.signal(signal.SIGCHLD, signal.SIG_IGN)
    # create socket from bounded port
    s1 = socket.socket(AF_UNIX, SOCK_STREAM)
    s1.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
    s1.bind(socket_path)
    s1.listen(100)

    s2 = socket.socket(AF_INET, SOCK_STREAM)
    s2.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
    s2.bind(('127.0.0.1', 3450))
    s2.listen(100)

    try:
        while 1:
            rfds, wfds, xfds = select([s1, s2], [], [], 1)
            for sck in rfds:
                if sck in [s1, s2]:
                    client_socket, client_addr = sck.accept()
                    child_pid = os.fork()
                    if child_pid == 0:
                        signal.signal(signal.SIGCHLD, signal.SIG_DFL)
                        sck.close()
                        server = ACLPassthrough(client_socket, client_addr)
                        server.start()
                        sys.exit(0)
                    else:
                        client_socket.close()
                        #os.waitpid(child_pid, 0)

    except KeyboardInterrupt:
        if client_socket:
            client_socket.close()
        logging.info('Terminate program')
        sys.exit(1)
    except socket.error as e:
        pass
    except AuthentifierSocketClosed as e:
        logging.debug("Authentifier Socket Closed")
    except Exception as e:
        logging.debug("Authentifier exeception %s" % e)

if __name__ == '__main__':

    standalone()
