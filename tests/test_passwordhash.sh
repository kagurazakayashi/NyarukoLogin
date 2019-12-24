#!/bin/bash
password='testpassword'
datestr='2022-07-31'
timestr='21:37:35'
curl "http://127.0.0.1/user/nyasignup.php?p=${password}&t=${datestr}%20${timestr}&passwordhashtest"
echo