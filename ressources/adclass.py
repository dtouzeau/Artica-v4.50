#!/usr/bin/env python
from unix import *
import os.path
import datetime
import urllib
import ldap
import traceback as tb
import socket
import syslog
from pprint import pprint
from phpserialize import serialize, unserialize


class ADLDAP:
    def __init__(self, logging):
        self.logging = None
        self.ldap_connection = None
        self.LDAP_SERVER = ''
        self.ldap_suffix = ''
        self.LDAP_DN = ''
        self.LDAP_PASSWORD = ''
        self.userdn = ''
        self.LogTosyslog=False
        self.DEBUG=False
        self.ActiveDirectoryConnections = unserialize(GET_INFO_STR("ActiveDirectoryConnections"))
        if logging=="rdpproxy-auth":
            self.LogTosyslog =True
            syslog.openlog("rdpproxy-auth", syslog.LOG_PID)
        else:
            self.logging=logging


    pass


    def debug_log(self,text):
        if self.LogTosyslog:
            if not self.DEBUG: return True
            syslog.syslog(syslog.LOG_INFO, "[DEBUG]: ADLDAP %s"  % text)
            return True

        self.logging.debug(text)
        return True

    def error_log(self, text):
        if self.LogTosyslog:
            syslog.syslog(syslog.LOG_INFO, "[ERROR]: ADLDAP %s"  % text)
            return True

        self.logging.debug(text)
        return True

    def ActiveDirectoryConnectionsUtf8(self,IndexConfig):
        CONF_ORG = self.ActiveDirectoryConnections[IndexConfig]
        CONF = {}
        for zKey in CONF_ORG:
            newkey = zKey.decode('utf-8')
            newval = CONF_ORG[zKey].decode('utf-8')
            CONF[newkey]=newval
        return CONF

    def Connect(self, IndexConfig):
        CONF = self.ActiveDirectoryConnectionsUtf8(IndexConfig)
        if self.DEBUG: self.debug_log("AD::Connect index[%s] " % IndexConfig)

        if not "LDAP_SUFFIX" in CONF:
            self.debug_log("AD::Connect connect to: no LDAP suffix defined in connection index[%s]" % IndexConfig)
            return False

        if not "LDAP_PORT" in CONF: CONF["LDAP_PORT"]=389
        if not "LDAP_SERVER" in CONF: CONF["LDAP_SERVER"] = ""
        if not "ADNETIPADDR" in CONF: CONF["ADNETIPADDR"]= ""
        LDAP_PORT_TEMP = CONF["LDAP_PORT"]
        LDAP_SERVER = CONF["LDAP_SERVER"]
        LDAP_SUFFIX = CONF["LDAP_SUFFIX"]
        LDAP_SSL = CONF["LDAP_SSL"]
        ADNETIPADDR = CONF["ADNETIPADDR"]
        if len(ADNETIPADDR)>4: LDAP_SERVER = ADNETIPADDR
        LDAP_PORT = strtoint(LDAP_PORT_TEMP)

        if len(LDAP_SERVER)==0:
            self.debug_log("AD::Connect connect to: no LDAP server defined in connection index[%s]" % IndexConfig)
            return False



        prefix = "ldap"
        if LDAP_PORT == 636: LDAP_SSL = 1
        if LDAP_SSL == 1:
            prefix = "ldaps"
            if LDAP_PORT == 389: LDAP_PORT = 636

        self.debug_log("AD::Connect connect to: %s:%s (%s) [ssl=%s]" % (LDAP_SERVER, LDAP_PORT_TEMP, LDAP_PORT,LDAP_SSL))

        ldapstring = '%s://%s:%s' % (prefix,LDAP_SERVER,LDAP_PORT)
        self.debug_log("AD::Connect(): connect to: [%s]" % ldapstring)
        try:
            self.ldap_connection = ldap.initialize(ldapstring)
            self.ldap_connection.set_option(ldap.OPT_REFERRALS, 0)
            self.ldap_connection.set_option(ldap.OPT_PROTOCOL_VERSION, 3)
            if LDAP_SSL == 1:
                self.debug_log("AD::Connect(): Enable SSL")
                self.ldap_connection.set_option(ldap.OPT_X_TLS, ldap.OPT_X_TLS_DEMAND)
                self.ldap_connection.set_option(ldap.OPT_X_TLS_DEMAND, True)
            self.ldap_connection.set_option(ldap.OPT_DEBUG_LEVEL, 255)
            if LDAP_SSL == 1: self.ldap_connection.set_option(ldap.OPT_X_TLS_NEWCTX, 0)
        except ldap.LDAPError as e:
            self.error_log('AD::Connect(): LDAP Error: %s : Type %s %s://%s' % (str(e), type(e), prefix,LDAP_SERVER))
            return False
        self.LDAP_SERVER = LDAP_SERVER
        self.ldap_suffix = LDAP_SUFFIX
        self.LDAP_DN = CONF["LDAP_DN"]
        self.LDAP_PASSWORD = CONF["LDAP_PASSWORD"]
        return True

    pass

    def SimpleConnect(self, index):
        if not self.Connect(index): return False
        try:
            self.debug_log('simple_bind_s as username:' + self.LDAP_DN + ' Password:' + self.LDAP_PASSWORD)
            self.ldap_connection.simple_bind_s(self.LDAP_DN, self.LDAP_PASSWORD)
        except ldap.LDAPError as e:
            self.error_log('SimpleConnect(): simple_bind_s error ' + e[0]['desc'] + ' ' + e[0][
                'info'] + ' ' + prefix + '://' + LDAP_SERVER + ' wrong credentials provided: ' + self.LDAP_DN)
            return False

        self.debug_log('SimpleConnect(): simple_bind_s SUCCESS');
        return True

    def TestAuthenticate(self, IndexConfig, username, password):
        if not self.Connect(IndexConfig): return False
        try:
            self.ldap_connection.simple_bind_s(username, password)

        except ldap.SERVER_DOWN:
            self.error_log("simple_bind_s: [%s] Server is down" % username)
            return False

        except ldap.INVALID_CREDENTIALS:
            self.error_log("simple_bind_s: [%s] (%s) Invalid credentials" % (username,password))
            return False


        except ldap.LDAPError as e:
            self.error_log('simple_bind_s error %s %s %s' %( e[0]['desc'] ,e[0]['info'] , username ))
            return False

        Dn = self.get_user_dn(username, 0)
        self.debug_log('simple_bind_s DN = %s '% Dn)
        return True

    pass

    def ConnectionIndexFromDN(self, DN):
        DNLower = DN.lower()
        try:
            for index in self.ActiveDirectoryConnections:
                CONF = self.ActiveDirectoryConnectionsUtf8(index)

                Suffix = CONF["LDAP_SUFFIX"]
                SuffixLower = Suffix.lower()
                self.debug_log("ConnectionIndexFromDN(): Checking [" + Suffix + "] Against [" + DN + "]")
                if SuffixLower in DNLower:
                    self.debug_log(
                        "ConnectionIndexFromDN(): Checking [" + Suffix + "] OK Index[" + str(index) + "]")
                    return index


        except:
            self.error_log("ConnectionIndexFromDN Fatal error while parsing ActiveDirectoryConnections [" + tb.format_exc() + "]")
            return None

    pass

    def ListComputerFromGroupDN(self, DN):
        Computers = []
        Index = self.ConnectionIndexFromDN(DN)
        if Index is None: return Computers
        Members = self.ListMembersFromGroupDN(Index, DN)

        if Members is None:
            self.debug_log("ListComputerFromGroupDN(%s): %s [None] Return no element" % (Index, DN ))
            return Computers

        for subdn in Members:
            subdn=subdn.decode("utf-8")
            self.debug_log("ListComputerFromGroupDN(1): %s" % subdn)
            text = self.get_computer_info(subdn)
            if text is None: continue
            self.debug_log("ListComputerFromGroupDN(2): %s" % text)
            Computers.append(text)

        return Computers

    pass

    def UserExistsInAll(self, username):

        for index in self.ActiveDirectoryConnections:
            self.debug_log("UserExistsInAll(): " + username + "  trying connection id [" + str(index) + "]")
            if not self.SimpleConnect(index): continue
            userdn = self.get_user_dn(username, index)
            self.debug_log("UserExistsInAll(): " + username + " [" + userdn + "] Index " + str(index))
            if len(userdn) > len(username):
                self.userdn = userdn
                return True

        return False

    pass

    def ListMembersFromGroupDN(self, index, DN):
        if not self.SimpleConnect(index): return False
        user_scope = ldap.SCOPE_SUBTREE
        user_filter = "(objectClass=*)";
        status_attribs = ['memberOf', 'member', 'displayName']

        try:
            # search for user
            self.debug_log("ListMembersFromGroupDN(): find all in " + DN)
            results = self.ldap_connection.search_s(DN, user_scope, user_filter, status_attribs)
        except ldap.LDAPError as e:
            self.error_log('ListMembersFromGroupDN(): Search error ' + e[0]['desc'] + ' ' + e[0][
                'info'] + ' ' + user_filter + ' in ' + user_base)
            return None

        result = results[0]
        try:
            members_tmp = result[1]['member']
            return members_tmp
        except:
            self.error_log('ListMembersFromGroupDN(): Fatal while forwarding array')
            return None

    pass

    def dngroupMatchesDN(self, index, DnGroup, DnUser):
        if not self.SimpleConnect(index):
            self.error_log("dngroupMatchesDN SimpleConnect(%s) return False" % index)
            return False
        user_scope = ldap.SCOPE_SUBTREE
        user_filter = "(objectClass=*)";
        status_attribs = ['pwdLastSet', 'accountExpires', 'userAccountControl', 'memberOf', 'member',
                          'msDS-User-Account-Control-Computed', 'msDS-UserPasswordExpiryTimeComputed',
                          'msDS-ResultantPSO', 'lockoutTime', 'sAMAccountName', 'displayName']

        try:
            # search for user
            self.debug_log("dngroupMatchesDN(): find memberOf,member attributes in " + DnGroup)
            results = self.ldap_connection.search_s(DnGroup, user_scope, user_filter, status_attribs)
        except ldap.LDAPError as e:
            self.error_log('dngroupMatchesDN(): Search error ' + e[0]['desc'] + ' ' + e[0][
                'info'] + ' ' + user_filter + ' in ' + user_base)
            return False

        try:
            result = results[0]
            members_tmp = result[1]['member']
        except:
            self.error_log("dngroupMatchesDN(): Invalid array affectation for result(s)")
            return False

        if DnUser in members_tmp:
            self.debug_log("dngroupMatchesDN(): [OK] Success found %s" % DnUser)
            return True

        for m in members_tmp:
            m=m.decode('utf-8')
            self.debug_log("dngroupMatchesDN(): list %s" % m)
            if m.lower() == DnUser.lower():
                self.debug_log("dngroupMatchesDN(): [OK] found member: find %s" % m)
                return True
        self.debug_log("dngroupMatchesDN(): " + DnUser + " Not found in " + DnGroup)
        return False

    pass

    def get_computer_info(self, ComputerDN):
        user_base = ComputerDN
        user_filter = "(objectClass=*)"
        user_scope = ldap.SCOPE_SUBTREE
        status_attribs = ['dNSHostName', 'name', 'operatingSystem', 'operatingSystemServicePack',
                          'operatingSystemVersion', 'description']
        try:
            # search for user
            self.debug_log("adldap::get_computer_info find %s in %s" %(user_filter , user_base))
            results = self.ldap_connection.search_s(user_base, user_scope, user_filter, status_attribs)
        except ldap.LDAPError as e:
            self.error_log('Search error %s %s %s in %s' %( e[0]['desc'] , e[0]['info'] , user_filter , user_base))
            return ''
        result = results[0]
        user_attribs = result[1]
        dNSHostName = ''
        name = ''
        operatingSystem = ''
        operatingSystemServicePack = ''
        operatingSystemVersion = ''
        description = ''
        ComputerName = ''
        self.debug_log("adldap::get_computer_info %s " % repr(user_attribs))

        try:
            dNSHostName = user_attribs["dNSHostName"][0].decode("utf-8")
        except:
            self.debug_log("get_computer_info(): dNSHostName Not found [" + tb.format_exc() + "]")
        try:
            name = user_attribs["name"][0]
        except:
            self.debug_log("adldap::get_computer_info name Not found")
        try:
            operatingSystem = user_attribs["operatingSystem"][0].decode("utf-8")
        except:
            self.debug_log("adldap::get_computer_info(): operatingSystem Not found")
        try:
            operatingSystemServicePack = user_attribs["operatingSystemServicePack"][0].decode("utf-8")
        except:
            self.debug_log("adldap::get_computer_info(): operatingSystemServicePack Not found")
        try:
            operatingSystemVersion = user_attribs["operatingSystemVersion"][0].decode("utf-8")
        except:
            self.debug_log("adldap::get_computer_info(): operatingSystemVersion Not found")
        try:
            description = user_attribs["description"][0].decode("utf-8")
        except:
            self.debug_log("adldap::get_computer_info()  description Not found")

        if len(dNSHostName) > 0: ComputerName = dNSHostName
        if len(ComputerName) == 0:
            if len(name) > 0: ComputerName = name

        if len(ComputerName) == 0: return None

        subdescription = operatingSystem + " " + operatingSystemServicePack + " " + operatingSystemVersion
        if len(description) == 0: description = subdescription
        return ComputerName + "@" + description

    def get_user_dn(self, user, index):
        user_dn = None
        user_base = self.ldap_suffix
        user_filter = "(sAMAccountName=%s)" % (user)
        matches = re.match('^(.*?)@.+', user)
        if matches:
            simpleuser = matches.group(1)
            user_filter = "(sAMAccountName=%s)" % (simpleuser)

        user_scope = ldap.SCOPE_SUBTREE
        status_attribs = ['pwdLastSet', 'accountExpires', 'userAccountControl', 'memberOf',
                          'msDS-User-Account-Control-Computed', 'msDS-UserPasswordExpiryTimeComputed',
                          'msDS-ResultantPSO', 'lockoutTime', 'sAMAccountName', 'displayName']

        try:
            # search for user
            self.debug_log("find %s in %s" %( user_filter , user_base))
            results = self.ldap_connection.search_s(user_base, user_scope, user_filter, status_attribs)
        except ldap.LDAPError as e:
            self.error_log("Search error %s %s %s in %s" % (e[0]['desc'] , e[0]['info'] ,  user_filter ,user_base))
            return ''

        self.debug_log('get_user_dn():: Search is a success..')

        try:
            self.debug_log('get_user_dn():: results[0][0]=' + str(results[0][0]))
            user_dn = results[0][0]
        except:
            self.error_log('get_user_dn():: Unable to get UserDN with FATAL ERROR')

        if user_dn is None:
            self.debug_log("get_user_dn():: user_dn is None in for user:" + user)
            return ''

        self.debug_log("find [DN: %s] in %s" % (user_dn,user_base))
        return user_dn
        # user_attribs = result[1]
        # uac = int(user_attribs['userAccountControl'][0])
        # uac_live = int(user_attribs['msDS-User-Account-Control-Computed'][0])
        # DisplayName = user_attribs['displayName'][0]

    pass