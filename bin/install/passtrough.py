#!/usr/bin/env -S python3 -O
# -*- coding: utf-8 -*-
##
# Author(s): Meng Tan
# Module description:  Passthrough ACL
# Allows RDPProxy to connect to any server RDP.
##
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
from rdsproxyclass import *
from unix import *
from inspect import currentframe
import random
import os
import signal
import traceback
import sys
from datetime import datetime
import syslog
from logger import Logger
from struct import unpack_from, pack
from select     import select
import socket
import traceback as tb
import re

# import uuid # for random rec_path


MAGICASK = u'UNLIKELYVALUEMAGICASPICONSTANTS3141592926ISUSEDTONOTIFYTHEVALUEMUSTBEASKED'
IsRDPProxyAuthDebug=GET_INFO_INT("IsRDPProxyAuthDebug");
DEBUG = False
if IsRDPProxyAuthDebug==1: DEBUG = True

if DEBUG:
    import pprint

class AuthentifierSocketClosed(Exception):
    pass

class AuthentifierSharedData():
    def __init__(self, conn):
        self.proxy_conx = conn
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
            u'proto_dest':      MAGICASK,
        }
        if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: AuthentifierSharedData initialize..")

    def send_data(self, data):
        u""" NB : Strings sent to the ReDemPtion proxy MUST be UTF-8 encoded """

        if DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: AuthentifierSharedData::send_data (update) =\n%s" % (pprint.pformat(data)))

        self.shared.update(data)

        def _toval(key, value):
            key = key.encode('utf-8')
            try:
                value = value.encode('utf-8')
            except:
                # int, etc
                value = str(value).encode('utf-8')
            key_len = len(key)
            value_len = len(value)
            return (
                True, key,
                pack(f'>1sB{key_len}sL{value_len}s',
                     b'!', key_len, key, value_len, value)
            )

        def _toask(key):
            key = key.encode('utf-8')
            key_len = len(key)
            return (
                False, key,
                pack(f'>1sB{key_len}s', b'?', key_len, key)
            )

        _list = [(_toval(key, value) if value != MAGICASK else _toask(key))
                   for key, value in data.items()]
        _list.sort()

        if DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: AuthentifierSharedData::send_data (on the wire) length = %s" % len(_list))

        _r_data = b''.join(t[2] for t in _list)
        self.proxy_conx.sendall(pack('>H', len(_list)))
        self.proxy_conx.sendall(_r_data)

    def receive_data(self):
        u""" NB : Strings coming from the ReDemPtion proxy are UTF-8 encoded """

        def read_sck():
            try:
                d = self.proxy_conx.recv(65536)
                if len(d):
                    if DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: AuthentifierSharedData::receive_data [%s]" % d)
                    return d

            except BrokenPipeError:
                syslog.syslog(syslog.LOG_INFO, "Socket close due to a Broken Pipe")
                raise AuthentifierSocketClosed()


            except Exception:
                syslog.syslog(syslog.LOG_INFO, "[ERROR]:Failed to read data from authentifier socket %s" %  traceback.format_exc(e))
                raise AuthentifierSocketClosed()

            if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: received_buffer (empty packet)")
            raise AuthentifierSocketClosed()

        class Buffer:
            def __init__(self):
                self._data = read_sck()
                self._offset = 0

            def reserve_data(self, n):
                while len(self._data) - self._offset < n:
                    if DEBUG:
                        syslog.syslog(syslog.LOG_INFO,"[DEBUG]: received_buffer (big packet) "\
                                      "old = %d / %d ; required = %d"
                                      % (self._offset, len(self._data), n))
                    self._data = self._data[self._offset:] + read_sck()
                    self._offset = 0

            def extract_name(self, n):
                self.reserve_data(n)
                _name = self._data[self._offset:self._offset+n].decode('utf-8')
                self._offset += n
                return _name

            def unpack(self, fmt, n):
                self.reserve_data(n)
                r = unpack_from(fmt, self._data, self._offset)
                self._offset += n
                return r

            def is_empty(self):
                return len(self._data) == self._offset

        _buffer = Buffer()
        _data = {}

        while True:
            _nfield, = _buffer.unpack('>H', 2)

            if DEBUG:
                syslog.syslog(syslog.LOG_INFO, "[DEBUG]: received_buffer (nfield) = %d" % (_nfield,))

            for _ in range(0, _nfield):
                _type, _n = _buffer.unpack("BB", 2)
                _key = _buffer.extract_name(_n)
                if DEBUG:syslog.syslog(syslog.LOG_INFO,"[DEBUG]: received_buffer (key)   = %s%s" % ('?' if _type == 0x3f else '!', _key,))

                if _type == 0x3f: # b'?'
                    _data[_key] = MAGICASK
                else:
                    _n, = _buffer.unpack('>L', 4)
                    _data[_key] = _buffer.extract_name(_n)

                    if DEBUG:
                        syslog.syslog(syslog.LOG_INFO,"[DEBUG]: received_buffer (value) = %s"% (_data[_key],))

            if _buffer.is_empty():
                break

            if DEBUG:
                syslog.syslog(syslog.LOG_INFO,"[DEBUG]: received_buffer (several packet)")

        self.shared.update(_data)

        if DEBUG:
            syslog.syslog(syslog.LOG_INFO,"[DEBUG]: receive_data (is asked): =\n%s" % (pprint.pformat(
                [e[0] for e in self.shared.items()])))

        return True, u''

    def get(self, key, default=None):
        return self.shared.get(key, default)

    def is_asked(self, key):
        return self.shared.get(key) == MAGICASK


