#!/usr/bin/env python
import ldap
import traceback as tb
import ldap
import re
from unix import *
import ldap.filter
class LOCALLDAP:

    def __init__(self, logging):
        self.ldap_server = "127.0.0.1"
        self.NoSQL = False
        self.username = file_get_contents("/etc/artica-postfix/ldap_settings/admin")
        self.password = file_get_contents("/etc/artica-postfix/ldap_settings/password")
        self.suffix = file_get_contents("/etc/artica-postfix/ldap_settings/suffix")
        self.logging = logging
        self.ldap = object
        self.AuthentFailed = False
        self.ldap_down = False
        self.ldap_error = ""
        self.PrintScreen = False
        self.sAMAccountName = ""
        pass

    def Connect(self):

        try:
            self.ldap = ldap.initialize("ldap://" + self.ldap_server + ":389")
        except:
            if self.PrintScreen: print(tb.format_exc())
            self.logging.info(tb.format_exc())
            return False

        try:
            self.ldap.set_option(ldap.OPT_TIMEOUT, 3)
            self.ldap.protocol_version = ldap.VERSION3
            self.ldap.set_option(ldap.OPT_REFERRALS, 0)

        except:
            self.logging.info(tb.format_exc())
            return False

        return True;
        pass

    def makeConnect(self):
        if not self.Connect():
            if self.PrintScreen: print("Connection Failed....")
            self.Disconnect()
            return False
        try:
            self.ldap.simple_bind_s("cn=" + self.username + "," + self.suffix, self.password)
        except ldap.INVALID_CREDENTIALS:
            self.AuthentFailed = True
            self.Disconnect()
            if self.PrintScreen: print(tb.format_exc())
            if self.PrintScreen: print("makeConnect:: Authentication Failed - INVALID_CREDENTIALS")
            self.ldap_error = "Authentication Failed"
            return False
        except ldap.SERVER_DOWN:
            self.ldap_down = True
            if self.PrintScreen: print("makeConnect:: SERVER_DOWN")
            self.ldap_error = "LDAP server down"
            self.Disconnect()
            return False

        except ldap.INVALID_DN_SYNTAX:
            self.ldap_down = True
            if self.PrintScreen: print("makeConnect:: INVALID_DN_SYNTAX 'cn=" + self.username + "," + self.suffix + "'")
            self.ldap_error = "LDAP INVALID_DN_SYNTAX"
            self.Disconnect()
            return False
        return True

    def GetRootDse(self):
        return file_get_contents("/etc/artica-postfix/ldap_settings/suffix")

    pass

    def GetUserPassword(self, username):
        RootDSE = self.GetRootDse()
        if not self.makeConnect(): return None
        criteria = "(&(objectClass=*)(uid=" + username + "))";
        attributes = ['gidNumber', 'userPassword']

        try:
            if self.PrintScreen: print("Search:", criteria)
            if self.PrintScreen: print("RootDSE:", RootDSE)
            result_data = self.ldap.search_s(RootDSE, ldap.SCOPE_SUBTREE, criteria, attributes)
            if result_data == None:
                if self.PrintScreen: print("GetUserDN:: Search Failed (NONE)")
                self.ldap_error = "Search User DN Failed (NONE)"
                return None

            if len(result_data) == 0:
                if self.PrintScreen: print("GetUserDN:: Search Failed (count=0)")
                self.ldap_error = "Search User DN Failed (count=0)"
                return None
            try:
                if "userPassword" in result_data[0][1]:
                    return result_data[0][1]["userPassword"][0]
            except Exception, error:
                if self.PrintScreen: print("GetUserDN:: Search Failed")
                if self.PrintScreen: print(tb.format_exc())
                self.ldap_error = "Search User Failed"
                self.logging.info(tb.format_exc())
                self.Disconnect()
                return None
        except Exception, error:
            if self.PrintScreen: print("GetUserDN:: Search user Failed")
            if self.PrintScreen: print(tb.format_exc())
            self.ldap_error = "Search User DN Failed"
            self.logging.info(tb.format_exc())
            self.Disconnect()
            return None

    def GetUserGroups(self, username):
        RootDSE = self.GetRootDse()
        GROUPS = {}

        criteria = ldap.filter.filter_format('(&(objectClass=posixGroup)(memberUid=%s))', [username, ])
        attributes = ['cn']

        try:
            if self.PrintScreen: print("Search:", criteria)
            if self.PrintScreen: print("RootDSE:", RootDSE)
            result_data = self.ldap.search_s(RootDSE, ldap.SCOPE_SUBTREE, criteria, attributes)

            for dn, entry in result_data:
                if "cn" in entry:
                    if self.PrintScreen: print
                    "FOUND", entry["cn"][0]
                    GroupName = entry["cn"][0]
                    GroupName = GroupName.decode('utf-8').lower()
                    GROUPS[GroupName] = True


        except Exception, error:
            if self.PrintScreen: print("GetUserGroups:: Search Group Failed")
            if self.PrintScreen: print(tb.format_exc())
            self.ldap_error = "Search Group Failed"
            self.logging.info(tb.format_exc())
            self.Disconnect()
            return None

        self.Disconnect()
        return GROUPS

    pass

    def Disconnect(self):
        try:
            self.ldap.unbind()
        except:
            self.logging.info(tb.format_exc())
        pass

    def TestBind(self):
        if not self.Connect():
            self.Disconnect()
            return False
        try:
            self.ldap.simple_bind_s(self.username, self.password)
        except ldap.INVALID_CREDENTIALS:
            self.AuthentFailed = True
            self.Disconnect()
            self.ldap_error = "Authentication Failed"
            return False
        except ldap.SERVER_DOWN:
            self.ldap_down = True
            self.ldap_error = "LDAP server down"
            self.Disconnect()
            return False

        self.Disconnect()
        return True
        pass
