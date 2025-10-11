module server

go 1.18

replace handlers => ../handlers

replace storage => ../storage

replace categorization => ../categorization

replace shields => ../shields

replace ufdbguard => ../ufdbguard

replace itchart => ../itchart

require (
	categorization v0.0.0-00010101000000-000000000000
	github.com/facebookgo/pidfile v0.0.0-20150612191647-f242e2999868
	github.com/fasthttp/router v1.4.6
	github.com/oschwald/geoip2-golang v1.7.0
	github.com/sirupsen/logrus v1.8.1
	github.com/techoner/gophp v0.2.0
	github.com/valyala/fasthttp v1.34.0
	handlers v0.0.0-00010101000000-000000000000
	shields v0.0.0-00010101000000-000000000000
	storage v0.0.0-00010101000000-000000000000
	ufdbguard v0.0.0-00010101000000-000000000000
)

require (
	github.com/allegro/bigcache/v3 v3.0.2 // indirect
	github.com/andres-erbsen/clock v0.0.0-20160526145045-9e14626cd129 // indirect
	github.com/andybalholm/brotli v1.0.4 // indirect
	github.com/bradfitz/gomemcache v0.0.0-20220106215444-fb4bf637b56d // indirect
	github.com/cespare/xxhash/v2 v2.1.2 // indirect
	github.com/d3mondev/resolvermt v0.3.2 // indirect
	github.com/dgryski/go-rendezvous v0.0.0-20200823014737-9f7001d12a5f // indirect
	github.com/facebookgo/atomicfile v0.0.0-20151019160806-2de1f203e7d5 // indirect
	github.com/go-redis/redis/v8 v8.11.5 // indirect
	github.com/klauspost/compress v1.15.0 // indirect
	github.com/leekchan/timeutil v0.0.0-20150802142658-28917288c48d // indirect
	github.com/miekg/dns v1.1.40 // indirect
	github.com/op/go-logging v0.0.0-20160315200505-970db520ece7 // indirect
	github.com/oschwald/maxminddb-golang v1.9.0 // indirect
	github.com/savsgio/gotils v0.0.0-20211223103454-d0aaa54c5899 // indirect
	github.com/valyala/bytebufferpool v1.0.0 // indirect
	go.uber.org/ratelimit v0.2.0 // indirect
	golang.org/x/crypto v0.0.0-20220331220935-ae2d96664a29 // indirect
	golang.org/x/net v0.0.0-20220225172249-27dd8689420f // indirect
	golang.org/x/sys v0.0.0-20220325203850-36772127a21f // indirect
)