class ACLPassthrough():
    def __init__(self, conn, addr):
        self.proxy_conx = conn
        self.addr       = addr
        self.shared = AuthentifierSharedData(conn)
        self.ArtcaAuth = rdsrdp(DEBUG)
        self.AllowAuthenticateScreen = GET_INFO_INT("AllowAuthenticateScreen")
        self.RDPRejectErrors         = GET_INFO_INT("RDPRejectErrors")
        if(len(self.addr)>2):
            syslog.syslog(syslog.LOG_INFO, "[INFO]: ACLPassthrough receive connection from [%s]" % self.addr)


    def interactive_target(self, data_to_send):
        if DEBUG:syslog.syslog(syslog.LOG_INFO, "[DEBUG]: ACLPassthrough::interactive_target [%s]" % data_to_send)
        data_to_send.update({ u'module' : u'interactive_target' })
        self.shared.send_data(data_to_send)
        _status, _error = self.shared.receive_data()
        if self.shared.get(u'display_message') != u'True':
            _status, _error = False, u'Connection closed by client'
        return _status, _error

    def receive_data(self):
        status, error = self.shared.receive_data()
        if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: ACLPassthrough::receive_data [%s] (%s)" % (status, error))
        if not status:
            raise Exception(error)


    def send_error_user(self,text):
        if self.RDPRejectErrors == 0:
            data_to_send = {
                u'module': u'confirm',
                u'message': u'%s' % text,
            }
            self.shared.send_data(data_to_send)
            _status, _error = self.shared.receive_data()
            if self.shared.get(u'display_message') != u'True':
                _status, _error = False, u'Connection closed by client'

        self.shared.send_data({u'module': u'close'})


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
            self.shared.shared[u'real_target_device'] = target_device
            # self.shared.shared[u'target_password'] = '...'
            # self.shared.shared[u'proto_dest'] = 'RDP'
        else:
            # selector_current_page, .....
            pass

    def ensureUtf(self,s, encoding='utf8'):
      if type(s) == bytes:
        return s.decode(encoding, 'ignore')
      else:
        return s

    def get_linenumber(self):
        cf = currentframe()
        return cf.f_back.f_lineno

    def start(self):
        SET_INFO("RDPPROXY_AUTH_VERSION", version)
        _status, _error = self.shared.receive_data()
        ComputerName        = ''
        matches             = False
        target_host         = ''
        target_device       = ""
        target_port         = 0
        kv                  = {}
        AuthorizeTSElogin   = 0
        command             = 0
        isAD                = 0
        UserName            = self.shared.get(u'login')
        Password            = self.shared.get(u'password')
        selected_device     = self.shared.get(u'target_login')
        RDP_ERROR_MSG1      = GET_INFO_STR("RDP_ERROR_MSG1")
        RDP_ERROR_MSG2      = GET_INFO_STR("RDP_ERROR_MSG2")
        RDP_ERROR_MSG3      = GET_INFO_STR("RDP_ERROR_MSG3")
        RDP_ERROR_MSG4      = GET_INFO_STR("RDP_ERROR_MSG4")
        RDP_ERROR_MSG5      = GET_INFO_STR("RDP_ERROR_MSG5")
        RDP_ERROR_MSG6      = GET_INFO_STR("RDP_ERROR_MSG6")
        RDP_ERROR_MSG7      = GET_INFO_STR("RDP_ERROR_MSG7")
        RDP_ERROR_MSG8      = GET_INFO_STR("RDP_ERROR_MSG8")


        if len(RDP_ERROR_MSG1) == 0: RDP_ERROR_MSG1="No password entered in your TSE client";
        if len(RDP_ERROR_MSG2) == 0: RDP_ERROR_MSG2="Internal Error, please contact your administrator"
        if len(RDP_ERROR_MSG3) == 0: RDP_ERROR_MSG3="Your are not authorized to use this method"
        if len(RDP_ERROR_MSG4) == 0: RDP_ERROR_MSG4="Your Computer is not authorized to use this method"
        if len(RDP_ERROR_MSG5) == 0: RDP_ERROR_MSG5 = "Permission denied"
        if len(RDP_ERROR_MSG6) == 0: RDP_ERROR_MSG6 = "Please prepare credentials before connecting"
        if len(RDP_ERROR_MSG7) == 0: RDP_ERROR_MSG7 = "Non-existent user"
        if len(RDP_ERROR_MSG8) == 0: RDP_ERROR_MSG8 = "No password typed"


        if Password == MAGICASK: Password=''
        if UserName == MAGICASK: UserName=''

        ip_client=self.shared.get(u'ip_client')
        password_len=len(Password)
        UserName_len=len(UserName)

        RDPPROXY_CNX=self.ArtcaAuth.memcache_get("RDPPROXY_CNX")
        if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: RDPPROXY_CNX:%s" % RDPPROXY_CNX)
        if RDPPROXY_CNX is None: RDPPROXY_CNX=1
        RDPPROXY_CNX2=int(RDPPROXY_CNX)+1

        if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: RDPPROXY_CNX set to new %s" % RDPPROXY_CNX2)
        self.ArtcaAuth.memcache_set("RDPPROXY_CNX",str(RDPPROXY_CNX2),42500)

        if UserName_len < 2:
            syslog.syslog(syslog.LOG_INFO,"[INFO]: Anonymous connection from [%s] (Debug=%s)" % (ip_client, DEBUG))
            UserName=''
            Password=''

        self.ArtcaAuth.password=Password

        if UserName_len>2:
            syslog.syslog(syslog.LOG_INFO, "[INFO]: Connection from [%s] with login [%s] and a password of %s characters (Debug=%s)" % (ip_client,UserName,password_len,DEBUG))
            matches = re.search('^(.+?)\/(.+)$', UserName)

        if matches:
            if DEBUG: syslog.syslog(syslog.LOG_INFO, "[INFO]: %s OK matches regex pattern" % UserName)
            ComputerName = matches.group(2)
            UserName     = matches.group(1)
            if password_len == 0:
                syslog.syslog(syslog.LOG_INFO,"[ERROR]: Connection from [%s] Username [%s] No password entered" % (ip_client, UserName))
                l=  self.get_linenumber()+1
                self.send_error_user("Error %s, %s" % (l,RDP_ERROR_MSG1))
                return False


            self.ArtcaAuth.password=Password
            self.ArtcaAuth.username=UserName

            try:
                AuthorizeTSElogin = self.ArtcaAuth.auto_tse_login(ip_client,ComputerName)
            except:
                syslog.syslog(syslog.LOG_INFO, "[FATAL]: %s" % tb.format_exc())
                self.send_error_user("Error %s, %s" % self.get_linenumber(),RDP_ERROR_MSG2)
                return False

            if AuthorizeTSElogin == 0:
                self.send_error_user("Error %s, %s" % (self.get_linenumber(),RDP_ERROR_MSG3))
                return False

            target_host,target_device,target_port = self.ArtcaAuth.ResolveComputer(AuthorizeTSElogin)
            if target_host is None:
                syslog.syslog(syslog.LOG_INFO, "[FATAL]: Unable to resolve computer %s id=[%s]" % (ComputerName,AuthorizeTSElogin))
                self.send_error_user("Error %s, %s" % (self.get_linenumber(),RDP_ERROR_MSG4))
                return False

            syslog.syslog(syslog.LOG_INFO, "[INFO]: Connection transfered from %s@%s to %s (%s|%s:%s)" % (UserName,ip_client,ComputerName,target_host,target_device,target_port))
            try:
                kv=self.ArtcaAuth.BuildConnectionMatched(AuthorizeTSElogin,self.ArtcaAuth.userid,UserName,Password)
            except:
                syslog.syslog(syslog.LOG_INFO, "[ERROR]: %s",tb.format_exc())
                self.send_error_user("Error %s,%s" % (self.get_linenumber(),RDP_ERROR_MSG2))
                return False

            if len(kv) == 0:
                syslog.syslog(syslog.LOG_INFO,"[ERROR]: Permission denied: No rule for %s [%s]" % (UserName, target_host))
                self.send_error_user("Error %s, %s"  % (self.get_linenumber(),RDP_ERROR_MSG5))

        if UserName_len < 2: Password=''

        if(len(Password)==0):
            if self.AllowAuthenticateScreen == 0:
                syslog.syslog(syslog.LOG_INFO, "[ERROR]:  %s, %s [Password=Nil, Allow Authenticate Screen = 0]" % (self.get_linenumber(),RDP_ERROR_MSG6))
                self.send_error_user("Error %s, %s" % (self.get_linenumber(),RDP_ERROR_MSG6))
                return False

            if len(UserName) == 0:
                if self.AllowAuthenticateScreen == 0:
                    syslog.syslog(syslog.LOG_INFO, "[ERROR]:  %s, %s [UserName=Nil, Allow Authenticate Screen = 0]" % (
                    self.get_linenumber(), RDP_ERROR_MSG6))
                    self.send_error_user("Error %s, %s" % (self.get_linenumber(),RDP_ERROR_MSG6))
                    return False


            if len(UserName)>2:
                if not self.ArtcaAuth.UserExists(UserName,ip_client):
                    if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Closing connection because UserExists() return false")
                    syslog.syslog(syslog.LOG_INFO, "[ERROR]:  %s, %s [%s] [Member Not found in rules]" % (self.get_linenumber(), RDP_ERROR_MSG7,UserName))
                    self.send_error_user("Error %s, %s" % (self.get_linenumber(),RDP_ERROR_MSG7))
                    return False
            else:
                if len(UserName) == 0:UserName=MAGICASK



            interactive_data = {
                u'target_password': self.shared.get(u'password', MAGICASK),
                u'target_login': UserName,
                u'target_device': ip_client,
                u'target_host': ip_client
            }

            _status, _error = self.interactive_target(interactive_data)
            if (len(Password) == 0):
                Password = self.shared.get(u'target_password')
                self.ArtcaAuth.password = Password

            if UserName == "UNLIKELYVALUEMAGICASPICONSTANTS3141592926ISUSEDTONOTIFYTHEVALUEMUSTBEASKED":
                if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Remove MAGICASK From user")
                UserName = ""

            if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: User [%s]/[%s]/[%s] after interactive_target" % (UserName,self.shared.get(u'login'),self.shared.get(u'target_login')))

            if len(UserName) == 0:
                UserName = self.shared.get(u'login')
                if len(UserName) == 0: UserName=self.shared.get(u'target_login')
                if DEBUG: syslog.syslog(syslog.LOG_INFO,"[DEBUG]: LOGIN BOX: login=%s" % UserName)

                if not self.ArtcaAuth.UserExists(UserName,ip_client):
                    if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Closing connection because UserExists() return false")
                    self.send_error_user("Error %s, %s" % (self.get_linenumber(),RDP_ERROR_MSG7))
                    return False




            if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s New password given after interactive_target" % UserName)
            if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s Device after interactive_target [%s]" % (UserName,target_device))

        if (len(Password) == 0):
            if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s [%s] no password typed." % (UserName,target_device))
            self.send_error_user("Error %s, %s" % (self.get_linenumber(),RDP_ERROR_MSG8))
            return False


        try:
            zRules = self.ArtcaAuth.check_rules(UserName,Password,ip_client)
            if len(zRules) == 0:
                syslog.syslog(syslog.LOG_INFO, "[ERROR]: ERROR %s Permission denied: No rule for %s [%s]" % (self.get_linenumber(),UserName, target_host))
                self.send_error_user("Error %s, %s"  % (self.get_linenumber(),RDP_ERROR_MSG5))
        except:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: <<<<%s>>>>" % tb.format_exc())
            self.send_error_user("Error %s,%s" % (self.get_linenumber(),RDP_ERROR_MSG2))
            return False



        selector_data = self.ArtcaAuth.BuildSelector(zRules)
        if self.ArtcaAuth.rules_number==0:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: %s [%s] No rule to build selector" % (UserName, target_host))
            self.send_error_user("Error %s, %s" % (self.get_linenumber(), RDP_ERROR_MSG5))
            return False

        self.selector_target(selector_data)


        while True:
            selected_device = self.shared.get(u'target_login')
            selector = self.shared.get(u'selector')
            selected_module  = self.shared.get(u'module')
            reporting        = self.shared.get(u'reporting')
            auth_channel_target = self.shared.get(u'auth_channel_target')
            display_message     = self.shared.get(u'display_message')
            accept_message = self.shared.get(u'accept_message')
            real_target_device = self.shared.get(u'real_target_device')
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: ************************************************")
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: display_message......[%s]" % display_message)
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: accept_message.......[%s]" % accept_message)
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: reporting............[%s]" % reporting)
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: auth_channel_target..[%s]" % auth_channel_target)
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: selector.............[%s]" % selector)
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: module...............[%s]" % selected_module)
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: selected_device......[%s]" % selected_device)
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: real_target_device...[%s]" % real_target_device)

            if selected_device==MAGICASK:
                if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]:  %s [%s] selected_device==MAGICASK Resend again the list of computers" % (UserName, target_host))
                self.selector_target(selector_data)
                continue

            if self.ArtcaAuth.UnderstandSelector(selected_device,selector,MAGICASK):

                if len(real_target_device) == 0:
                    if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: %s [%s] real_target_device == Nil, Connection closed" % (UserName, target_host))
                    self.shared.send_data({u'module': u'close'})
                    return False


                ruleid      = self.ArtcaAuth.unserstand_ruleid
                targetid    = self.ArtcaAuth.unserstand_targetid
                isAD        = self.ArtcaAuth.unserstand_isAD
                if len(self.ArtcaAuth.unserstand_computername) >0: ComputerName = self.ArtcaAuth.unserstand_computername
                break
            if DEBUG:  syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Resend again the list of computers")
            self.selector_target(selector_data)

        if DEBUG:  syslog.syslog(syslog.LOG_INFO,"[DEBUG]: USER SELECT %s Selected <%s> rule.%s target.%s" % (UserName,selected_device, ruleid, targetid))
        kv=self.ArtcaAuth.BuildConnection(ruleid,targetid,self.ArtcaAuth.userid,ComputerName,isAD)
        self.shared.send_data(kv)

        try_next = False
        signal.signal(signal.SIGUSR1, self.kill_handler)
        try:
            self.shared.send_data(kv)

            # Looping on keepalived socket
            while True:
                r = []
                syslog.syslog(syslog.LOG_INFO, "[INFO]: %s [%s] Waiting on proxy" % (UserName,target_host))
                got_signal = False
                try:
                    r, w, x = select([self.proxy_conx], [], [], 60)
                except Exception as e:
                    if DEBUG:
                        syslog.syslog(syslog.LOG_INFO, "[ERROR]: exception: '%s'" % e)
                        syslog.syslog(syslog.LOG_INFO, "[ERROR]: <<<<%s>>>>" % traceback.format_exc(e))
                    if e[0] != 4:
                        raise
                    syslog.syslog(syslog.LOG_INFO, "[INFO]: Got Signal %s" % e)
                    got_signal = True
                if self.proxy_conx in r:
                    _status, _error = self.shared.receive_data()
                    # On attrappe les sessions ici
                    try:
                        command=self.ArtcaAuth.session_check(self.shared)
                    except:
                        syslog.syslog(syslog.LOG_INFO, "[ERROR]: * * * * * ArtcaAuth.session_check Fatal error * * * * *")
                        syslog.syslog(syslog.LOG_INFO, tb.format_exc())

                    if command==999:
                        self.proxy_conx.close()
                        break


                    if self.shared.is_asked(u'keepalive'):
                        self.shared.send_data({u'keepalive': u'True'})
                # r can be empty
                else: # (if self.proxy_conx in r)
                    syslog.syslog(syslog.LOG_INFO, "[ERROR]: ID 339 Missing Keepalive [break connection]")
                    release_reason = u'Break connection'
                    break
            syslog.syslog(syslog.LOG_INFO, "[INFO]: End Of Keep Alive")


        except AuthentifierSocketClosed as e:
            if DEBUG:
                import traceback
                syslog.syslog(syslog.LOG_INFO, "[INFO]: 383 Connection terminated by client")
                syslog.syslog(syslog.LOG_INFO, "[DEBUG]: <<<<%s>>>>" % traceback.format_exc(e))
        except Exception as e:
            if DEBUG: syslog.syslog(syslog.LOG_INFO, "[INFO]: Connection terminated by client")

        try:
            syslog.syslog(syslog.LOG_INFO, "[INFO]: Close connection ...")
            self.proxy_conx.close()
            syslog.syslog(syslog.LOG_INFO, "[INFO]: Close connection done.")
        except IOError:
            if DEBUG:
                syslog.syslog(syslog.LOG_INFO, "[DEBUG]: Close connection: Exception")
                syslog.syslog(syslog.LOG_INFO, "[DEBUG]: 398 <<<<%s>>>>" % traceback.format_exc(e))
    # END METHOD - START


    def kill_handler(self, signum, frame):
        # Logger().info("KILL_HANDLER = %s" % signum)
        if signum == signal.SIGUSR1:
            self.kill()

    def kill(self):
        try:
            syslog.syslog(syslog.LOG_INFO, "[INFO]: Closing a connection")
            self.proxy_conx.close()
        except Exception as e:
            if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]:<<<<%s>>>>" %  traceback.format_exc(e))
            pass



