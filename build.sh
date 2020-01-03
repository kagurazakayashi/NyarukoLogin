#!/bin/bash
echo "build: convertfile"
go build tools/convertfile.go
mv -f convertfile bin/