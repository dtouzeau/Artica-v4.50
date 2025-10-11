#/usr/bin/python
# -*- coding: UTF-8 -*-

#Author:Peter Manev <petermanev@gmail.com>
#File: Follow_JSON_Multi.py
#File: Hollow_JSON_Multi.yaml

#Copyright (C) 2012 Open Information Security Foundation

#You can copy, redistribute or modify this Program under the terms of
#the GNU General Public License version 2 as published by the Free
#Software Foundation.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#
#You should have received a copy of the GNU General Public License
#version 2 along with this program; if not, write to the Free Software
#Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
#02110-1301, USA.



import multiprocessing
from multiprocessing import Pool
import time, sys, os
import json #for JSON parsing
import socket
import yaml # for yaml parsing
import MySQLdb #for MySQL
import psycopg2 #for PostgreSQL

#we need 
#sudo apt-get install python-yaml python-mysqldb python-psycopg2
#!!!! -- for this to work --!!!!

def follow(thejsonfile):
    if yaml_options['Insert'] == "continuous":
      thejsonfile.seek(0,2) 
      while True:
        line = thejsonfile.readline()
        if not line:
            time.sleep(0.1)
            continue
        yield line
    elif yaml_options['Insert'] == "notcontinuous":      
      while True:
	line = thejsonfile.readline()
	#print line
	if not line:
	  return
	  #sys.exit()
	yield line
	#return line
    else:
      print "Insert option in yaml should be ONLY continuous or notcontinuous!"
      print "Check your spelling. "
      sys.exit(1)
	

def jsonlog():
  try:
    logfile = open(yaml_options['Json-log']['files-json'],"r")
  except IOError as e:
    print "I/O error({0}): {1}".format(e.errno, e.strerror), "ERROR !!!"
    
  return logfile

  
def db_establish_conn():
  if yaml_options['Database']['type'] == "MySQL":
    #db = MySQLdb.connect("localhost","testuser","test123","TESTDB" )
    database = MySQLdb.connect(host=yaml_options['Database']['host'],user=yaml_options['Database']['user'],passwd=yaml_options['Database']['pass'],port=yaml_options['Database']['port'],db=yaml_options['Database']['dbname'] )
    cur = database.cursor()
    return database, cur
    
  if yaml_options['Database']['type'] == "PostgreSQL":
    database = psycopg2.connect(host=yaml_options['Database']['host'],user=yaml_options['Database']['user'],password=yaml_options['Database']['pass'],port=yaml_options['Database']['port'],database=yaml_options['Database']['dbname'] ) 
    cur = database.cursor()
    return database, cur
  
  
def insert_it(line_dictionary_json):
	#print "in DB"
	(db, cursor) = db_establish_conn()
	json_line_dic = line_dictionary_json
	hostname=socket.getfqdn()
  	### code start for mysql ##
	# Execute the SQL command
	try:
	  cursor.execute("""
			INSERT INTO %s (time_received, ipver, srcip, dstip, protocol, sp, dp, http_uri, http_host, http_referer, filename, magic, state, md5, stored, size,proxyname)
			VALUES 
			  (%%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s, %%s)
	
		    """ % yaml_options['Database']['dbtable'],
		    (json_line_dic['timestamp'], json_line_dic['ipver'], json_line_dic['srcip'], json_line_dic['dstip'], json_line_dic['protocol'], json_line_dic['sp'], json_line_dic['dp'], json_line_dic['http_uri'], json_line_dic['http_host'], json_line_dic['http_referer'], json_line_dic['filename'], json_line_dic['magic'], json_line_dic['state'], json_line_dic['md5'], json_line_dic['stored'], json_line_dic['size'],hostname))
		    
        # Commit the changes in the database
	  db.commit()
	except MySQLdb.Error, e:
	  print "crap", e, "ERROR !!!"
	  db.rollback()
	 ## Rollback in case there is any error
	 ## code end for mysql ##

  
        
class ParseYamlConfig():
	
	def parse(self):
		try:
                  f = open('/etc/suricata/Hollow_JSON_Multi.yaml', 'r')
		except IOError as e:
		  print "I/O error({0}): {1}".format(e.errno, e.strerror), "ERROR !!!"
		
		self.dataMap = yaml.load(f)
		f.close()
		
		if not self.dataMap['Threads']['number_of_threads'] or not \
		((str((self.dataMap['Threads']['number_of_threads']))).isdigit() or \
		self.dataMap['Threads']['number_of_threads'] == "auto"):
		  print "NUMBER_OF_THREADS in the YAML configuration must be set to a digit or auto !!!"
		  print "EXITING"
		  sys.exit(1)
		  
		if not self.dataMap['Threads']['chunks'] or not \
		((str((self.dataMap['Threads']['chunks']))).isdigit() or \
		self.dataMap['Threads']['chunks'] == "auto" ):
		  print "CHUNKS in the YAML configuration must be set to a" \
		  " digit or auto !!!"
		  print "Check your spelling. EXITING"
		  sys.exit(1)
		  
		if not self.dataMap['Insert'] == "notcontinuous" \
		and not self.dataMap['Insert'] == "continuous":
		  print "Insert option in yaml should be ONLY \"continuous\" or" \
		  " \"notcontinuous\" !!!"
		  print "Check your spelling. EXITING"
		  print "You specified - \"%s\"" % self.dataMap['Insert']
		  sys.exit(1)
		
		return self.dataMap;
		
		
		
def main_loop(jsonlines):
  json_line_dic = {}
  line = jsonlines

 ###
  try:
    json_line_dic = json.loads(line)
  except ValueError, e:
    json_line_dic = {}
    print "ERROR!!!", e
    
     
  for key in json_keys:
    if key not in json_line_dic.keys():
      json_line_dic[key] = ""
	  
  insert_it(json_line_dic)
  print "entry inserted"
  ###
    
def get_processes():
  
  if yaml_options['Threads']['number_of_threads'] == "auto":
    processes_to_start = multiprocessing.cpu_count()
    return processes_to_start
  else:
    processes_to_start = yaml_options['Threads']['number_of_threads']
    return processes_to_start
    
    
def get_chunks():
  if yaml_options['Threads']['chunks'] == "auto":
    chunks = 10
    return chunks
  else:
    chunks = yaml_options['Threads']['chunks']
    return chunks
  
		
if __name__ == '__main__':
    json_line_dic= {}
    json_keys = ["timestamp", "ipver", "srcip", "dstip", "protocol", "sp", "dp", "http_uri", "http_host", "http_referer", "filename", "magic", "state", "md5", "stored", "size"]
    
    yaml_options = ParseYamlConfig().parse()
    
    processes_to_start = get_processes()
    chunks = get_chunks()
    myPID=str(os.getpid())
    file("/var/run/suricata-tail.pid",'w').write("%s" % myPID)
    

    print "Starting %s : processes, reading %s : lines at a time each" % \
    (processes_to_start, chunks)
    
    loglines = follow(jsonlog())
    
    
    pool = multiprocessing.Pool(processes_to_start)
    pool.imap(main_loop, (loglines), chunks)
    pool.close()
    pool.join()


    #db.close()
        
        
        
        
        
        
        
