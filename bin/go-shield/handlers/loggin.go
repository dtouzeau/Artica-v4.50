package handlers

import (
	"fmt"
	"github.com/leekchan/timeutil"
	"github.com/op/go-logging"
	"math"
	"os"
	"strconv"
	"time"
)

var logstats = logging.MustGetLogger("ksrn-stats")

func IsInt(s string) bool {
	_, err := strconv.Atoi(s)
	return err == nil
}

func TimeTrack(start time.Time) string {
	//elapsed := time.Since(start)
	//log.Printf("%s took %dms", name, elapsed.Nanoseconds()/1000)
	return fmt.Sprintf("took %d μs", time.Since(start).Microseconds())
}

func GetTimeDelta(delta time.Duration, format int) string {
	if delta == 0 {
		delta = 1
	}
	d := time.Minute * delta
	deltaSeconds := float64(d / time.Second) //delta time in seconds
	t := time.Now()
	s := daySeconds(t)                                                       // seconds
	ms := t.Nanosecond() / 1000                                              //Microseconds
	r := math.Floor((float64(s)+deltaSeconds/2)/deltaSeconds) * deltaSeconds //rounding
	rs := int(r)                                                             //rounding in seconds
	o := rs - s
	td := timeutil.Timedelta{Days: 0, Seconds: time.Duration(o), Microseconds: time.Duration(-ms)}
	result := t.Add(td.Duration())
	var str string
	if format == 1 {
		str = timeutil.Strftime(&result, "%Y%m%d%H%M")
	}
	if format == 2 {
		str = timeutil.Strftime(&result, "%Y-%m-%d %H:%M:%S")
	}

	return str
}

func daySeconds(t time.Time) int {
	year, month, day := t.Date()
	t2 := time.Date(year, month, day, 0, 0, 0, 0, t.Location())
	return int(t.Sub(t2).Seconds())
}

func Logfile(filename, data string) {
	f, err := os.OpenFile(filename, os.O_RDWR|os.O_CREATE|os.O_APPEND, 0666)
	if err != nil {
		logstats.Fatal(err)
	}
	defer f.Close()
	backend := logging.NewLogBackend(f, "", 0)
	logging.SetBackend(backend)
	logstats.Info(data)
	//mylog.SetOutput(f)
	//mylog.Println(data)
}
