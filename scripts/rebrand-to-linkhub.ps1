# LinkHub Rebranding Script
param(
    [switch]$DryRun = $false
)

$ErrorActionPreference = "Stop"

if ($PSScriptRoot) {
    $rootPath = Split-Path -Parent $PSScriptRoot
} else {
    $rootPath = "E:\ElyseVIPSTuff\linkhub"
}

Write-Host "LinkHub Rebranding Script" -ForegroundColor Cyan
Write-Host "Root Path: $rootPath" -ForegroundColor Gray
if ($DryRun) {
    Write-Host "DRY RUN MODE" -ForegroundColor Yellow
}
Write-Host ""

$extensions = @("*.php", "*.js", "*.css", "*.json", "*.md", "*.txt")
$excludeDirs = @("node_modules", "vendor", ".git", "visual-builder", "src")

$files = @()
foreach ($ext in $extensions) {
    $found = Get-ChildItem -Path $rootPath -Filter $ext -Recurse -File | Where-Object {
        $exclude = $false
        foreach ($dir in $excludeDirs) {
            if ($_.FullName -like "*\$dir\*") {
                $exclude = $true
                break
            }
        }
        -not $exclude
    }
    $files += $found
}

Write-Host "Found $($files.Count) files to process" -ForegroundColor Green
Write-Host ""

$replacements = @(
    @{
        Find = 'ElyseVIP\\DiviTreeOfLinks'
        Replace = 'ElyseVIP\\LinkHub'
        Description = 'Namespace (backslash)'
    },
    @{
        Find = 'ElyseVIP\DiviTreeOfLinks'
        Replace = 'ElyseVIP\LinkHub'
        Description = 'Namespace (forward slash)'
    },
    @{
        Find = 'DTOL_'
        Replace = 'LH_'
        Description = 'PHP Constants'
    },
    @{
        Find = 'dtol_'
        Replace = 'lh_'
        Description = 'Function/Hook Prefixes'
    },
    @{
        Find = '_dtol_'
        Replace = '_lh_'
        Description = 'Meta Keys'
    },
    @{
        Find = '.dtol-'
        Replace = '.lh-'
        Description = 'CSS Classes'
    },
    @{
        Find = 'divi-tree-of-links'
        Replace = 'linkhub'
        Description = 'Slug/Identifier'
    },
    @{
        Find = 'Divi Tree of Links'
        Replace = 'LinkHub'
        Description = 'Plugin Name'
    }
)

$totalReplacements = 0

foreach ($replacement in $replacements) {
    Write-Host "Processing: $($replacement.Description)" -ForegroundColor Cyan
    Write-Host "  Find: $($replacement.Find)" -ForegroundColor Gray
    Write-Host "  Replace: $($replacement.Replace)" -ForegroundColor Gray
    
    $count = 0
    
    foreach ($file in $files) {
        $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
        
        if ($content -match [regex]::Escape($replacement.Find)) {
            $count++
            
            if (-not $DryRun) {
                $newContent = $content -replace [regex]::Escape($replacement.Find), $replacement.Replace
                Set-Content -Path $file.FullName -Value $newContent -Encoding UTF8 -NoNewline
            }
            
            $relativePath = $file.FullName.Replace($rootPath, "").TrimStart('\')
            Write-Host "    OK $relativePath" -ForegroundColor Green
        }
    }
    
    if ($count -eq 0) {
        Write-Host "    No matches found" -ForegroundColor DarkGray
    } else {
        Write-Host "    Changed $count files" -ForegroundColor Yellow
        $totalReplacements += $count
    }
    
    Write-Host ""
}

Write-Host "Complete!" -ForegroundColor Green
Write-Host "Total files modified: $totalReplacements" -ForegroundColor Yellow

if ($DryRun) {
    Write-Host ""
    Write-Host "DRY RUN - No changes made. Run without -DryRun to apply." -ForegroundColor Yellow
}
