# Test Register Endpoint
$body = @{
    name = "Test User"
    email = "test$(Get-Date -Format 'yyyyMMddHHmmss')@example.com"
    password = "password123"
} | ConvertTo-Json

Write-Host "Testing POST request to /api/register..." -ForegroundColor Yellow
Write-Host "Request Body: $body" -ForegroundColor Cyan

try {
    $response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/register" `
        -Method POST `
        -ContentType "application/json" `
        -Body $body
    
    Write-Host "`nSuccess! Response:" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 10
} catch {
    Write-Host "`nError occurred:" -ForegroundColor Red
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "Status Code: $statusCode" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $reader.BaseStream.Position = 0
        $reader.DiscardBufferedData()
        $responseBody = $reader.ReadToEnd()
        Write-Host "Response Body:" -ForegroundColor Red
        Write-Host $responseBody
    }
}

