#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import socket
import logging
import ldap
import re
from unix import *
import mysql.connector
from mysql.connector import Error
from localldapclass import *
from activedirectoryclass import *

class ARTICAAUTH:
    
    def __init__(self,logging):
        self.ok=False;
        self.logging=logging
        self.PrintScreen=False
        pass

#------------------------------------------------------------------------------------------------------------------------------------------------
    def artica_authenticate_mysql(self,username,password,GroupToCheck):
        q=MYSQLENGINE(self.logging)
        q.OnlyMySQL=True
        q.mysql_database="artica_backup"
        GroupToCheck=self.CleanGroups(GroupToCheck)

        row=q.QUERY_SQL("SELECT value FROM radcheck WHERE username='"+username+"' and `attribute`='Cleartext-Password'")
        if not q.ok:
            self.logging.info("[ARTICAAUTH]:"+ q.mysql_error)
            return False
        if len(row)==0:return False
        sql_password=row[0][0]
        if password!=sql_password: return False
        if len(GroupToCheck)==0:return True
        
        zPos=GroupToCheck.find(",")
        if zPos==-1:
            row=q.QUERY_SQL("SELECT id FROM radusergroup WHERE username='"+username+"' and `groupname`='"+GroupToCheck+"'")
            if len(row)==0: return False
            zId=int(row[0][0])
            if zId>0:return True
            return False
        
        if zPos>0:
            TBL=GroupToCheck.split(",")
            for zGroup in TBL:
                GroupToCheck=str(zGroup)
                GroupToCheck=zGroup.strip()
                GroupToCheck=GroupToCheck.lower()
                self.logging.info("[ARTICAAUTH]:Checking "+username+" against "+str(GroupToCheck))
                row=q.QUERY_SQL("SELECT id FROM radusergroup WHERE username='"+username+"' and `groupname`='"+GroupToCheck+"'")
                if len(row)==0: continue
                zId=int(row[0][0])
                if zId>0:return True
        
        return False
        pass
#------------------------------------------------------------------------------------------------------------------------------------------------
    def artica_authenticate_ldap(self,username,password,GroupToCheck):
        ldap=LOCALLDAP(self.logging)
        UserPassword=ldap.GetUserPassword(username)
        if len(UserPassword)==0: return False
        if password!=UserPassword: return False
        if len(GroupToCheck)==0:return True
        MainGroups=ldap.GetUserGroups(username)
        
        zPos=GroupToCheck.find(",")
        if zPos==-1:
            if GroupToCheck in MainGroups: return True
            if self.PrintScreen: print "GroupToCheck:",GroupToCheck," not found in ",MainGroups
            
        
        if zPos>0:
            TBL=GroupToCheck.split(",")
            for zGroup in TBL:
                GroupToCheck=self.CleanGroups(str(zGroup))
                if GroupToCheck in MainGroups: return True
                if self.PrintScreen: print "GroupToCheck:",GroupToCheck," not found in ",MainGroups
                
        return False
        pass            
#------------------------------------------------------------------------------------------------------------------------------------------------
    def artica_authenticate_AD(self,hostname,username,password,GroupToCheck):
        AD=ActiveDirectory(self.logging)
        AD.username=username
        AD.password=password
        remote_port=389
        matches=re.match("^(.+?):([0-9]+)",hostname)
        if matches:
            hostname=matches.group(1)
            remote_port=int(matches.group(2))
            
        AD.ldap_server=hostname
        AD.remote_port=remote_port
        
        if not AD.TestBind(): return False
        if len(GroupToCheck)==0:return True
        
        MainGroups=AD.GetUserGroups(AD.username)
        
        zPos=GroupToCheck.find(",")
        if zPos==-1:
            if GroupToCheck in MainGroups: return True
            if self.PrintScreen: print "GroupToCheck:",GroupToCheck," not found in ",MainGroups
            
        
        if zPos>0:
            TBL=GroupToCheck.split(",")
            for zGroup in TBL:
                GroupToCheck=self.CleanGroups(str(zGroup))
                if GroupToCheck in MainGroups: return True
                if self.PrintScreen: print "GroupToCheck:",GroupToCheck," not found in ",MainGroups
                
        return False
        pass            
#------------------------------------------------------------------------------------------------------------------------------------------------  
    def CleanGroups(self,GroupToCheck):
        GroupToCheck=GroupToCheck.strip()
        GroupToCheck=GroupToCheck.lower()
        if GroupToCheck=='*': GroupToCheck=""
        if GroupToCheck=='everyone': GroupToCheck=""
        if GroupToCheck=='tout le monde': GroupToCheck=""
        return GroupToCheck
        

    
    


