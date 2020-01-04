<?php
class nyagetmedia {

    /**
     * @description: 获得该上传资源的信息
     * @param String path 相对路径
     * 例如： /2020/03/19/15845549880_0501e63e1cfbc7ab81261bc84da84b91
     */
    function getmedia(string $path) {
        global $nlcore;
        $uploaddir = $nlcore->cfg->app->upload["uploaddir"];
    }
}
?>