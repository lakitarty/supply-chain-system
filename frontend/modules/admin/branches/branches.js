<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../shared/css/base.css">
    <link rel="stylesheet" href="../../../shared/css/shell.css">
    <link rel="stylesheet" href="organisation-profile.css">
    <title>Organisation profile | Nexora MSRC</title>
</head>
<body>
    <div id="shell"></div>

    <main class="page organisation-profile-page">
        <section class="organisation-branding">
            <div class="organisation-logo-preview" id="organizationLogoPreview">
                <img id="logoPreview" alt="Organisation logo" hidden>
                <span id="logoFallback">?</span>
            </div>

            <div class="organisation-branding-content">
                <div class="organisation-branding-heading">
                    <h2>Organisation logo</h2>
                </div>

                <p class="organisation-branding-hint">PNG, JPEG or WebP, maximum 2 MB</p>

                <input type="file" id="logoFile" accept="image/png,image/jpeg,image/webp" hidden>

                <div class="organisation-logo-actions">
                    <button type="button" class="secondary-button" id="chooseLogoButton">Choose image</button>
                    <button type="button" class="primary-button" id="uploadLogoButton" disabled>Upload logo</button>
                    <button type="button" class="secondary-button" id="removeLogoButton" hidden>Remove logo</button>
                </div>

                <div id="logoMessage" class="form-message" role="status" aria-live="polite"></div>
            </div>
        </section>

        <section class="organisation-details-section">
            <div class="organisation-details-heading">
                <h2>Organisation details</h2>
                <button type="button" class="secondary-button" id="editProfileButton">Edit details</button>
            </div>

            <dl id="organizationSummary" class="organisation-details" aria-live="polite"></dl>
        </section>

        <div id="formMessage" class="form-message" role="alert" aria-live="polite"></div>

        <div id="profileModal" class="profile-modal-backdrop" hidden>
            <section class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle" tabindex="-1">
                <header class="profile-modal-header">
                    <h2 id="profileModalTitle">Edit organisation details</h2>
                    <button type="button" class="secondary-button" id="closeProfileModal">Close</button>
                </header>

                <form id="profileForm" class="form-grid profile-form" novalidate>
                    <div class="form-group">
                        <label for="organizationName">Organisation name</label>
                        <input type="text" id="organizationName" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label for="organizationType">Organisation type</label>
                        <input type="text" id="organizationType" disabled>
                    </div>

                    <div class="form-group">
                        <label for="kraPin">KRA PIN <span class="optional">(optional)</span></label>
                        <input type="text" id="kraPin" maxlength="30" placeholder="A123456789Z">
                    </div>

                    <div class="form-group">
                        <label for="organizationEmail">Email <span class="optional">(optional)</span></label>
                        <input type="email" id="organizationEmail" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label for="organizationPhone">Phone <span class="optional">(optional)</span></label>
                        <input type="tel" id="organizationPhone" maxlength="30">
                    </div>

                    <div class="form-group">
                        <label for="organizationAddress">Address <span class="optional">(optional)</span></label>
                        <input type="text" id="organizationAddress" maxlength="255">
                    </div>

                    <div class="form-actions profile-form-actions">
                        <button type="submit" class="primary-button" id="saveButton">Save changes</button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <script src="../../../shared/js/api.js"></script>
    <script src="../../../shared/js/form-helpers.js"></script>
    <script src="../../../shared/js/navigation.js"></script>
    <script src="../../../shared/js/shell.js"></script>
    <script src="organisation-profile.js"></script>
</body>
</html>