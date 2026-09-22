# Script to find and set JAVA_HOME for this session
# Run this before executing: npx expo run:android

Write-Host "Searching for Java installation..." -ForegroundColor Yellow

# Try to find Java in common locations
$javaPaths = @(
    "C:\Program Files\Java\jdk-17*",
    "C:\Program Files\Java\jdk-21*",
    "C:\Program Files\Java\jdk-11*",
    "C:\Program Files\Eclipse Adoptium\jdk-17*",
    "C:\Program Files\Eclipse Adoptium\jdk-21*",
    "C:\Program Files (x86)\Java\jdk-17*",
    "C:\Program Files (x86)\Java\jdk-21*"
)

$foundJava = $null

foreach ($path in $javaPaths) {
    $dirs = Get-ChildItem $path -Directory -ErrorAction SilentlyContinue | Sort-Object Name -Descending
    if ($dirs) {
        $javaExe = Join-Path $dirs[0].FullName "bin\java.exe"
        if (Test-Path $javaExe) {
            $foundJava = $dirs[0].FullName
            Write-Host "Found Java at: $foundJava" -ForegroundColor Green
            break
        }
    }
}

if (-not $foundJava) {
    Write-Host "Could not find Java installation automatically." -ForegroundColor Red
    Write-Host "Please set JAVA_HOME manually:" -ForegroundColor Yellow
    Write-Host "1. Find your Java installation (usually in C:\Program Files\Java\)" -ForegroundColor Yellow
    Write-Host "2. Set environment variable JAVA_HOME to that path" -ForegroundColor Yellow
    Write-Host "3. Restart your terminal and try again" -ForegroundColor Yellow
    exit 1
}

# Set JAVA_HOME for this session
$env:JAVA_HOME = $foundJava
Write-Host "JAVA_HOME set to: $env:JAVA_HOME" -ForegroundColor Green

# Verify Java works
Write-Host "`nVerifying Java installation..." -ForegroundColor Yellow
& "$env:JAVA_HOME\bin\java.exe" -version

if ($LASTEXITCODE -eq 0) {
    Write-Host "`nJava is working! You can now run: npx expo run:android" -ForegroundColor Green
} else {
    Write-Host "`nJava verification failed. Please check your installation." -ForegroundColor Red
    exit 1
}
