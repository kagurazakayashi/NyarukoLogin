#!/bin/bash
file="convertimage"
echo "build: $file"
go build tools/$file.go
mv -f $file bin/$file
chmod +x bin/$file
#
file="convertvideo"
echo "build: $file"
go build tools/$file.go
mv -f $file bin/$file
chmod +x bin/$file
#
file="mserver"
echo "build: $file"
go build tools/$file.go
mv -f $file bin/$file
chmod +x bin/$file