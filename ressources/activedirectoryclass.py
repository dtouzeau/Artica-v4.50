#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import ldap
import traceback as tb
import re
from unix import *
from phpserialize import serialize, unserialize
from inspect import currentframe, getframeinfo

class ActiveDirectory:

    def __init__(self, logging=None):
        self.ldap_server = ""
        self.ldap_ssl=0
        self.NoSQL = False
        self.username = ""
        self.password = ""
        self.logging = logging
        self.ldap = object
        self.AuthentFailed = False
        self.ldap_down = False
        self.ldap_error = ""
        self.PrintScreen = False
        self.sAMAccountName = ""
        self.remote_port = 389
        self.ldap_suffix=""
        self.INTERNAL_LOGS = []
        self.ActiveDirectoryConnections = unserialize(GET_INFO_STR("ActiveDirectoryConnections"))
        pass

    def ilog(self,line,func,text):
        if func is None:
            func=""
        else:
            func=" %s" % func
        text="--------------: ActiveDirectory:%s%s %s" % (line,func,text)
        if self.PrintScreen: print(text)
        self.INTERNAL_LOGS.append(str(text))
        if self.logging is not None: self.logging.info(text)

    def LoadConnection(self,connection_id):
        self.ldap_ssl = 0
        self.ldap_server=""
        self.username =""
        self.ldap_suffix=""

        if len(self.ActiveDirectoryConnections)==0:
            self.ilog(self.get_linenumber(), self.getfunc(), "ActiveDirectoryConnections Len=0")
            return False
        try:
            config = self.ActiveDirectoryConnections[connection_id]
        except:
            return False

        try:
            self.ldap_suffix = config["LDAP_SUFFIX"].lower()
            self.password=config["LDAP_PASSWORD"]
            self.ldap_server=config["LDAP_SERVER"]
            self.remote_port=int(config["LDAP_PORT"])
            self.username=config["LDAP_DN"]
            if "LDAP_SSL" in config: self.ldap_ssl=config["LDAP_SSL"]
            if self.remote_port==636: self.ldap_ssl=1
        except:
            error = tb.format_exc()
            self.ilog(self.get_linenumber(), self.getfunc(), "%s" % error)
            return False

        return True




    def Connect(self):
        cnxstring="ldap://%s:%s" % (self.ldap_server,self.remote_port)
        if self.ldap_ssl == 1:cnxstring="ldaps://%s:%s" % (self.ldap_server,self.remote_port)

        try:
            self.ldap = ldap.initialize(cnxstring)
        except:
            if self.PrintScreen: print(tb.format_exc())
            self.logging.info(tb.format_exc())
            return False

        try:
            self.ldap.set_option(ldap.OPT_TIMEOUT, 3)
            self.ldap.protocol_version = ldap.VERSION3
            self.ldap.set_option(ldap.OPT_REFERRALS, 0)
            if self.ldap_ssl==1:
                self.ldap.set_option(ldap.OPT_X_TLS_REQUIRE_CERT, ldap.OPT_X_TLS_NEVER)
                self.ldap.set_option(ldap.OPT_X_TLS, ldap.OPT_X_TLS_DEMAND)
                self.ldap.set_option(ldap.OPT_X_TLS_DEMAND, True)
                # l.set_option(ldap.OPT_DEBUG_LEVEL, 255)

        except Exception as e:
            if self.PrintScreen: print(e)
            error = tb.format_exc()
            self.ilog(self.get_linenumber(), self.getfunc(), "%s" % error)
            return False

        self.ilog(self.get_linenumber(), self.getfunc(), "%s Connection succeed" % cnxstring)
        return True
        pass

    def getfunc(self):
        caller = currentframe().f_back
        func_name = getframeinfo(caller)[2]
        caller = caller.f_back
        from pprint import pprint
        func = caller.f_locals.get(func_name, caller.f_globals.get(func_name))
        return func
    def get_linenumber(self):
        cf = currentframe()
        return cf.f_back.f_lineno

    def GetConnectioIDFromDn(self,dn):
        if len(dn)==0: return None
        dn=dn.lower()
        if len(self.ActiveDirectoryConnections)==0: return None
        for index in self.ActiveDirectoryConnections:
            config=self.ActiveDirectoryConnections[index]
            try:
               LDAP_SUFFIX=config["LDAP_SUFFIX"].lower()
               self.ilog(self.get_linenumber(),self.getfunc(),"%s] LDAP_SUFFIX: %s" % (index,config["LDAP_SUFFIX"]))
            except:
                error=tb.format_exc()
                self.ilog(self.get_linenumber(), self.getfunc(), "%s" % error)
                continue

            if dn.find(LDAP_SUFFIX)>-1:
                self.ilog(self.get_linenumber(), self.getfunc(), "[OK] DN %s as connection ID %s "% (dn,index))
                return index

        return None





    def makeConnect(self):
        if not self.Connect():
            if self.PrintScreen: print("Connection Failed....")
            self.Disconnect()
            return False
        try:
            self.ldap.simple_bind_s(self.username, self.password)
        except ldap.INVALID_CREDENTIALS:
            self.AuthentFailed = True
            self.Disconnect()
            if self.PrintScreen: print(tb.format_exc())
            if self.PrintScreen: print("makeConnect:: Authentication Failed - INVALID_CREDENTIALS")
            self.ldap_error = "Authentication Failed"
            return False
        except ldap.SERVER_DOWN:
            self.ldap_down = True
            if self.PrintScreen: print("makeConnect:: Active Directory down - SERVER_DOWN")
            self.ldap_error = "Active Directory down"
            self.Disconnect()
            return False
        return True

    def GetRootDse(self):
        if not self.makeConnect():
            if self.PrintScreen: print("GetRootDse:: Connection Failed....")
            return None
        baseDN = ""
        if self.PrintScreen: print("GetRootDse:: make query...")
        searchScope = ldap.SCOPE_BASE
        retrieveAttributes = None
        searchFilter = "objectclass=*"
        try:
            result_data = self.ldap.search_s(baseDN, searchScope, searchFilter, retrieveAttributes)
            for dn, entry in result_data:
                if "namingContexts" in entry:
                    self.Disconnect()
                    return entry["namingContexts"][0]


        except ldap.LDAPError as e:
            self.logging.info(tb.format_exc())

        self.Disconnect()
        return None

    pass

    def GetUserDN(self, username):
        RootDSE = self.GetRootDse()
        criteria=""
        attributes=[]
        if RootDSE == None:
            if self.PrintScreen: print("GetUserGroups:: Search Root DSE Failed")
            self.ldap_error = "Search Root DSE Failed"
            return None

        if not self.makeConnect(): return None
        # userPrincipalName
        if username.find('@') > 1:
            criteria = "(&(objectClass=user)(userPrincipalName=" + username + "))";
            attributes = ['distinguishedName', 'sAMAccountName']

        if username.find('@') < 1:
            criteria = "(&(objectClass=user)(sAMAccountName=" + username + "))";
            attributes = ['distinguishedName', 'sAMAccountName']

        try:
            if self.PrintScreen: print("Search:", criteria)
            if self.PrintScreen: print("RootDSE:", RootDSE)
            result_data = self.ldap.search_s(RootDSE, ldap.SCOPE_SUBTREE, criteria, attributes)
            try:
                self.sAMAccountName = result_data[0][1]["sAMAccountName"][0]
                return result_data[0][0]
            except Exception as error:
                if self.PrintScreen: print("GetUserDN:: Search Group Failed")
                if self.PrintScreen: print(tb.format_exc())
                self.ldap_error = "Search User DN Failed"
                self.logging.info(tb.format_exc())
                self.Disconnect()
                return None
        except Exception as error:
            if self.PrintScreen: print("GetUserDN:: Search Group Failed")
            if self.PrintScreen: print(tb.format_exc())
            self.ldap_error = "Search User DN Failed"
            self.logging.info(tb.format_exc())
            self.Disconnect()
            return None

    def Close(self):
        self.INTERNAL_LOGS=[]
        try:
            self.ldap.unbind_s()
        except:
            self.ilog(self.get_linenumber(), self.getfunc(), tb.format_exc())

    def GetUsersFromDNGroup(self,dngroup,connection_id=None):
        results=[]
        if connection_id==None: return results
        self.ilog(self.get_linenumber(), self.getfunc(),"[DEBUG]: GetUsersFromDNGroup: connection_id:%s" % connection_id)
        if not self.LoadConnection(connection_id):return results

        searchFilter = "(objectClass=*)"
        attributes = ['member', 'memberOf']
        if not self.makeConnect(): return results
        try:
            result_data = self.ldap.search_s(dngroup, ldap.SCOPE_SUBTREE, searchFilter, attributes)
        except ldap.OPERATIONS_ERROR:
            self.ldap_error = "Operations error"
            self.ilog(self.get_linenumber(), self.getfunc(),tb.format_exc())
            return results

        for dn, entry in result_data:
            self.ilog(self.get_linenumber(), self.getfunc(), "Found DN=%s"% dn)
            if "member" in entry:
                for record in entry["member"]:
                    Username=self.get_sAMAccountName(record)
                    if len(Username)==0: continue;
                    Username=Username.lower()
                    results.append(Username)

        return results


    def get_sAMAccountName(self,userdn):
        searchFilter = "(objectClass=*)"
        attributes = ['sAMAccountName']
        try:
            result_data = self.ldap.search_s(userdn, ldap.SCOPE_SUBTREE, searchFilter, attributes)
        except ldap.OPERATIONS_ERROR:
            self.ldap_error = "Operations error"
            self.ilog(self.get_linenumber(), self.getfunc(),tb.format_exc())
            return ""

        try:
            return result_data[0][1]["sAMAccountName"][0]
        except:
            return ""




    def GetUserGroups(self, username):
        RootDSE = self.GetRootDse()
        GROUPS = {}
        if RootDSE == None:
            if self.PrintScreen: print("GetUserGroups:: Search Root DSE Failed")
            self.ldap_error = "Search Root DSE Failed"
            return None

        if not self.makeConnect(): return None
        Dn = self.GetUserDN(username)

        if Dn == None:
            if self.PrintScreen: print("GetUserGroups: Unable to find user DN try without @")
            matches = re.search('^(.+?)@', username)
            if self.PrintScreen: print("GetUserGroups: Unable to find user DN try with " + matches.group(1))
            if matches: Dn = self.GetUserDN(matches.group(1))

        if Dn == None:
            if self.PrintScreen: print("GetUserGroups: Unable to find user DN")
            self.ldap_error = "GetUserGroups: Unable to find user DN"
            self.Disconnect()
            return None

        criteria = ldap.filter.filter_format('(&(objectClass=group)(member=%s))', [Dn, ])
        attributes = ['sAMAccountName', 'dn']

        try:
            if self.PrintScreen: print("Search:", criteria)
            if self.PrintScreen: print("RootDSE:", RootDSE)
            result_data = self.ldap.search_s(RootDSE, ldap.SCOPE_SUBTREE, criteria, attributes)

            for dn, entry in result_data:
                if "sAMAccountName" in entry:
                    if self.PrintScreen: print("FOUND", entry["sAMAccountName"][0])
                    GroupName = entry["sAMAccountName"][0]
                    GroupName = GroupName.decode('utf-8').lower()
                    GROUPS[GroupName] = True


        except Exception as error:
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
            self.ldap_error = "Active Directory down"
            self.Disconnect()
            return False

        self.Disconnect()
        return True
        pass
