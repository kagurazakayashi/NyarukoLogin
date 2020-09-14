<?php
class nyamediafiles {
    function mediafiles() {
        global $nlcore;
        $inputInformation = $nlcore->sess->decryptargv("session");
        $argReceived = $inputInformation[0];
        $totpToken = $inputInformation[2];
        $ipid = $inputInformation[3];
        $appid = $inputInformation[4];

        if (!isset($argReceived["path"])) {
            $nlcore->msg->stopmsg(2050201);
        }
        $uploaddir = $nlcore->cfg->app->upload["uploaddir"];
        $mediainfo = $nlcore->func->imageurl($argReceived["path"]);
        if (count($mediainfo) > 0) {
            $mediainfo["code"] = 1000000;
        } else {
            $mediainfo["code"] = 2050200;
        }
        echo $nlcore->sess->encryptargv($mediainfo);
    }
}
?>