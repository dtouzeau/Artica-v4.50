#!/usr/bin/env python
from unix import *
import os.path
import sqlite3

class ArticaEvents:
    
    def __init__(self,logging):
        self.ok=False;
        self.ProxyUseArticaDB=0
        self.database="artica_events"
        self.hostname="/var/run/ArticaStats"
        self.portname=0
        self.host=''
        self.ConnectionString=''
        self.sql_error=''
        self.database='proxydb'
        self.logging=logging
        self.connection=object
        self.connection_state=False
        self.mysql_password=''
        self.mysql_error=''
        self.mysql_username='root'
        self.mysql_port=0
        self.mysql_server="127.0.0.1"
        self.ok=True
        
        
        pass
    
    def mysql_escape_string(self,zvalue):
        return zvalue
        pass
    
    
    def Connect(self):
        try:
            self.connection = sqlite3.connect('/home/artica/SQLITE/system_events.db')                 
            return True
        except:
            self.logging.info("[SQLITE]: Error on OPEN /home/artica/SQLITE/system_events.db")
            return False
        pass
    
    def Disconnect(self):
        try:
            self.connection.close()
        except:
            self.logging.info("[SQLITE]: Error on close")
        pass
    
    
    def QUERY_SQL(self,sql):
        self.ok=True
        if not self.Connect():
            self.ok=False
            return False
        cur = self.connection.cursor()
        rows = []
        
        try:
            cur.execute(sql)
        except sqlite3.Error as e:
            self.ok=False
            self.mysql_error="Error "+str(e.args[0])+" "+ str(e.args[1])
            self.logging.info("[SQLITE]: Error :"+self.ConnectionString+" "+str(e.args[0])+" "+ str(e.args[1]))
            if cur is not None:
                cur.close()
            self.Disconnect()
            return rows         
    
        self.ok=True
        
        try:
            rows = cur.fetchall()
        except sqlite3.Error as e:
            if cur is not None:
                cur.close()
                self.Disconnect()
                return rows
              

        if cur is not None:
            cur.close()
       
        self.Disconnect()    
        return rows
        pass
    
    def hotspot_admin_sql(self,severity,subject,content,function):
        subject=MySQLdb.escape_string(subject)
        content=MySQLdb.escape_string(content)
        sql="INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES"
        sql=sql+"(NOW(),'"+content+"','"+subject+"','"+function+"','hotspot-service','0','"+str(severity)+"')"
        self.QUERY_SQL(sql)
        pass
    
            
        
        
        