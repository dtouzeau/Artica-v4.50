#!/usr/bin/env python
from unix import *
import os.path
import MySQLdb

class MYSQLENGINE:
    
    def __init__(self,logging):
        self.ok=False
        self.ProxyUseArticaDB=0
        self.database="proxydb"
        self.host="'/var/run/ArticaStats'"
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
        
        self.BuildSettings()
        pass
    
    def BuildSettings(self):
        self.mysql_password=file_get_contents("/etc/artica-postfix/settings/Mysql/database_password")
        self.mysql_admin=file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin")
        self.mysql_server=file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server")
        
        data=file_get_contents("/etc/artica-postfix/settings/Mysql/port")
        testdata=unicode(data,'utf-8')
        if data =='':
            data=0
        if testdata.isnumeric():
            self.mysql_port=int(data)
  
        self.mysql_server=self.mysql_server.strip()
        self.mysql_admin=self.mysql_admin.strip()
        self.mysql_password=self.mysql_password.strip()
        
        if self.mysql_server =='localhost.localdomain':
            self.mysql_server='127.0.0.1'         
        
        if self.mysql_server =='localhost':
            self.mysql_server='127.0.0.1'        
        
        if self.mysql_server =='':
            self.mysql_server='127.0.0.1'
            
        if self.mysql_admin=='':
            self.mysql_admin='root'
        
        if self.mysql_port == 0:
            self.mysql_port=3306
            
        pass
    
    def Connect(self):
        if self.mysql_server=='127.0.0.1':
            self.ConnectionString="Unix socket on /var/run/mysqld/mysqld.sock"
            try:
                self.connection = MySQLdb.connect(user="root",passwd="",db="artica_backup",unix_socket='/var/run/mysqld/mysqld.sock')
            except MySQLdb.Error, e:
                self.mysql_error="Unable to connect:"+self.ConnectionString +" "+str(e.args[0])+" "+ str(e.args[1])
                self.logging.info("[MySQL]: Unable to connect :"+self.ConnectionString+" "+str(e.args[0])+" "+ str(e.args[1]))
                return False
                    
                
        else:
            self.ConnectionString="TCP on "+str(self.mysql_admin)+"@"+self.mysql_server+":"+str(self.mysql_port)
        try:
            self.connection = MySQLdb.connect(host=self.mysql_server,port=self.mysql_port,user=self.mysql_admin,passwd=self.mysql_password,db="artica_backup")
        except MySQLdb.Error, e:
            self.mysql_error="Unable to connect:"+self.ConnectionString +" "+str(e.args[0])+" "+ str(e.args[1])
            self.logging.info("[MySQL]: Unable to connect :"+self.ConnectionString+" "+str(e.args[0])+" "+ str(e.args[1]))
            return False
                    
        return True        
        pass
    
    def Disconnect(self):
        try:
            self.connection.close()
        except:
            self.logging.info("[MySQL]: Error on close")
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
        except MySQLdb.Error, e:
            self.ok=False
            self.mysql_error="Error "+str(e.args[0])+" "+ str(e.args[1])
            self.logging.info("[MySQL]: Error :"+self.ConnectionString+" "+str(e.args[0])+" "+ str(e.args[1]))
            if cur is not None:
                cur.close()
            self.connection.close()
            self.Disconnect()
            return rows         
    
        self.ok=True
        
        try:
            rows = cur.fetchall()
        except MySQLdb.Error, e:
            if cur is not None:
                cur.close()
                self.Disconnect()
                return rows
              

        if cur is not None:
            cur.close()
        self.Disconnect()
        return rows
        pass
    
