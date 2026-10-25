$ErrorActionPreference = 'Stop'

$root = 'C:\xampp\htdocs\supply-chain-system'
$apiBase = 'http://localhost/supply-chain-system/Backend/'
$mysql = 'C:\xampp\mysql\bin\mysql.exe'
$php = 'C:\xampp\php\php.exe'
$database = 'supplychainsystem'
$manufacturerName = 'Mill Bakers'
$supplierName = 'Fratres Limited Kitui'
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

function Invoke-Sql([string]$query) {
    $output = & $mysql -u root $database -N -B -e $query
    if ($LASTEXITCODE -ne 0) { throw "SQL failed: $query" }
    $lines = @($output | Where-Object { "$_" -ne '' })
    return ,$lines
}

function Get-Counts {
    $appointments = Invoke-Sql 'SELECT COUNT(*) FROM product_suppliers'
    $coverage = Invoke-Sql 'SELECT COUNT(*) FROM supplier_distribution_areas'
    return [pscustomobject]@{ Appointments = [int]$appointments[0]; Coverage = [int]$coverage[0] }
}

function Send-Json([string]$path, $payload) {
    $json = $payload | ConvertTo-Json -Compress
    try {
        $response = Invoke-WebRequest -Uri ($apiBase + $path) -Method Post -Body $json -ContentType 'application/json' -WebSession $script:session -UseBasicParsing
        return [pscustomobject]@{ Status = [int]$response.StatusCode; Body = $response.Content }
    } catch {
        $failed = $_.Exception.Response
        if (-not $failed) { throw }
        $text = $_.ErrorDetails.Message
        if (-not $text -and ($failed.PSObject.Methods.Name -contains 'GetResponseStream')) {
            $reader = New-Object System.IO.StreamReader($failed.GetResponseStream())
            $text = $reader.ReadToEnd()
        }
        return [pscustomobject]@{ Status = [int]$failed.StatusCode; Body = $text }
    }
}

function Read-Json([string]$text) {
    try { return $text | ConvertFrom-Json } catch { return $null }
}

Write-Host 'Step 1: static checks'
& $php -l "$root\Backend\network\distribution-save.php"
if ($LASTEXITCODE -ne 0) { throw 'PHP syntax check failed.' }
node --check "$root\frontend\modules\network\distribution\distribution.js"
if ($LASTEXITCODE -ne 0) { throw 'JavaScript syntax check failed.' }
$leftover = Select-String -Path "$root\Backend\network\*.php", "$root\frontend\modules\network\distribution\*.js", "$root\frontend\modules\network\distribution\*.html" -Pattern 'confirmMissingCoverage|allowMissingCoverage|coverageWarning|COVERAGE_WARNING|acknowledged'
if ($leftover) {
    $leftover | ForEach-Object { '{0}:{1}: {2}' -f $_.Path, $_.LineNumber, $_.Line.Trim() }
    throw 'Leftover override references found.'
}
Write-Host 'Syntax checks passed and no override references remain.'

Write-Host 'Step 2: inspecting the login'
$loginPath = "$root\Backend\auth\login.php"
$tokenFiles = @($loginPath, "$root\Backend\core\bootstrap.php", "$root\Backend\core\guard.php", "$root\frontend\shared\js\api.js")
$tokenHits = Select-String -Path $tokenFiles -Pattern 'csrf|xsrf|x-requested-with'
if ($tokenHits) {
    $tokenHits | ForEach-Object { '{0}:{1}: {2}' -f $_.Path, $_.LineNumber, $_.Line.Trim() }
    throw 'The code may require a CSRF token or custom header. Send these lines to Claude; the script cannot handle this yet.'
}
$loginText = Get-Content $loginPath -Raw
if ($loginText -notmatch 'email' -or $loginText -notmatch 'password') {
    throw 'login.php does not mention email and password. Send the file to Claude.'
}
$identifierKey = 'email'
$passwordKey = 'password'
Write-Host 'Login will send email and password as JSON. No token requirement found.'

Write-Host 'Step 3: choosing a test case from the database'
$manufacturer = Invoke-Sql "SELECT organization_id FROM organizations WHERE organization_name = '$manufacturerName' AND status = 'ACTIVE' LIMIT 1"
$supplier = Invoke-Sql "SELECT organization_id FROM organizations WHERE organization_name = '$supplierName' AND status = 'ACTIVE' LIMIT 1"
if (-not $manufacturer -or -not $supplier) { throw 'Manufacturer or supplier not found.' }
$manufacturerId = [int]$manufacturer[0]
$supplierId = [int]$supplier[0]

$relationship = Invoke-Sql "SELECT COUNT(*) FROM organization_relationships r JOIN organizations s ON s.organization_id = r.target_organization_id WHERE r.source_organization_id = $manufacturerId AND r.target_organization_id = $supplierId AND r.status = 'ACTIVE' AND s.status = 'ACTIVE' AND s.organization_type IN ('SUPPLIER','MANUFACTURER_SUPPLIER')"
if ([int]$relationship[0] -ne 1) { throw "Relationship check returned '$($relationship -join '|')' for manufacturer $manufacturerId and supplier $supplierId." }

