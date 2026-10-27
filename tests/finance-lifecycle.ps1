$ErrorActionPreference = 'Stop'

$root = 'C:\xampp\htdocs\supply-chain-system'
$phpExe = 'C:\xampp\php\php.exe'
$apiBase = 'http://localhost/supply-chain-system/Backend/'
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$stamp = Get-Date -Format 'yyyyMMddHHmmss'
$failures = 0

function Send-Request([string]$path, [string]$method = 'Get', $payload = $null) {
    $arguments = @{ Uri = ($apiBase + $path); Method = $method; WebSession = $script:session; UseBasicParsing = $true }
    if ($null -ne $payload) {
        $arguments.Body = ($payload | ConvertTo-Json -Depth 6 -Compress)
        $arguments.ContentType = 'application/json'
    }
    try {
        $response = Invoke-WebRequest @arguments
        return [pscustomobject]@{ Status = [int]$response.StatusCode; Data = ($response.Content | ConvertFrom-Json) }
    } catch {
        $failed = $_.Exception.Response
        if (-not $failed) { throw }
        $data = $null
        try { $data = $_.ErrorDetails.Message | ConvertFrom-Json } catch { }
        return [pscustomobject]@{ Status = [int]$failed.StatusCode; Data = $data }
    }
}

function Assert-That([string]$name, [bool]$condition) {
    if ($condition) {
        Write-Host "PASS  $name" -ForegroundColor Green
    } else {
        Write-Host "FAIL  $name" -ForegroundColor Red
        $script:failures++
    }
}

function Get-Balance([string]$code) {
    $accounts = (Send-Request 'finance/accounts-list.php').Data.accounts
    return [decimal](($accounts | Where-Object { $_.account_code -eq $code }).balance)
}

function New-Draft([string]$suffix, [int]$counterpartyId, [string]$issueDate, [string]$dueDate) {
    return Send-Request 'finance/document-save.php' 'Post' @{
        document_type = 'INVOICE'
        counterparty_organization_id = $counterpartyId
        document_number = "TST-$stamp-$suffix"
        issue_date = $issueDate
        due_date = $dueDate
        notes = 'Automated test'
        lines = @(@{ description = 'Test item'; quantity = 2; unit_price = 100; tax_rate = 16 })
    }
}

Write-Host 'Step 1: syntax checks'
foreach ($name in 'document-delete.php', 'document-void.php', 'payment-cancel.php', 'documents-list.php', 'helpers.php') {
    $result = & $phpExe -l "$root\Backend\finance\$name" 2>&1
    Write-Host $result
    if ($LASTEXITCODE -ne 0) { throw "Syntax check failed for $name." }
}

$answer = Read-Host "Type YES to create test records tagged TST-$stamp in your database"
if ($answer -ne 'YES') { throw 'Cancelled. Nothing was sent.' }

