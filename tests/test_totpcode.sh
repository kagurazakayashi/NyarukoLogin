#!/bin/bash
apptoken='d13faDB5370d4581bf5329bC89F58640E939C405763c3b0C0b5b2b9E1D65190b'
while :
do
    curl -X POST -d "t=${apptoken}" "http://dev.zeyuze.com/user/tests/totpcode.php"
    echo
    sleep 1
done
# otpauth://totp/NYATOTPTEST:d13f?secret=IF3GPQSYH3RE6JNKR5YL24AFIDYLCS7NQJE7IGQ4X5CVF3DLUQ4RCBB63X34JCHS&issuer=NYATOTPTEST