#!/usr/bin/env python
from unix import *
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import os.path
import logging
import re
import socket


class SQUID_CLIENT:
    
    def __init__(self):
        self.SquidMgrListenPort=GET_INFO_INT("SquidMgrListenPort")
        self.debug=False
        self.log=object
        pass
    
    
    def MakeQuery(self,query):
        fulldata=""
        data=""
        try:
            squid_sock = socket.socket()
            print("127.0.0.1:%s " % str(self.SquidMgrListenPort))
            squid_sock.connect(("127.0.0.1", self.SquidMgrListenPort))
            squid_sock.settimeout(0.25)
            squid_sock.sendall("GET cache_object://localhost/"+query+" HTTP/1.0\r\n" +
                       "Host: localhost\r\n" +
                       "Accept: */*\r\n" +
                       "Connection: close\r\n\r\n")
              
            while True:
                data = squid_sock.recv(1024)
                if not data:
                    break
                data=data.replace("\r","")
                fulldata=fulldata+data
        except Exception as e:
            self.log.error('Couldnt connect to squid: %s', e)
            return fulldata
        
        squid_sock.close()
        return fulldata
        pass
        
        
    
    
