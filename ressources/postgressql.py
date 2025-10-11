#!/usr/bin/env python
from unix import *
import os.path
import psycopg2

class Postgres:
    
    def __init__(self,loopback=0,username=''):
        
        self.ok=False
        self.database="proxydb"
        self.host="'/var/run/ArticaStats'"
        self.hostname="/var/run/ArticaStats"
        self.portname=0
        self.isRemote=False
        self.InfluxApiIP='127.0.0.1'
        self.InfluxUseRemote=0
        self.InfluxUseRemoteIpaddr=''
        self.InfluxUseRemotePort=0
        self.EnableInfluxDB=1
        self.InfluxSyslogRemote=0
        self.host=''
        self.ConnectionString=''
        self.sql_error=''
        self.database='proxydb'
        self.username=username
        self.log=object
        self.connection=object
        self.connection_state=False
        self.loopback=loopback
        self.BuildParams()

    def BuildParams(self):
        self.InfluxApiIP=GET_INFO_STR("InfluxApiIP")
        self.InfluxUseRemoteIpaddr=GET_INFO_STR("InfluxUseRemoteIpaddr")
        self.InfluxUseRemotePort=GET_INFO_INT("InfluxUseRemotePort")
        self.InfluxUseRemote=GET_INFO_INT("InfluxUseRemote")
        self.EnableInfluxDB=GET_INFO_INT("EnableInfluxDB")
        self.InfluxSyslogRemote=GET_INFO_INT("InfluxSyslogRemote")
        self.host = "'/var/run/ArticaStats'"
        
        if self.InfluxApiIP == '': self.InfluxApiIP ='127.0.0.1'
        if self.InfluxUseRemotePort==0: self.InfluxUseRemotePort=5432
        if self.InfluxUseRemotePort == 8086: self.InfluxUseRemotePort=5432

        if self.loopback==1 :
            self.InfluxUseRemote=1
            self.InfluxUseRemoteIpaddr="127.0.0.1"
            self.host="127.0.0.1"



        if self.InfluxSyslogRemote==1: self.InfluxUseRemote=0
        if len(self.username)==0: self.username="ArticaStats"
        
        if self.InfluxUseRemote==1:
            self.isRemote=True
            self.InfluxApiI=self.InfluxUseRemoteIpaddr
            self.host="'"+self.InfluxApiI+"' port='"+str(self.InfluxUseRemotePort)+"'"
            self.hostname=self.InfluxApiI
            
        self.ConnectionString="host="+self.host+" user='"+self.username+"' dbname='"+self.database+"' connect_timeout='5'"

    def connect(self):
        try:
            self.connection=psycopg2.connect(self.ConnectionString)
        except psycopg2.Error as e:
            self.sql_error='Unable to connect! - ' +self.ConnectionString+' '+str(e.pgerror)
            if hasattr(self.log, 'info'):
                self.log.info('Unable to connect! - ' +self.ConnectionString+' '+str(e.pgerror))
            return False
        self.connection.autocommit = True
        self.connection_state=True
        return True
        
    pass

    def disconnect(self):
        if not self.connection_state:
            return
        
        try:
            cur = self.connection.cursor()
            if cur is not None: cur.close()
            self.connection.close()
        finally:
            self.connection_state=False
    pass
        
    def QUERY_SQL(self,sql):
        self.sql_error=""
        self.ok=False
        if not self.connect():
            self.sql_error=self.sql_error+" Connection failed"
            return ''
        
        try:
            cur = self.connection.cursor()
        except psycopg2.Error as e:
                if hasattr(self.log, 'info'):
                    self.sql_error='connection.cursor() -'+str(e.pgerror)
                    self.log.info('PostGreSQL Error - connection.cursor() -'+str(e.pgerror))
                    return ''
            
        

        try:
            cur.execute(sql)
        except psycopg2.Error as e:
            if hasattr(self.log, 'info'):
                self.sql_error='connection.execute() -'+str(e.pgerror)
                self.log.info('PostGreSQL Error - cur.execute() -'+str(e.pgerror))   
            self.ok=False
            self.sql_error=str(e.pgerror)
            if cur is not None:
                cur.close()
            self.disconnect()
            return ''
            
        self.ok=True
        rows = []
        try:
            rows = cur.fetchall()
        except psycopg2.Error as e:
            if cur is not None:
                cur.close()
                self.disconnect()
                return rows
            self.disconnect()
              

        
        if cur is not None:
            cur.close()
        
        self.disconnect()
        return rows
    pass

    def QUERY_SQL_FETCH_ONE(self,sql):
        self.sql_error=""
        self.ok=False
        if not self.connect():
            self.sql_error=self.sql_error+" Connection failed"
            return ''

        try:
            cur = self.connection.cursor()
        except psycopg2.Error as e:
                if hasattr(self.log, 'info'):
                    self.sql_error='connection.cursor() -'+str(e.pgerror)
                    self.log.info('PostGreSQL Error - connection.cursor() -'+str(e.pgerror))
                    return ''



        try:
            cur.execute(sql)
        except psycopg2.Error as e:
            if hasattr(self.log, 'info'):
                self.sql_error='connection.execute() -'+str(e.pgerror)
                self.log.info('PostGreSQL Error - cur.execute() -'+str(e.pgerror))
            self.ok=False
            self.sql_error=str(e.pgerror)
            if cur is not None:
                cur.close()
            self.disconnect()
            return ''

        self.ok=True
        rows = []
        try:
            rows = cur.fetchone()
        except psycopg2.Error as e:
            if cur is not None:
                cur.close()
                self.disconnect()
                return rows
            self.disconnect()



        if cur is not None:
            cur.close()

        self.disconnect()
        return rows
    pass