Write-Host 'Step 2: login'
$email = Read-Host 'Administrator email'
$secure = Read-Host 'Password' -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try { $plain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
$login = Send-Request 'auth/login.php' 'Post' @{ email = $email; password = $plain }
$plain = $null
if ($login.Status -ne 200 -or $login.Data.success -ne $true) { throw "Login failed (HTTP $($login.Status)): $($login.Data.error)" }

$counterparties = (Send-Request 'finance/counterparties-list.php').Data.counterparties
if (-not $counterparties) { throw 'This organisation has no active supply relationship to test with.' }
$counterpartyId = [int]$counterparties[0].organization_id
Write-Host "Using counterparty $($counterparties[0].organization_name)"

$today = Get-Date -Format 'yyyy-MM-dd'

Write-Host 'Step 3: draft delete'
$draft = New-Draft 'A' $counterpartyId $today ''
Assert-That 'draft saved with subtotal 200, tax 32, total 232' ($draft.Status -eq 201 -and [decimal]$draft.Data.subtotal -eq 200 -and [decimal]$draft.Data.tax_total -eq 32 -and [decimal]$draft.Data.total_amount -eq 232)
$deleted = Send-Request 'finance/document-delete.php' 'Post' @{ document_id = [int]$draft.Data.document_id }
Assert-That 'draft deleted' ($deleted.Status -eq 200)
$remaining = (Send-Request 'finance/documents-list.php?status=DRAFT').Data.documents | Where-Object { $_.document_number -eq "TST-$stamp-A" }
Assert-That 'deleted draft no longer listed' (-not $remaining)

Write-Host 'Step 4: issue then void'
$receivableBefore = Get-Balance '1100'
$revenueBefore = Get-Balance '4000'
$taxBefore = Get-Balance '2100'
$invoice = New-Draft 'B' $counterpartyId $today ''
$invoiceId = [int]$invoice.Data.document_id
$issued = Send-Request 'finance/document-issue.php' 'Post' @{ document_id = $invoiceId }
Assert-That 'invoice issued' ($issued.Status -eq 200)
Assert-That 'receivable up 232, revenue up 200, tax payable up 32' ((Get-Balance '1100') -eq $receivableBefore + 232 -and (Get-Balance '4000') -eq $revenueBefore + 200 -and (Get-Balance '2100') -eq $taxBefore + 32)
$short = Send-Request 'finance/document-void.php' 'Post' @{ document_id = $invoiceId; reason = 'x' }
Assert-That 'void without a proper reason refused (400)' ($short.Status -eq 400)
$voided = Send-Request 'finance/document-void.php' 'Post' @{ document_id = $invoiceId; reason = 'Automated test void' }
Assert-That 'invoice voided' ($voided.Status -eq 200)
Assert-That 'all three balances returned exactly' ((Get-Balance '1100') -eq $receivableBefore -and (Get-Balance '4000') -eq $revenueBefore -and (Get-Balance '2100') -eq $taxBefore)
$again = Send-Request 'finance/document-void.php' 'Post' @{ document_id = $invoiceId; reason = 'Second attempt' }
Assert-That 'second void refused (409)' ($again.Status -eq 409)
$voidList = (Send-Request 'finance/documents-list.php?status=VOID').Data.documents | Where-Object { $_.document_number -eq "TST-$stamp-B" }
Assert-That 'voided invoice appears under the VOID filter' ([bool]$voidList)
$deleteIssued = Send-Request 'finance/document-delete.php' 'Post' @{ document_id = $invoiceId }
Assert-That 'deleting a non-draft refused (409)' ($deleteIssued.Status -eq 409)

Write-Host 'Step 5: overdue filter'
$pastIssue = (Get-Date).AddDays(-10).ToString('yyyy-MM-dd')
$pastDue = (Get-Date).AddDays(-3).ToString('yyyy-MM-dd')
$overdue = New-Draft 'C' $counterpartyId $pastIssue $pastDue
if ($overdue.Status -eq 201) {
    $overdueId = [int]$overdue.Data.document_id
    [void](Send-Request 'finance/document-issue.php' 'Post' @{ document_id = $overdueId })
    $overdueList = (Send-Request 'finance/documents-list.php?status=OVERDUE').Data.documents | Where-Object { $_.document_number -eq "TST-$stamp-C" }
    Assert-That 'unpaid past-due invoice appears under the OVERDUE filter' ([bool]$overdueList)
    [void](Send-Request 'finance/document-void.php' 'Post' @{ document_id = $overdueId; reason = 'Automated test cleanup' })
} else {
    Write-Host 'SKIP  overdue filter (the supply relationship started after the test issue date)' -ForegroundColor Yellow
}

Write-Host 'Step 6: payment cancel'
$reference = "TST-$stamp"
$recorded = Send-Request 'finance/payment-record.php' 'Post' @{ payee_organization_id = $counterpartyId; amount = 50; payment_method = 'CASH'; reference_number = $reference; payment_date = $today }
Assert-That 'payment recorded' ($recorded.Status -eq 201 -or $recorded.Status -eq 200)
$pending = (Send-Request 'finance/payments-list.php?status=PENDING').Data.payments | Where-Object { $_.reference_number -eq $reference }
Assert-That 'payment is pending' ([bool]$pending)
$paymentId = [int]$pending.payment_id
$noReason = Send-Request 'finance/payment-cancel.php' 'Post' @{ payment_id = $paymentId; reason = '' }
Assert-That 'cancel without a reason refused (400)' ($noReason.Status -eq 400)
$cancelled = Send-Request 'finance/payment-cancel.php' 'Post' @{ payment_id = $paymentId; reason = 'Automated test cancel' }
Assert-That 'payer cancel succeeds with status CANCELLED' ($cancelled.Status -eq 200 -and $cancelled.Data.status -eq 'CANCELLED')
$stored = (Send-Request 'finance/payments-list.php?status=CANCELLED').Data.payments | Where-Object { $_.payment_id -eq $paymentId }
Assert-That 'cancelled payment listed with the reason in its notes' ([bool]$stored -and "$($stored.notes)" -like '*Automated test cancel*')
$cancelAgain = Send-Request 'finance/payment-cancel.php' 'Post' @{ payment_id = $paymentId; reason = 'Second attempt' }
Assert-That 'cancelling again refused (409)' ($cancelAgain.Status -eq 409)

Write-Host ''
if ($failures -eq 0) {
    Write-Host 'All checks passed.' -ForegroundColor Green
} else {
    Write-Host "$failures check(s) failed. Paste the FAIL lines here." -ForegroundColor Red
}