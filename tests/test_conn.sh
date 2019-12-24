#!/bin/bash
apptoken='bc51fbdc3ba9bbd377e8c51c972c1b0a465245c29a5342488a929667bca13dc2'
appsecret='vbCxaCOZL36G5EamUIbKC9ABk4aj8L9CTxBrcaJdrdukZJU3PrZs1oAh2UNkK0nW'
curl "http://127.0.0.1/user/tests/conntest.php?t=${apptoken}&apiver=1&appsecret=${appsecret}&testkey1=testval1&testkey2=testval2"
echo