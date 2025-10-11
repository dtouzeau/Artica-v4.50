#!/usr/bin/env python
from unix import *
import os.path
import datetime
import urllib
import ldap
import traceback as tb

class CLLDAP:
    
    def __init__(self,logging):
        self.server=file_get_contents("/etc/artica-postfix/ldap_settings/server")
        self.admin=file_get_contents("/etc/artica-postfix/ldap_settings/admin")
        self.password=file_get_contents("/etc/artica-postfix/ldap_settings/password")
        self.port=strtoint(file_get_contents("/etc/artica-postfix/ldap_settings/port"))
        self.suffix=file_get_contents("/etc/artica-postfix/ldap_settings/suffix")
        if self.port==0:
            self.port=389
        if len(self.server)<3:
            self.server="127.0.0.1"
            
        self.logging=logging
    pass


    def Connect(self):
        self.logging.debug("Connect to "+self.server+" On port "+str(self.port)+" With DN= cn="+self.admin+","+self.suffix)
        
        self.ldap = ldap.open(self.server,self.port)
        self.ldap.protocol_version = ldap.VERSION3
        self.ldap.set_option(ldap.OPT_TIMEOUT, 3)
        try:
            self.ldap.simple_bind("cn="+self.admin+","+self.suffix, self.password)
        except:
            self.logging.info(tb.format_exc())
            return False
        
        return True
        pass
    
    def Disconnect(self):
        try:
            self.ldap.unbind()
        except:
            self.logging.info(tb.format_exc())
        pass
    
            
    
    def GetUserPassword(self,uid):
        if not self.Connect():
            self.Disconnect()
            return ""
        baseDN="dc=organizations,"+self.suffix
        retrieveAttributes = ['userPassword']
        searchScope = ldap.SCOPE_SUBTREE
        searchFilter = "uid="+uid

        try:
            ldap_result_id = self.ldap.search(baseDN, searchScope, searchFilter, retrieveAttributes)
            result_set = []
            while 1:
                result_type, result_data = self.ldap.result(ldap_result_id, 0)
                if (result_data == []):
                    break
                else:
                    if result_type == ldap.RES_SEARCH_ENTRY:
                        result_set.append(result_data)
            if len(result_set) == 0:
                self.logging.debug("ldap.search(1): uid --> no match '"+uid+"'")
                self.Disconnect()
                return ""
            
            for i in range(len(result_set)):
                for entry in result_set[i]:
                    userPassword = entry[1]['userPassword'][0]
                    if(len(userPassword)>0):
                        self.Disconnect()
                        return userPassword
        except :
            self.logging.info(tb.format_exc())
            self.Disconnect()
            
        self.logging.debug("ldap.search(2) uid --> no match '"+uid+"'")
        self.Disconnect()
        pass
    
        

