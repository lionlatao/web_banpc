$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
if (-not (Get-Command git -ErrorAction SilentlyContinue)) { throw 'Git chua duoc cai. Cai Git for Windows truoc.' }
if (-not (Test-Path .git)) { git init; git branch -M main }
git add .
git commit -m "Upload LionPC v3 Laravel project"
if ($LASTEXITCODE -ne 0) { Write-Host 'Neu Git bao nothing to commit, co the tiep tuc push.' }
git remote remove origin 2>$null
$global:LASTEXITCODE = 0
git remote add origin https://github.com/lionlatao/web_banpc.git
git push -u origin main
if ($LASTEXITCODE -ne 0) { throw 'Push that bai. Kiem tra dang nhap GitHub va quyen ghi.' }
Write-Host 'Da upload: https://github.com/lionlatao/web_banpc'
