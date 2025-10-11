#!/usr/bin/env python
"""

Code to read a squid log file
    
Sample usage:

f = SquidLog('access.log.1.gz')
for l in f:
    print l.ts, l.remhost, l.url
   
AJC Nov 2007

"""                          

import gzip
import time   
import sys
import operator
   
# time format for pretty-printing log files   
_time_format = "%Y-%m-%d %H:%M:%S"    

class SquidLogLine(object):
    """Representation of a squid log entry
    
    Items are 'ts', 'elapsed', 'remhost', 'status', 'bytes', 'method', 'url', 'rfc931', 'peerstatus', 'type'
    
    'ip' is available as an alias for 'remhost'
    
    """ 
    fields = ['ts', 'elapsed', 'remhost', 'status', 'bytes', 'method', 'url', 'rfc931', 'peerstatus', 'type']  

    def __init__(self, line):
        """setup fields""" 
        self._print_human_times = True 
        self._print_minimal = False 
        try:                                        
            map( lambda k,v: setattr(self, k, v), SquidLogLine.fields, line.split() )
        except TypeError:
            l = line.split()
            l = l[:6] + [''.join(l[6:-3])] + l[-3:]
            map( lambda k,v: setattr(self, k, v), SquidLogLine.fields, l )
        self.client = self.remhost
        try:
            self.ts = float(self.ts)  
        except TypeError, e:
            # blank line
            if self.ts == None:
                pass
            else:
                raise e
        
    def __str__(self):   
        if self._print_human_times:
            s = "%s " % time.strftime(_time_format, time.localtime(self.ts))
        else:
            s = "%s " % self.ts 
        if self._print_minimal: 
            s += "%s %s %s %s %s" % (self.remhost, self.status[-3:], self.method, self.url, self.type)
        else:
            for k in SquidLogLine.fields[1:]:
                s += "%s " % getattr(self, k)
            s = s[:-1]
        return s
                
class SquidLog(object):
    """
    Class for opening and reading Squid logfile
    f can be any iterator
    """
    def __init__(self, f): 
        """open a squid logfile, optionally gziped""" 
        if type(f) == type(str()):
            # assume it's a filename and try and open it
            try:      
                self.f = gzip.open(f) 
                self.f.next()
                self.f.rewind()
            except IOError, e:         
                self.f = open(f)
            except StopIteration, e:
                pass
        else:
            # it's an iterator of some sort
            self.f = f
        self._print_human_times = True 
        self._print_minimal = False
            
    def __iter__(self):
        return self
        
    def next(self):
        line = self.f.next()
        return SquidLogLine( line)
        
    def close(self):
        """close fh"""
        self.f.close()
        pass
    