<?php
require_once "../src/nyacore.class.php";
function microtime_float() {
    list($u_sec, $sec) = explode(' ', microtime());
    return (floatval($u_sec) + floatval($sec));
}
$start_time = microtime_float();
$argv = count($_POST) > 0 ? $_POST : $_GET;
$pwd = $argv["pwd"] ?? $nlcore->cfg->enc->privateKeyPassword;
$nlcore->safe->rsaCreateKey($pwd);
?>
<!doctype html>
<html>

<head>
    <meta charset="unicode">
    <title>RSA密钥生成测试</title>
    <style type="text/css">
        body,
        td,
        th,
        code {
            font-family: Consolas, "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", Monaco, "Courier New", monospace;
            color: #F2F2F2;
            font-size: 14px;
        }

        body {
            background-color: #000000;
            padding: 0;
            margin: 0;
            width: 1050px;
        }

        code {
            word-break: break-all;
        }
    </style>
</head>

<body>
    <table width="100%" border="0" cellspacing="5" cellpadding="5">
        <tbody>
            <tr>
                <td width="50%" align="left" valign="top">
                    <p>PUBLIC KEY
                        <hr />
                    </p>
                    <p><code><?php echo str_replace("\n", "<br/>", $nlcore->sess->publicKey); ?></code></p>
                    <p>&emsp;</p>
                    <p>&emsp;</p>
                    <p>PASSWORD ( <?php echo strlen($pwd); ?> )
                        <hr />
                    </p>
                    <p><code><?php echo $pwd; ?></code></p>
                </td>
                <td width="50%" align="left" valign="top">
                    <p>PRIVATE KEY ( <?php echo $nlcore->cfg->enc->pkeyConfig["private_key_bits"]; ?> bits )
                        <hr />
                    </p>
                    <p><code><?php echo str_replace("\n", "<br/>", $nlcore->sess->privateKey); ?></code></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    $end_time = microtime_float();
    $total_time = $end_time - $start_time;
    $time_cost = sprintf("%.10f", $total_time);
    echo "<code>&emsp;program cost total " . $time_cost . "s</code>";
    ?>
</body>

</html>