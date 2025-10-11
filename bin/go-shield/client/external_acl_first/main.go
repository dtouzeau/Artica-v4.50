package main

import (
	"bufio"
	b64 "encoding/base64"
	"github.com/bradfitz/gomemcache/memcache"
	"log/syslog"
	"net"
	"os/signal"
	"runtime/debug"
	"strings"
	"syscall"
	"time"

	//_ "expvar"
	"fmt"
	"github.com/valyala/fasthttp"
	"handlers"
	"log"
	"os"
	"storage"
	"strconv"
	"sync"
)

var (
	rewriter_exit_chan  chan int       = make(chan int, 1)
	response_chan       chan string    = make(chan string, 1024*10)
	signal_hup_chan     chan os.Signal = make(chan os.Signal, 1)
	stdin_line_chan     chan string    = make(chan string, 200)
	signalInterruptChan chan os.Signal = make(chan os.Signal, 1)
)

var (
	addr     = "127.0.0.1"
	port int = 3333
	//CONN_TYPE = "tcp"
	version                 = "1.0.11"
	isDebug            bool = false
	timeOut            int  = 5
	SelfSquidUrgency   bool
	strContentType     = []byte("Content-Type")
	strApplicationJSON = []byte("application/json")
)

type CategoriesResponse struct {
	Category_id   string
	Category_name string
}

//var answer *string
//var err error

func init() {
	log.SetFlags(log.LstdFlags | log.Lshortfile)
	if _slog, err := syslog.New(syslog.LOG_DEBUG, "go-shield-connector"); err == nil {
		log.SetOutput(_slog)
	}
	signal.Notify(signal_hup_chan, syscall.SIGHUP)
	signal.Notify(signalInterruptChan, os.Interrupt, syscall.SIGTERM)
}

func main() {
	defer func() {
		if r := recover(); r != nil {
			log.Printf("Panic: %v,\n%s", r, debug.Stack())
			os.Exit(1)
		}
	}()

	addr = handlers.GetSocketInfoString("Go_Shield_Connector_Addr")
	port = handlers.GetSocketInfoInt("Go_Shield_Connector_Port")
	isDebug = handlers.GetSocketInfoBool("Go_Shield_Connector_Debug")
	timeOut = handlers.GetSocketInfoInt("Go_Shield_Connector_TimeOut")
	SelfSquidUrgency = handlers.GetSocketInfoBool("SquidUrgency")
	if addr == "" {
		addr = "127.0.0.1"
	}
	if port == 0 {
		port = 3333
	}
	if timeOut == 0 {
		timeOut = 5
	}
	//dns1_token_int, _ := strconv.Atoi(dns1_token)
	//dns2_token_int, _ := strconv.Atoi(dns2_token)
	//addr_token_int, _ := strconv.Atoi(addr_token)
	//port_token_int, _ := strconv.Atoi(port_token)

	//internal.SetSocketInfo("Go_Shield_Connector_Version", version)
	//addr_token_int, _ := strconv.Atoi(addr_token)
	//port_token_int, _ := strconv.Atoi(port_token)

	//mc := memcache.New("/var/run/memcached.sock")

	storage.MC.Set(&memcache.Item{Key: "Go-Shield-Connector-Version", Value: []byte(version), Expiration: 2590000})
	isDebug = true
	var wg sync.WaitGroup

	reader := bufio.NewReader(os.Stdin)
	//http.ListenAndServe(":1234", nil)
	for {
		line, err := reader.ReadString('\n')

		if err != nil {
			// You may check here if err == io.EOF
			break
		}

		wg.Add(1)
		go ProcessRequest(line, &wg)

	}
	wg.Wait()

}

func ProcessRequest(line string, wg *sync.WaitGroup) {
	defer func() {
		if r := recover(); r != nil {
			log.Printf("Panic: %v,\n%s", r, debug.Stack())
			os.Exit(1)
		}
	}()
	defer wg.Done()
	start := time.Now()
	lparts := strings.Split(strings.TrimRight(line, "\n"), " ")
	id := lparts[0]
	query := b64.URLEncoding.EncodeToString([]byte(line))

	if SelfSquidUrgency {
		if isDebug {
			log.Printf("WARNING... Emergency Enabled")
		}
		defer wg.Done()
		fmt.Println(id + "OK first=EMERGENCY webfilter=pass\n")
		return
	}
	if isDebug {
		log.Printf("Receive <%s> Query <%s>", line, query)
	}
	req := fasthttp.AcquireRequest()
	defer fasthttp.ReleaseRequest(req)
	log.Printf("http://" + addr + ":" + strconv.Itoa(port) + "/external-acl-first/" + query)
	req.SetRequestURI("http://" + addr + ":" + strconv.Itoa(port) + "/external-acl-first/" + query)

	resp := fasthttp.AcquireResponse()
	defer fasthttp.ReleaseResponse(resp)
	client := &fasthttp.Client{
		ReadTimeout:         time.Duration(timeOut) * time.Second,
		MaxIdleConnDuration: time.Duration(10) * time.Second,
		Dial: func(addr string) (net.Conn, error) {
			return fasthttp.DialTimeout(addr, time.Duration(timeOut)*time.Second)
		},
		//TLSConfig: &tls.Config{InsecureSkipVerify: true},
	}
	//client.Do(req, resp)
	if err := client.DoTimeout(req, resp, time.Duration(timeOut)*time.Second); err != nil {
		fmt.Println(id + " OK first=ERROR")
		return
	}

	bodyBytes := resp.Body()
	code := resp.StatusCode()
	if code == 200 {
		//defer wg.Done()
		elapsed := time.Since(start).Microseconds()
		if isDebug {
			log.Printf("FINISH PARSING - Resp = %s | code = %d - took %d μs", bodyBytes, code, elapsed)
		}
		fmt.Print(string(bodyBytes))
		return
	}
	//defer wg.Done()
	if isDebug {
		log.Printf("Invalid - Resp = %s | code = %d", bodyBytes, code)
	}
	fmt.Print(id + "OK first=ERROR webfilter=pass exterr=invalid_code_%d\n")
	return

}
