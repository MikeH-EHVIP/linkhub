$wrongDir = "includes\Rendering"
$scriptsDir = "scripts"

# Ensure scripts directory exists
if (-not (Test-Path $scriptsDir)) {
    New-Item -ItemType Directory -Path $scriptsDir | Out-Null
}

# Move files if they exist in the wrong place
if (Test-Path "$wrongDir\.distignore") {
    Move-Item -Path "$wrongDir\.distignore" -Destination ".\.distignore" -Force
    Write-Host "Moved .distignore to root"
}
if (Test-Path "$wrongDir\build-plugin-zip.ps1") {
    Move-Item -Path "$wrongDir\build-plugin-zip.ps1" -Destination "$scriptsDir\build-plugin-zip.ps1" -Force
    Write-Host "Moved build-plugin-zip.ps1 to scripts folder"
}
if (Test-Path "$wrongDir\build-plugin-zip.sh") {
    Move-Item -Path "$wrongDir\build-plugin-zip.sh" -Destination "$scriptsDir\build-plugin-zip.sh" -Force
    Write-Host "Moved build-plugin-zip.sh to scripts folder"
}
