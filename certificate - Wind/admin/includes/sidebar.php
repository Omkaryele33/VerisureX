<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Dashboard' ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Certificates' ? 'active' : ''; ?>" href="certificates.php">
                    <i class="bi bi-card-list"></i> Certificates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Create Certificate' ? 'active' : ''; ?>" href="create_certificate.php">
                    <i class="bi bi-plus-circle"></i> Create Certificate
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Bulk Certificate Generation' ? 'active' : ''; ?>" href="bulk_certificates.php">
                    <i class="bi bi-files"></i> Bulk Certificates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Certificate Templates' || $pageTitle === 'Create Certificate Template' || $pageTitle === 'Edit Certificate Template' ? 'active' : ''; ?>" href="certificate_templates.php">
                    <i class="bi bi-file-earmark-richtext"></i> Templates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Verification Logs' ? 'active' : ''; ?>" href="verification_logs.php">
                    <i class="bi bi-clock-history"></i> Verification Logs
                </a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Users' ? 'active' : ''; ?>" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Advanced</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'API Management' ? 'active' : ''; ?>" href="api_management.php">
                    <i class="bi bi-key"></i> API Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Settings' ? 'active' : ''; ?>" href="settings.php">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Security Log' ? 'active' : ''; ?>" href="security_log.php">
                    <i class="bi bi-shield-lock"></i> Security Log
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $pageTitle === 'Diagnostics' ? 'active' : ''; ?>" href="diagnostic.php">
                    <i class="bi bi-wrench"></i> Diagnostics
                </a>
            </li>
        </ul>
    </div>
</nav>
