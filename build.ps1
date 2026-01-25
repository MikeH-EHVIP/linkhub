# LinkHub - WordPress Plugin Build Script
# Creates a clean distribution-ready ZIP file

param(
    [string]$Version = "0.2.0",
    [string]$OutputDir = "dist"
)

$ErrorActionPreference = "Stop"

Write-Host "LinkHub Build Script" -ForegroundColor Cyan
Write-Host "===================" -ForegroundColor Cyan
Write-Host ""

# Get plugin directory
$pluginDir = $PSScriptRoot
$pluginName = "linkhub"
$buildDir = Join-Path $pluginDir $OutputDir
$tempDir = Join-Path $buildDir "temp"
$zipPath = Join-Path $buildDir "$pluginName-$Version.zip"

# Create build directory
if (-not (Test-Path $buildDir)) {
    New-Item -ItemType Directory -Path $buildDir | Out-Null
}

# Clean up old builds
if (Test-Path $tempDir) {
    Remove-Item -Recurse -Force $tempDir
}
if (Test-Path $zipPath) {
    Remove-Item -Force $zipPath
}

Write-Host "Creating temp directory..." -ForegroundColor Gray
New-Item -ItemType Directory -Path $tempDir | Out-Null
$pluginTempDir = Join-Path $tempDir $pluginName
New-Item -ItemType Directory -Path $pluginTempDir | Out-Null

# Read .distignore patterns
$distignorePath = Join-Path $pluginDir ".distignore"
$ignorePatterns = @()
if (Test-Path $distignorePath) {
    $ignorePatterns = Get-Content $distignorePath | Where-Object {
        $_.Trim() -ne "" -and -not $_.StartsWith("#")
    }
}

Write-Host "Copying plugin files..." -ForegroundColor Gray

# Get all files (filter out Windows reserved names)
$reservedNames = @('nul', 'con', 'aux', 'prn', 'com1', 'com2', 'com3', 'com4', 'lpt1', 'lpt2', 'lpt3', 'lpt4')
$allFiles = Get-ChildItem -Path $pluginDir -Recurse -File | Where-Object {
    $reservedNames -notcontains $_.BaseName.ToLower()
}

$copiedCount = 0
foreach ($file in $allFiles) {
    $relativePath = $file.FullName.Substring($pluginDir.Length + 1)
    
    # Check if file should be ignored
    $shouldIgnore = $false
    foreach ($pattern in $ignorePatterns) {
        # Simple pattern matching
        if ($relativePath -like "*$pattern*" -or $relativePath -eq $pattern) {
            $shouldIgnore = $true
            break
        }
        # Check if path starts with pattern (for directories)
        if ($relativePath.StartsWith($pattern + "\") -or $relativePath.StartsWith($pattern + "/")) {
            $shouldIgnore = $true
            break
        }
    }
    
    # Skip dist directory itself
    if ($relativePath.StartsWith("dist\") -or $relativePath.StartsWith("dist/")) {
        $shouldIgnore = $true
    }
    
    if (-not $shouldIgnore) {
        $targetPath = Join-Path $pluginTempDir $relativePath
        $targetDir = Split-Path -Parent $targetPath
        
        if (-not (Test-Path $targetDir)) {
            New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
        }
        
        Copy-Item -Path $file.FullName -Destination $targetPath -Force
        $copiedCount++
    }
}

Write-Host "Copied $copiedCount files" -ForegroundColor Green
Write-Host ""

# Create ZIP file
Write-Host "Creating ZIP archive..." -ForegroundColor Gray

# Use .NET compression to avoid file locking issues
Add-Type -AssemblyName System.IO.Compression.FileSystem
try {
    [System.IO.Compression.ZipFile]::CreateFromDirectory($pluginTempDir, $zipPath, 'Optimal', $false)
    Write-Host "Archive created successfully" -ForegroundColor Green
} catch {
    Write-Host "Error creating archive: $_" -ForegroundColor Red
    # Clean up temp directory
    Remove-Item -Recurse -Force $tempDir
    exit 1
}

# Get file size
$zipSize = (Get-Item $zipPath).Length
$zipSizeMB = [math]::Round($zipSize / 1MB, 2)

Write-Host ""
Write-Host "===================" -ForegroundColor Cyan
Write-Host "Build Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Output: $zipPath" -ForegroundColor Yellow
Write-Host "Size: $zipSizeMB MB" -ForegroundColor Yellow
Write-Host ""

# Copy to local WordPress test site
$testSitePluginDir = "E:\laragon\www\WordPress-Test-Site\wp-content\plugins\linkhub"
Write-Host "Copying to test site..." -ForegroundColor Gray

if (Test-Path $testSitePluginDir) {
    Remove-Item -Recurse -Force $testSitePluginDir
}

Copy-Item -Path $pluginTempDir -Destination $testSitePluginDir -Recurse -Force
Write-Host "Deployed to: $testSitePluginDir" -ForegroundColor Green
Write-Host ""

# Clean up temp directory
Remove-Item -Recurse -Force $tempDir

Write-Host "Ready to test locally or upload remotely!" -ForegroundColor Cyan
