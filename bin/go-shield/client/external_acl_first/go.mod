module external_acl_first

go 1.18

replace handlers => ../../handlers

replace storage => ../../storage

require (
	github.com/bradfitz/gomemcache v0.0.0-20220106215444-fb4bf637b56d
	github.com/techoner/gophp v0.2.0
	github.com/valyala/fasthttp v1.34.0
	handlers v0.0.0-00010101000000-000000000000
	storage v0.0.0-00010101000000-000000000000
)

require (
	github.com/allegro/bigcache/v3 v3.0.2 // indirect
	github.com/andybalholm/brotli v1.0.4 // indirect
	github.com/cespare/xxhash/v2 v2.1.2 // indirect
	github.com/dgryski/go-rendezvous v0.0.0-20200823014737-9f7001d12a5f // indirect
	github.com/go-redis/redis/v8 v8.11.5 // indirect
	github.com/klauspost/compress v1.15.0 // indirect
	github.com/leekchan/timeutil v0.0.0-20150802142658-28917288c48d // indirect
	github.com/op/go-logging v0.0.0-20160315200505-970db520ece7 // indirect
	github.com/valyala/bytebufferpool v1.0.0 // indirect
)