$product = Invoke-Sql "SELECT product_id, product_name FROM products WHERE manufacturer_id = $manufacturerId AND status = 'ACTIVE' ORDER BY product_id LIMIT 1"
if (-not $product) { throw 'No active product found.' }
$productId = [int]($product[0] -split "`t")[0]
$productName = ($product[0] -split "`t")[1]

$area = Invoke-Sql "SELECT a.area_id, a.area_name FROM distribution_areas a WHERE a.status = 'ACTIVE' AND NOT EXISTS (SELECT 1 FROM supplier_distribution_areas s WHERE s.supplier_id = $supplierId AND s.area_id = a.area_id AND s.status = 'ACTIVE') ORDER BY a.area_id LIMIT 1"
if (-not $area) { throw 'Every active county is covered, so there is no uncovered test case.' }
$areaId = [int]($area[0] -split "`t")[0]
$areaName = ($area[0] -split "`t")[1]

$today = Get-Date -Format 'yyyy-MM-dd'
$laterDate = (Get-Date).AddDays(30).ToString('yyyy-MM-dd')

$openCovered = Invoke-Sql "SELECT COUNT(*) FROM supplier_distribution_areas WHERE supplier_id = $supplierId AND area_id = $areaId AND status = 'ACTIVE' AND start_date <= '$today' AND end_date IS NULL"
$datedCovered = Invoke-Sql "SELECT COUNT(*) FROM supplier_distribution_areas WHERE supplier_id = $supplierId AND area_id = $areaId AND status = 'ACTIVE' AND start_date <= '$today' AND (end_date IS NULL OR end_date >= '$laterDate')"
if ([int]$openCovered[0] -ne 0 -or [int]$datedCovered[0] -ne 0) { throw 'The chosen county turned out to be covered. Aborting.' }

Write-Host "Test case: $productName (id $productId), $supplierName (id $supplierId), $areaName (id $areaId), start $today. Verified uncovered."

$before = Get-Counts
Write-Host "Counts before: $($before.Appointments) appointments, $($before.Coverage) coverage records (earlier snapshot was 32 and 9)."

$answer = Read-Host 'Type YES to send three requests that should all be rejected with 409'
if ($answer -ne 'YES') { throw 'Cancelled. Nothing was sent.' }

Write-Host 'Step 4: login'
$identifier = Read-Host 'Mill Bakers administrator email'
$secure = Read-Host 'Password' -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try { $plain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
$login = Send-Json 'auth/login.php' @{ $identifierKey = $identifier; $passwordKey = $plain }
$plain = $null
$secure = $null
$loginBody = Read-Json $login.Body
if ($login.Status -ne 200 -or -not $loginBody -or $loginBody.success -ne $true) {
    throw "Login did not succeed (HTTP $($login.Status)). No appointment request was sent."
}
Write-Host 'Logged in.'

Write-Host 'Step 5: bypass attempts'
$attempts = @(
    @{ Name = 'open-ended, confirmMissingCoverage true'; EndDate = ''; Flag = $true },
    @{ Name = 'dated, confirmMissingCoverage true'; EndDate = $laterDate; Flag = $true },
    @{ Name = 'open-ended, no flag'; EndDate = ''; Flag = $null }
)
$allRejected = $true
foreach ($attempt in $attempts) {
    $payload = @{ productId = $productId; supplierId = $supplierId; areaId = $areaId; startDate = $today; endDate = $attempt.EndDate; isExclusive = $false }
    if ($null -ne $attempt.Flag) { $payload.confirmMissingCoverage = $attempt.Flag }
    $result = Send-Json 'network/distribution-save.php' $payload
    $parsed = Read-Json $result.Body
    $rejected = ($result.Status -eq 409 -and $parsed -and $parsed.code -eq 'SUPPLIER_COVERAGE_REQUIRED')
    $now = Get-Counts
    $unchanged = ($now.Appointments -eq $before.Appointments -and $now.Coverage -eq $before.Coverage)
    Write-Host ("{0}: HTTP {1}, code {2}, rejected: {3}, counts unchanged: {4}" -f $attempt.Name, $result.Status, $parsed.code, $rejected, $unchanged)
    if (-not $rejected -or -not $unchanged) {
        $allRejected = $false
        Write-Host "Response body: $($result.Body)"
        if (-not $unchanged) {
            Invoke-Sql 'SELECT product_supplier_id, product_id, supplier_id, area_id, start_date, end_date, status FROM product_suppliers ORDER BY product_supplier_id DESC LIMIT 1'
            Write-Host 'A record was written. End it with the Distribution page, which keeps the history. Nothing was deleted.'
        }
        break
    }
}

$after = Get-Counts
Write-Host "Counts after: $($after.Appointments) appointments, $($after.Coverage) coverage records."
if ($allRejected -and $after.Appointments -eq $before.Appointments -and $after.Coverage -eq $before.Coverage) {
    Write-Host 'RESULT: PASS. Uncovered requests were rejected with 409 and no records changed.'
} else {
    Write-Host 'RESULT: FAIL or INCONCLUSIVE. Send this output to Claude.'
    exit 1
}