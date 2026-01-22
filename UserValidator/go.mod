module github.com/kagurazakayashi/NyarukoLogin/UserValidator

go 1.24.4

replace github.com/kagurazakayashi/libNyaruko_Go/nyanats => ../libNyaruko_Go/nyanats

require (
	github.com/kagurazakayashi/libNyaruko_Go/nyanats v0.0.0-00010101000000-000000000000
	gopkg.in/yaml.v3 v3.0.1
)

require (
	github.com/google/uuid v1.6.0 // indirect
	github.com/klauspost/compress v1.18.2 // indirect
	github.com/nats-io/nats.go v1.49.0 // indirect
	github.com/nats-io/nkeys v0.4.12 // indirect
	github.com/nats-io/nuid v1.0.1 // indirect
	golang.org/x/crypto v0.46.0 // indirect
	golang.org/x/sys v0.39.0 // indirect
)
