<?php
require_once "../src/nyacore.class.php";
global $nlcore;
$uploaddir = $nlcore->cfg->app->upload["uploaddir"];
$mediainfo = $nlcore->func->imageurl($_GET["path"],$uploaddir);
echo json_encode($mediainfo);
?>