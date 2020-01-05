SET file=convertimage
ECHO build: %file%
go build tools\%file%.go
MOVE /Y %file%.exe bin\%file%.exe
REM
SET file=convertvideo
ECHO build: %file%
go build tools\%file%.go
MOVE /Y %file%.exe bin\%file%.exe
REM
SET file=mserver
ECHO build: %file%
go build tools\%file%.go
MOVE /Y %file%.exe bin\%file%.exe