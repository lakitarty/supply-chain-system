param([string]$Base = "http://localhost/supply-chain-system/Backend")

$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$stamp = Get-Date -Format "yyyyMMddHHmmss"
$email = "phase0-$stamp@example.com"
$password = "Test@1234"
$failures = 0

function Send-Request($Path, $Method = "Get", $Body = $null) {
    $params = @{ Uri = "$Base/$Path"; Method = $Method; WebSession = $session; UseBasicParsing = $true }

    if ($Body) {
        $params.Body = $Body | ConvertTo-Json
        $params.ContentType = "application/json"
    }

    try {
        $response = Invoke-WebRequest @params
        return @{ Status = [int]$response.StatusCode; Json = $response.Content | ConvertFrom-Json }
    } catch {
        $json = if ($_.ErrorDetails.Message) { $_.ErrorDetails.Message | ConvertFrom-Json } else { $null }
        return @{ Status = [int]$_.Exception.Response.StatusCode; Json = $json }
    }
}

function Assert-Check($Name, [bool]$Condition) {
    if ($Condition) {
        "PASS  $Name"
    } else {
        "FAIL  $Name"
        $script:failures++
    }
}

$registration = @{
    organizationName = "Phase0 Test $stamp"; organizationType = "RETAILER"; kraPin = ""
    organizationEmail = ""; organizationPhone = ""; organizationAddress = ""
    branchName = "Main Branch"; branchLocation = "Nairobi"
    firstName = "Test"; lastName = "User"; adminEmail = $email; password = $password
}
$credentials = @{ email = $email; password = $password }

$r = Send-Request "auth/session.php"
Assert-Check "session is empty before sign-in" ($r.Json.authenticated -eq $false)

$r = Send-Request "organizations/register.php" "Post" @{ organizationName = "" }
Assert-Check "registration rejects missing fields with 400 and a field name" ($r.Status -eq 400 -and $r.Json.field)

$r = Send-Request "organizations/register.php" "Post" $registration
Assert-Check "registration succeeds with 201" ($r.Status -eq 201 -and $r.Json.success -eq $true)
Assert-Check "registration returns the organization type" ($r.Json.data.organization.organization_type -eq "RETAILER")

$r = Send-Request "auth/session.php"
Assert-Check "registration signs the new admin in" ($r.Json.authenticated -eq $true -and $r.Json.user.role -eq "ADMIN")

$r = Send-Request "auth/logout.php"
Assert-Check "logout rejects GET with 405" ($r.Status -eq 405)

$r = Send-Request "auth/logout.php" "Post" @{ confirm = $true }
Assert-Check "logout succeeds" ($r.Status -eq 200)

$r = Send-Request "auth/session.php"
Assert-Check "session is empty after logout" ($r.Json.authenticated -eq $false)

$r = Send-Request "auth/login.php" "Post" @{ email = $email; password = "Wrong@9999" }
Assert-Check "wrong password is rejected with 401" ($r.Status -eq 401)

$r = Send-Request "auth/login.php" "Post" $credentials
Assert-Check "login succeeds after registration" ($r.Status -eq 200 -and $r.Json.success -eq $true)

$r = Send-Request "auth/session.php"
Assert-Check "session is active after login" ($r.Json.authenticated -eq $true -and $r.Json.user.email -eq $email)

$r = Send-Request "organizations/register.php" "Post" $registration
Assert-Check "duplicate email is rejected with 409 on adminEmail" ($r.Status -eq 409 -and $r.Json.field -eq "adminEmail")

$r = Send-Request "auth/logout.php" "Post" @{ confirm = $true }
$r = Send-Request "auth/login.php" "Post" $credentials
Assert-Check "login works again after a second sign-out" ($r.Status -eq 200)

$r = Send-Request "auth/logout.php" "Post" @{ confirm = $true }

if ($failures -eq 0) {
    "All checks passed."
} else {
    "$failures check(s) failed."
}