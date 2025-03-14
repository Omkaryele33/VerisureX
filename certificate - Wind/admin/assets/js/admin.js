/**
 * CertifyPro - Premium Certificate Validation System
 * Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alert messages after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }, 5000);
    });
    
    // Initialize date pickers if present
    const dateInputs = document.querySelectorAll('input[type="date"]');
    if (dateInputs.length > 0) {
        dateInputs.forEach(function(input) {
            if (!input.value && input.getAttribute('data-default-today') !== 'false') {
                const today = new Date();
                const year = today.getFullYear();
                let month = today.getMonth() + 1;
                let day = today.getDate();
                
                // Add leading zeros if needed
                month = month < 10 ? '0' + month : month;
                day = day < 10 ? '0' + day : day;
                
                input.value = `${year}-${month}-${day}`;
            }
        });
    }
    
    // File input preview for image uploads
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photo-preview');
    
    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    photoPreview.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Premium Features - Analytics Dashboard
    setupAnalyticsDashboard();
    
    // Premium Features - Bulk Operations
    setupBulkOperations();
    
    // Premium Features - Export Functionality
    setupExportFunctions();
});

/**
 * Setup Analytics Dashboard
 */
function setupAnalyticsDashboard() {
    // Check if we're on the dashboard page with charts
    if (!document.getElementById('verificationsChart')) {
        return;
    }
    
    // Handle export analytics button
    const exportAnalyticsBtn = document.getElementById('export-analytics');
    if (exportAnalyticsBtn) {
        exportAnalyticsBtn.addEventListener('click', function() {
            // In a real implementation, this would trigger the export process
            // For demo purposes, we'll just show an alert
            alert('Exporting analytics data... This feature will be available in the next update.');
        });
    }
    
    // Set up data refresh button
    const refreshButton = document.getElementById('refresh-analytics');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            // Show loading state
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Refreshing...';
            this.disabled = true;
            
            // Simulate data refresh
            setTimeout(() => {
                this.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh Data';
                this.disabled = false;
                
                // Show success message
                const alertContainer = document.querySelector('.container-fluid');
                if (alertContainer) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        Analytics data refreshed successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    alertContainer.insertBefore(alert, alertContainer.firstChild);
                    
                    // Auto dismiss after 5 seconds
                    setTimeout(() => {
                        const closeButton = alert.querySelector('.btn-close');
                        if (closeButton) {
                            closeButton.click();
                        }
                    }, 5000);
                }
            }, 2000);
        });
    }
}

/**
 * Setup Bulk Operations
 */
function setupBulkOperations() {
    const bulkActionSelect = document.getElementById('bulk-action');
    const bulkActionBtn = document.getElementById('apply-bulk-action');
    
    if (bulkActionSelect && bulkActionBtn) {
        bulkActionBtn.addEventListener('click', function() {
            const selectedAction = bulkActionSelect.value;
            if (!selectedAction) {
                return;
            }
            
            // Get all selected certificates
            const selectedCertificates = document.querySelectorAll('input[name="selected_certificates[]"]:checked');
            if (selectedCertificates.length === 0) {
                alert('Please select at least one certificate to perform this action.');
                return;
            }
            
            // Confirm action
            if (confirm(`Are you sure you want to ${selectedAction} ${selectedCertificates.length} certificate(s)?`)) {
                // In a real implementation, this would submit the form
                // For demo, we'll just show a success message
                alert(`Successfully ${selectedAction}ed ${selectedCertificates.length} certificate(s).`);
            }
        });
    }
    
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('select-all-certificates');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_certificates[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
}

/**
 * Setup Export Functions
 */
function setupExportFunctions() {
    const exportButtons = document.querySelectorAll('.export-data');
    
    exportButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const exportType = this.dataset.exportType || 'csv';
            const exportTarget = this.dataset.exportTarget || 'data';
            
            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Exporting...';
            this.disabled = true;
            
            // Simulate export process
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
                
                // Show success message
                alert(`${exportTarget} has been exported as ${exportType.toUpperCase()} successfully!`);
            }, 1500);
        });
    });
}
