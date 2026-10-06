# Laedt die Webseite Muehle Prautitz in das GitHub-Repository hoch und aktiviert GitHub Pages.
$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$here  = Split-Path -Parent $MyInvocation.MyCommand.Path
$repo  = 'MasterBecksen/muehle-prautitz'
$api   = "https://api.github.com/repos/$repo"
$token = (Get-Content -Raw (Join-Path $here 'token.txt')).Trim()
$h = @{ Authorization = "Bearer $token"; Accept = 'application/vnd.github+json'; 'X-GitHub-Api-Version' = '2022-11-28'; 'User-Agent' = 'muehle-upload' }

function Gh($method, $url, $body) {
  if ($body -ne $null) {
    $json  = $body | ConvertTo-Json -Depth 20 -Compress
    $bytes = [Text.Encoding]::UTF8.GetBytes($json)
    return Invoke-RestMethod -Method $method -Uri $url -Headers $h -Body $bytes -ContentType 'application/json; charset=utf-8'
  }
  return Invoke-RestMethod -Method $method -Uri $url -Headers $h
}

Write-Host "Entpacke Projekt ..."
$src = Join-Path $env:TEMP ('muehle-' + [guid]::NewGuid().ToString('N'))
Expand-Archive -Path (Join-Path $here 'site.zip') -DestinationPath $src

Write-Host "Erster Commit (Repository ist leer) ..."
$init = Gh 'PUT' "$api/contents/.keep" @{ message = 'Init'; content = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes('init')); branch = 'main' }
$parent = $init.commit.sha

Write-Host "GitHub Pages aktivieren ..."
try { Gh 'POST' "$api/pages" @{ build_type = 'workflow' } | Out-Null } catch { Write-Host "  (Pages: $($_.Exception.Message))" }

$files = Get-ChildItem -Path $src -Recurse -File -Force
$tree = @()
$i = 0
foreach ($f in $files) {
  $i++
  $rel = $f.FullName.Substring($src.Length + 1).Replace('\', '/')
  Write-Host ("[{0}/{1}] {2}" -f $i, $files.Count, $rel)
  $blob = Gh 'POST' "$api/git/blobs" @{ content = [Convert]::ToBase64String([IO.File]::ReadAllBytes($f.FullName)); encoding = 'base64' }
  $tree += @{ path = $rel; mode = '100644'; type = 'blob'; sha = $blob.sha }
}

Write-Host "Commit erstellen ..."
$t = Gh 'POST' "$api/git/trees" @{ tree = $tree }
$c = Gh 'POST' "$api/git/commits" @{ message = 'Neue Webseite Muehle Prautitz mit Redaktionssystem'; tree = $t.sha; parents = @($parent) }
Gh 'PATCH' "$api/git/refs/heads/main" @{ sha = $c.sha; force = $true } | Out-Null

Start-Sleep -Seconds 5
Write-Host "Bild-Import starten ..."
try { Gh 'POST' "$api/actions/workflows/import-images.yml/dispatches" @{ ref = 'main' } | Out-Null } catch { Write-Host "  (Import: $($_.Exception.Message))" }

Remove-Item -Recurse -Force $src
Remove-Item -Force (Join-Path $here 'token.txt')
Write-Host ""
Write-Host "FERTIG. Vorschau in wenigen Minuten: https://masterbecksen.github.io/muehle-prautitz/" -ForegroundColor Green
Set-Content -Path (Join-Path $here 'ERGEBNIS.txt') -Value "OK $($c.sha)"