from socket import fromfd
from socket import AF_UNIX
from socket import AF_INET
from socket import SOCK_STREAM
from socket import SOL_SOCKET
from socket import SO_REUSEADDR
from select import select
from logger import Logger

socket_path = '/var/run/rdpproxy/auth.sock'
localport   = 3450
version     = "2.12"

def writePidFile():
    pid = str(os.getpid())
    f = open('/var/run/rdpproxy/auth.pid', 'w')
    f.write(pid)
    f.close()

def standalone():
    syslog.openlog("rdpproxy-auth", syslog.LOG_PID)
    syslog.syslog(syslog.LOG_INFO, "[INFO]: RDS Proxy authenticator v%s socket %s and local port 127.0.0.1:%s" % (version,socket_path,str(localport)))
    SET_INFO("RDPPROXY_AUTH_VERSION",version)
    signal.signal(signal.SIGCHLD, signal.SIG_IGN)
    if os.path.exists(socket_path): os.unlink(socket_path)
    # create socket from bounded port
    s1 = socket.socket(AF_UNIX, SOCK_STREAM)
    s1.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
    s1.bind(socket_path)
    s1.listen(100)

    s2 = socket.socket(AF_INET, SOCK_STREAM)
    s2.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
    s2.bind(('127.0.0.1', 3450))
    s2.listen(100)
    writePidFile()
    ArtcaAuth = rdsrdp(DEBUG)
    ArtcaAuth.database_maitenance()
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

    except socket.error as e:
        syslog.syslog(syslog.LOG_INFO, "[INFO]: RDS Proxy Authenticator Socket [Terminated]")
        if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: L.535 [%s]"% tb.format_exc())
        pass
    except AuthentifierSocketClosed as e:
        if DEBUG: syslog.syslog(syslog.LOG_INFO, "[DEBUG]: RDS Proxy Authenticator socket Closed")
    except Exception as e:
        syslog.syslog(syslog.LOG_INFO, "[WARN]: RDS Proxy Authenticator socket exception [Terminated]" )
        if DEBUG: syslog.syslog(syslog.LOG_INFO, "[ERROR]: %s "% tb.format_exc())
        sys.exit(1)

if __name__ == '__main__':
    standalone()
