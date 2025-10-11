import re
import logging,os,getopt,sys
import psutil,time


def redis_server_pid():
    process_name    = "/usr/bin/redis-server"
    pidpath         = "/var/run/redis.pid"
    daemon_pid      = 0

    if os.path.exists(pidpath):

        with open(pidpath, 'r') as fp:
            try:
                daemon_pid = int(fp.read())
            except:
                pass
        if daemon_pid >0:
            if psutil.pid_exists(daemon_pid): return daemon_pid


    for proc in psutil.process_iter():

        if process_name in proc.exe():
            pid = proc.pid
            return pid

        if "/usr/bin/redis-check-rdb" in proc.exe():
            pid = proc.pid
            return pid


    return None

def file_put_contents(filename,data):
    try:
        f = open(filename, 'w')
        f.write(str(data))
        f.close()
    except:
        return

def file_put_contents(filename,data):
    try:
        f = open(filename, 'w')
        f.write(str(data))
        f.close()
    except:
        return



def redis_server_start():
    spid        = redis_server_pid()
    nohup       = "/usr/bin/nohup"
    pidfile     = "/var/run/redis.pid"
    cmd         = "%s /usr/bin/redis-server /etc/redis/redis.conf >/dev/null 2>&1 &" % nohup


    if spid is not None:
        print("redis_server Already started pid %s..." % spid )
        if not os.path.exists(pidfile):
            file_put_contents(pidfile,str(spid))
        return True

    print("Starting redis_server service...")
    os.system("/bin/mkdir -p /var/run/redis")
    os.system("/usr/bin/chmod 0755 /var/run/redis")
    os.system("/usr/bin/echo never >/sys/kernel/mm/transparent_hugepage/enabled")
    os.system(cmd)

    for i in [0, 1, 2, 3, 4, 5]:
        spid = redis_server_pid()
        if spid is not None:
            print("Starting redis_server service success pid %s..." % spid)
            return True
        print("Starting redis_server waiting %s/5..." % i)
        time.sleep(1)

    print("Starting redis_server service [FAILED] [%s]..." % cmd)
    sys.exit(1)


def redis_server_stop():
    spid = redis_server_pid()

    if spid is None:
        print("redis_server Already stopped...")
        return False

    p = psutil.Process(spid)
    print("Terminate process %s" % spid)
    p.terminate()
    time.sleep(1)
    for i in [0, 1, 2, 3, 4, 5]:
        spid = redis_server_pid()
        if spid is None:
            print("redis_server successfully stopped...")
            return True

        print("Waiting %s/5" % i)
        time.sleep(1)

    spid = redis_server_pid()
    if spid is not None:
        print("Killing process %s" % spid)
        p.kill()

    spid = redis_server_pid()
    if spid is None:
        print("Killing redis_server success")
        return True

    print("Killing redis_server [Failed]")
    sys.exit(1)
    return False




def main(argv):
    if "start" in argv:
        redis_server_start()
        sys.exit(0)
    if "stop" in argv:
        redis_server_stop()
        sys.exit(0)

    if "restart" in argv:
        redis_server_stop()
        redis_server_start()
        sys.exit(0)
if __name__ == "__main__":
   main(sys.argv[1:])

