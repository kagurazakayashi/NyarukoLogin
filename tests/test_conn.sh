#!/bin/bash
apptoken='00d0d6ff9d3f0cea5ca869e09f493e25'
appid='testapp1'
appsecret='mipxT4wpGJ7JD29ZwI87AKmRvvCx19rI'
curl "http://127.0.0.1/NyarukoLogin/tests/conntest.php?t=${apptoken}&apiver=1&appid=${appid}&appsecret=${appsecret}&testkey1=testval1&testkey2=testval2"
echo
