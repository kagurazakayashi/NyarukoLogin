$body = @{
    username = "user"
    password = "pass"
    appkey   = "appkey"
} | ConvertTo-Json

try {
    $result = Invoke-RestMethod -Uri "http://127.0.0.1:9080/validate" -Method POST -Body $body -ContentType "application/json; charset=utf-8"
    Write-Output "Success: $($result.success)"
    Write-Output "Token: $($result.token)"
    Write-Output "Message: $($result.message)"
} catch {
    Write-Output "Request failed: $_"
}
