<?php
class userinfo {
    function getuserinfo($echojson=true) {
        global $nlcore;
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("session");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $appid = $jsonarrTotpsecret[4];
        // if (!isset($_FILES["file"])) $nlcore->msg->stopmsg(2050104,$totpsecret);

    }
}
