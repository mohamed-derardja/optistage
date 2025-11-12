# Laravel API Endpoint Testing Script
# This script tests various API endpoints

$baseUrl = "http://127.0.0.1:8000/api"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Laravel API Endpoint Testing" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: GET /api/test
Write-Host "1. Testing GET /api/test" -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/test" -Method GET
    Write-Host "   ✓ Success!" -ForegroundColor Green
    $response | ConvertTo-Json
} catch {
    Write-Host "   ✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 2: POST /api/test
Write-Host "2. Testing POST /api/test" -ForegroundColor Yellow
try {
    $body = @{message = "Hello from PowerShell"; timestamp = (Get-Date).ToString()} | ConvertTo-Json
    $response = Invoke-RestMethod -Uri "$baseUrl/test" -Method POST -ContentType "application/json" -Body $body
    Write-Host "   ✓ Success!" -ForegroundColor Green
    $response | ConvertTo-Json
} catch {
    Write-Host "   ✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 3: POST /api/register (with unique email)
Write-Host "3. Testing POST /api/register" -ForegroundColor Yellow
try {
    $uniqueEmail = "test$(Get-Date -Format 'yyyyMMddHHmmss')@example.com"
    $body = @{
        name = "Test User"
        email = $uniqueEmail
        password = "password123"
    } | ConvertTo-Json
    
    $response = Invoke-RestMethod -Uri "$baseUrl/register" -Method POST -ContentType "application/json" -Body $body
    Write-Host "   ✓ Success! User registered with email: $uniqueEmail" -ForegroundColor Green
    Write-Host "   Token: $($response.token)" -ForegroundColor Gray
    $global:testToken = $response.token
    $global:testUserId = $response.user.id
} catch {
    Write-Host "   ✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $stream = $_.Exception.Response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        $errorBody = $reader.ReadToEnd()
        Write-Host "   Error details: $errorBody" -ForegroundColor Red
    }
}
Write-Host ""

# Test 4: POST /api/login
Write-Host "4. Testing POST /api/login" -ForegroundColor Yellow
Write-Host "   (Using the user we just created)" -ForegroundColor Gray
try {
    $body = @{
        email = $uniqueEmail
        password = "password123"
    } | ConvertTo-Json
    
    $response = Invoke-RestMethod -Uri "$baseUrl/login" -Method POST -ContentType "application/json" -Body $body
    Write-Host "   ✓ Success! Logged in" -ForegroundColor Green
    Write-Host "   Token: $($response.token)" -ForegroundColor Gray
    $global:testToken = $response.token
} catch {
    Write-Host "   ✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 5: GET /api/user (requires authentication)
Write-Host "5. Testing GET /api/user (Authenticated)" -ForegroundColor Yellow
if ($global:testToken) {
    try {
        $headers = @{
            "Authorization" = "Bearer $($global:testToken)"
            "Accept" = "application/json"
        }
        $response = Invoke-RestMethod -Uri "$baseUrl/user" -Method GET -Headers $headers
        Write-Host "   ✓ Success! Current user retrieved" -ForegroundColor Green
        $response | ConvertTo-Json
    } catch {
        Write-Host "   ✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
    }
} else {
    Write-Host "   ⚠ Skipped: No authentication token available" -ForegroundColor Yellow
}
Write-Host ""

# Test 6: POST /api/logout (requires authentication)
Write-Host "6. Testing POST /api/logout (Authenticated)" -ForegroundColor Yellow
if ($global:testToken) {
    try {
        $headers = @{
            "Authorization" = "Bearer $($global:testToken)"
            "Accept" = "application/json"
        }
        $response = Invoke-RestMethod -Uri "$baseUrl/logout" -Method POST -Headers $headers
        Write-Host "   ✓ Success! Logged out" -ForegroundColor Green
        $response | ConvertTo-Json
    } catch {
        Write-Host "   ✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
    }
} else {
    Write-Host "   ⚠ Skipped: No authentication token available" -ForegroundColor Yellow
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Testing Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

