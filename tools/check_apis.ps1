$outFile = "api_check_results.txt"
if (Test-Path $outFile) { Remove-Item $outFile }
Add-Content $outFile "API check run: $(Get-Date)"
Add-Content $outFile "Working dir: $(Get-Location)"

$apiDir = Join-Path (Get-Location) 'api'
$files = Get-ChildItem -Path $apiDir -Filter '*.php' | Sort-Object Name

foreach ($f in $files) {
    $endpoint = "http://localhost:8000/api/$($f.Name)"
    Add-Content $outFile "\n=== $($f.Name) ==="
    Add-Content $outFile "URL: $endpoint"

    # GET
    try {
        $r = Invoke-WebRequest -Uri $endpoint -Method GET -UseBasicParsing -TimeoutSec 10 -ErrorAction Stop
        Add-Content $outFile "GET: Status=$($r.StatusCode) Length=$($r.RawContentLength)"
        $snippet = $r.Content -replace "\r|\n"," "
        if ($snippet.Length -gt 1000) { $snippet = $snippet.Substring(0,1000) + '...[truncated]' }
        Add-Content $outFile "GET Content Snippet: $snippet"
    } catch {
        Add-Content $outFile "GET ERROR: $($_.Exception.Message)"
    }

    # POST (form)
    try {
        $r = Invoke-WebRequest -Uri $endpoint -Method POST -Body @{test='1'} -UseBasicParsing -TimeoutSec 10 -ErrorAction Stop
        Add-Content $outFile "POST: Status=$($r.StatusCode) Length=$($r.RawContentLength)"
        $snippet = $r.Content -replace "\r|\n"," "
        if ($snippet.Length -gt 1000) { $snippet = $snippet.Substring(0,1000) + '...[truncated]' }
        Add-Content $outFile "POST Content Snippet: $snippet"
    } catch {
        Add-Content $outFile "POST ERROR: $($_.Exception.Message)"
    }

    # Small pause to avoid spamming
    Start-Sleep -Milliseconds 200
}

Add-Content $outFile "\nCompleted: $(Get-Date)"
Write-Output "Wrote results to $outFile"