$sendTime = [DateTimeOffset]::Now.ToUnixTimeMilliseconds()
$result = Invoke-RestMethod -Uri "http://127.0.0.1:9080/ping?timestamp=$sendTime" -Method GET
$receiveTime = [DateTimeOffset]::Now.ToUnixTimeMilliseconds()
$totalDelay = $receiveTime - $sendTime

Write-Output "Server pong (one-way): $($result.pong) ms"
Write-Output "Server time: $($result.servertime)"
Write-Output "Total RTT (client): $totalDelay ms"