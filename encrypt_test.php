<?php
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
require_once $phpfiledir."src/nyarsa.class.php";
$rsa = new nyarsa();
$rsa->createKey();
die(json_encode([
    "privateKey" => $rsa->privateKey,
    "publicKey" => $rsa->publicKey
]));

$rsa->privateKey = "-----BEGIN PRIVATE KEY-----\n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\nIN/UmhlRItQPr5hO4Gu2OcAsxbAWyA==\n-----END PRIVATE KEY-----\n";
$rsa->publicKey = "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAts5Z5Q6AA0RTwm8UZgt7\ncC2zFIzSYuEJz+ZjUUKuQI5UTI53L2x/zvr7TogPVho9EEmd9VqLBZqFQA1C0+hf\nZmArOyYuJna2t+AXaTCWR5txRyTCKaXmMxcwTMNsoJdW2iJssJ5Cd9OJv+v7X/QW\njDSJz7NxiFOQ48t4F51fG72sl5G7NjhsVF9bsOxFtQUh8AN3z7hjbfCRAgn7dFM9\nPyCYOowXWEGmK6VVuan8LYgOdJO5lH0hmAsVLDVSOpJf8TxcUpHtC7p+FxM3ePOh\nfRQRjztXXuG/Il8RG25HeNcyQZh0okPnfv8x2fo28NZf3wj72L6JxRDy4OSJ0ldl\nbyO2TCxnB/xW+bFTNHGUfrDG1l4/ZgdzhjPna9HPPjuLDRPZteNoZyJgWoRwzydb\nXNs9YB7MRqINGfvGESEGyNo8MHHIqN6pekpSpyQZB7pBlfsviRsXsBbeZmuz82ga\nHQsD/xvzGkKIsg4v+iqXK6O/Fkk6cO4Tz32J3KevbyHaujCdUXpehMl4STmwbMX1\neS+58qoUf+JuAqWkRs7D5f44b+F2cUinxEQlFRbxj5HKH1zE55aE+MRyAmU9TccW\niXYQDElpaY+U0JMmmvBpuFItlPGAJIGf72fAFqu0zfbA/7bTMcOexIDNuQVleEpu\nJx7B1hkr3bT5L9nhIzE73YkCAwEAAQ==\n-----END PUBLIC KEY-----\n";


// $etxt = $rsa->encrypt("微风拂过，最初的羁绊。繁花，绽放甘美的旅程。苍白大雪中，爱恨终结。明月孤悬，而希望……在生长。",true);
// echo $rsa->decrypt($etxt,false);

$rsa->publicKey = base64_decode(str_replace(['-','_'],['+','/'],$_GET["key"]));
$txt = $_GET["txt"];
$dtxt = $rsa->decrypt($txt,true);

// echo json_encode([
//     "get" => $txt,
//     "decrypt" => $dtxt
// ]);

$dtxt = $dtxt. " OK";
$dtxt = $rsa->encrypt($dtxt,false);
echo $dtxt;

?>
