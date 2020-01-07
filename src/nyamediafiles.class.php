<?php
class nyamediafiles {
    function mediafiles() {
        global $nlcore;
        $jsonarrTotpsecret = $nlcore->safe->decryptargv("session");
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $appid = $jsonarrTotpsecret[4];

        if (!isset($jsonarr["path"])) {
            $nlcore->msg->stopmsg(2050201,$totpsecret);
        }
        $uploaddir = $nlcore->cfg->app->upload["uploaddir"];
        $mediainfo = $nlcore->func->imageurl($jsonarr["path"]);
        if (count($mediainfo) > 0) {
            $mediainfo["code"] = 1000000;
        } else {
            $mediainfo["code"] = 2050200;
        }
        echo $nlcore->safe->encryptargv($mediainfo,$totpsecret);
    }
}
?>