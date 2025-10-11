import os
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import traceback as tb
import multiprocessing
import socket
import logging
from theshieldsservice import *
from unix import *



def handle(connection, address):
    import logging
    global ShieldClass
    TheShieldDebug = GET_INFO_INT("TheShieldDebug")
    logger = logging.getLogger("theshields-daemon")
    logger.setLevel(logging.INFO)
    if TheShieldDebug ==1: logger.setLevel(logging.DEBUG)
    formatter = logging.Formatter("%(asctime)s [%(process)d]: %(message)s")
    handler = logging.FileHandler("/var/log/theshields-daemon.log")
    handler.setFormatter(formatter)
    logger.addHandler(handler)


    try:
        logger.debug("Connected %r at %r", connection, address)
        while True:
            data = connection.recv(2048)
            if data == "": break
            logger.debug("Received data %r", data)
            results=TheShieldsClass.response(ReceivedData)
            logger.debug("Sent data %s "% results)
            connection.sendall(results)

    except:
        logger.info("Problem handling request %s" % tb.format_exc())
    finally:
        logger.debug("Closing socket")
        connection.close()

class Server(object):
    def __init__(self, hostname, port):
        import logging
        TheShieldDebug = GET_INFO_INT("TheShieldDebug")
        logger = logging.getLogger("theshields-daemon")
        logger.setLevel(logging.INFO)
        formatter = logging.Formatter("%(asctime)s [%(process)d]: %(message)s")
        handler = logging.FileHandler("/var/log/theshields-daemon.log")
        handler.setFormatter(formatter)
        logger.addHandler(handler)
        self.hostname = hostname
        self.port = port

    def start(self):
        self.logger.debug("listening %s:%s" % (self.hostname, self.port))
        self.socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.socket.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
        self.socket.bind((self.hostname, self.port))
        self.socket.listen(1)

        while True:
            conn, address = self.socket.accept()
            self.logger.debug("Got connection")
            process = multiprocessing.Process(target=handle, args=(conn, address))
            process.daemon = True
            process.start()
            self.logger.debug("Started process %r", process)

if __name__ == "__main__":
    global ShieldClass
    HOST = "127.0.0.1"
    PORT = 2004
    pidfile_path = '/var/run/theshields.pid'
    TheShieldDebug = GET_INFO_INT("TheShieldDebug")

    logger = logging.getLogger("theshields-daemon")
    logger.setLevel(logging.INFO)
    formatter = logging.Formatter("%(asctime)s [%(process)d]: %(message)s")
    handler = logging.FileHandler("/var/log/theshields-daemon.log")
    handler.setFormatter(formatter)
    logger.addHandler(handler)

    ShieldClass = TheShieldsService(logger)
    TheShieldsIP = GET_INFO_STR("TheShieldsIP")
    TheShieldsPORT = GET_INFO_INT("TheShieldsPORT")
    if len(TheShieldsIP) > 3: HOST = TheShieldsIP
    if TheShieldsPORT > 0: PORT = TheShieldsPORT

    server = Server(HOST, PORT)
    try:
        logging.info("[SERVICE]: Listening %s:%s" % (HOST, PORT))
        server.start()
    except:
        logging.info("[SERVICE]: Unexpected exception %s" % tb.format_exc())
    finally:
        logging.info("[SERVICE]:  Shutting down")
        for process in multiprocessing.active_children():
            logging.info("[SERVICE]: Shutting down process %r", process)
            process.terminate()
            process.join()
    logging.info("[SERVICE]: All done")
