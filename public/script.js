// Link Rotator JavaScript
class LinkRotator {
    constructor() {
        this.destinationCount = 1;
        this.baseUrl = this.getBaseUrl();
        this.init();
    }

    init() {
        // Initialize collapse functionality
        this.initCollapseForm();
        
        // Initialize event listeners
        this.bindEvents();
        
        // Initialize clipboard functionality
        this.initClipboard();
        
        // Auto-cleanup expired links
        this.scheduleCleanup();
    }

    // Initialize form collapse functionality
    initCollapseForm() {
        const heroButton = document.getElementById('hero-start-creating-btn');
        const linkCreator = document.getElementById('link-creator');
        
        if (heroButton && linkCreator) {
            heroButton.addEventListener('click', () => {
                // Show the entire link creator section
                linkCreator.style.display = 'block';
                linkCreator.style.opacity = '0';
                linkCreator.style.transform = 'translateY(20px)';
                
                // Smooth scroll to link creator section
                linkCreator.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'center'
                });
                
                // Animate the section appearance after scroll
                setTimeout(() => {
                    linkCreator.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    linkCreator.style.opacity = '1';
                    linkCreator.style.transform = 'translateY(0)';
                    
                    // Initialize captcha after section is visible
                    setTimeout(() => {
                        if (window.captchaManager) {
                            window.captchaManager.generateCaptcha();
                        }
                        
                        // Focus first input
                        const firstInput = linkCreator.querySelector('.destination-url');
                        if (firstInput) {
                            firstInput.focus();
                        }
                    }, 300);
                }, 800); // Wait for scroll to complete
            });
        }
    }

    // Get base URL for XAMPP compatibility
    getBaseUrl() {
        // Get the current page's path
        let path = window.location.pathname;
        console.log('Original path:', path); // Debug log
        
        // Remove filename if present
        if (path.endsWith('.php') || path.endsWith('.html')) {
            path = path.substring(0, path.lastIndexOf('/'));
        }
        
        // Remove trailing slash
        path = path.replace(/\/$/, '');
        
        // If we're in a subdirectory like dashboard or admin, go up one level
        if (path.endsWith('/dashboard') || path.endsWith('/admin') || path.endsWith('/settings')) {
            path = path.substring(0, path.lastIndexOf('/'));
        }
        
        console.log('Calculated base URL:', path); // Debug log
        return path || '';
    }

    bindEvents() {
        // Add destination button
        const addDestBtn = document.getElementById('add-destination');
        if (addDestBtn) {
            addDestBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.addDestinationField();
            });
        }

        // Form submissions
        const linkForm = document.getElementById('link-form');
        if (linkForm) {
            linkForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createLink();
            });
        }

        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }

        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleRegister();
            });
        }

        // Delete link buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-link')) {
                e.preventDefault();
                const linkId = e.target.dataset.linkId;
                this.deleteLink(linkId);
            }

            if (e.target.classList.contains('edit-link')) {
                e.preventDefault();
                const linkId = e.target.dataset.linkId;
                const destinations = JSON.parse(e.target.dataset.destinations || '[]');
                const rotationType = e.target.dataset.rotationType;
                const hasPassword = e.target.dataset.hasPassword === 'true';
                this.editLink(linkId, destinations, rotationType, hasPassword);
            }

            if (e.target.classList.contains('clickable-link')) {
                e.preventDefault();
                const url = e.target.dataset.url;
                this.copyToClipboardWithFeedback(url, e.target);
            }

            if (e.target.classList.contains('remove-destination')) {
                e.preventDefault();
                this.removeDestinationField(e.target);
            }
        });

        // Real-time URL validation
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('destination-url')) {
                this.validateUrl(e.target);
            }
            
            if (e.target.id === 'custom_code') {
                this.validateCustomCode(e.target);
            }
        });

        // Custom code blur validation
        const customCodeInput = document.getElementById('custom_code');
        if (customCodeInput) {
            customCodeInput.addEventListener('blur', (e) => {
                if (e.target.value.trim()) {
                    this.checkCustomCodeAvailability(e.target);
                }
            });
        }
    }

    addDestinationField() {
        this.destinationCount++;
        const container = document.getElementById('destinations-container');
        
        const div = document.createElement('div');
        div.className = 'destination-input';
        div.innerHTML = `
            <input type="url" 
                   name="destinations[]" 
                   class="form-input destination-url" 
                   placeholder="https://example.com"
                   required>
            <button type="button" class="remove-destination remove-btn" title="Remove">×</button>
        `;
        
        container.appendChild(div);
        
        // Focus on the new input
        div.querySelector('input').focus();
    }

    removeDestinationField(button) {
        const container = document.getElementById('destinations-container');
        const destinationInputs = container.querySelectorAll('.destination-input');
        
        // Don't allow removing the last destination
        if (destinationInputs.length > 1) {
            button.closest('.destination-input').remove();
            this.destinationCount--;
        } else {
            this.showAlert('At least one destination URL is required', 'error');
        }
    }

    validateUrl(input) {
        const url = input.value.trim();
        if (url && !this.isValidUrl(url)) {
            input.setCustomValidity('Please enter a valid URL');
            input.classList.add('error');
        } else {
            input.setCustomValidity('');
            input.classList.remove('error');
        }
    }

    isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    validateCustomCode(input) {
        const code = input.value.trim();
        let isValid = true;
        let message = '';

        if (code.length > 0) {
            // Check length (3-50 characters)
            if (code.length < 3) {
                isValid = false;
                message = 'Custom code must be at least 3 characters';
            } else if (code.length > 50) {
                isValid = false;
                message = 'Custom code must be 50 characters or less';
            }
            // Check valid characters (letters, numbers, hyphens, underscores)
            else if (!/^[a-zA-Z0-9_-]+$/.test(code)) {
                isValid = false;
                message = 'Only letters, numbers, hyphens, and underscores allowed';
            }
            // Check reserved words
            else if (this.isReservedCode(code)) {
                isValid = false;
                message = 'This code is reserved and cannot be used';
            }
        }

        // Update UI
        if (isValid) {
            input.classList.remove('error');
            input.setCustomValidity('');
            this.hideCustomCodeError();
        } else if (code.length > 0) {
            input.classList.add('error');
            input.setCustomValidity(message);
            this.showCustomCodeError(message);
        } else {
            input.classList.remove('error');
            input.setCustomValidity('');
            this.hideCustomCodeError();
        }

        return isValid;
    }

    isReservedCode(code) {
        const reserved = ['api', 'admin', 'dashboard', 'login', 'register', 'logout', 'public', 'includes', 'config', 'www', 'mail', 'ftp', 'test'];
        return reserved.includes(code.toLowerCase());
    }

    async checkCustomCodeAvailability(input) {
        const code = input.value.trim();
        
        if (!code || !this.validateCustomCode(input)) {
            return;
        }

        // Show checking state
        this.showCustomCodeChecking();

        try {
            const response = await fetch(this.baseUrl + '/api/check-availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'code=' + encodeURIComponent(code)
            });

            const result = await response.json();
            
            if (result.available) {
                this.showCustomCodeSuccess('✓ Available');
            } else {
                this.showCustomCodeError('This custom link is already taken');
                input.classList.add('error');
            }
        } catch (error) {
            console.error('Availability check failed:', error);
            this.hideCustomCodeError();
        }
    }

    showCustomCodeError(message) {
        this.updateCustomCodeStatus(message, 'error');
    }

    showCustomCodeSuccess(message) {
        this.updateCustomCodeStatus(message, 'success');
    }

    showCustomCodeChecking() {
        this.updateCustomCodeStatus('Checking availability...', 'checking');
    }

    hideCustomCodeError() {
        const status = document.querySelector('.custom-code-status');
        if (status) {
            status.remove();
        }
    }

    updateCustomCodeStatus(message, type) {
        this.hideCustomCodeError();
        
        const container = document.querySelector('.custom-link-container');
        if (!container) return;

        const status = document.createElement('div');
        status.className = `custom-code-status custom-code-${type}`;
        status.innerHTML = message;
        container.appendChild(status);
    }

    async createLink() {
        const form = document.getElementById('link-form');
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        
        // Validate captcha answer is provided
        const captchaAnswer = formData.get('captcha_answer');
        if (!captchaAnswer || captchaAnswer.trim() === '') {
            this.showAlert('Please solve the captcha', 'error');
            return;
        }
        
        // Show loading state
        this.setLoading(submitBtn, true);
        
        try {
            const apiUrl = this.baseUrl + '/api/create-link.php';
            console.log('Making request to:', apiUrl); // Debug log
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status); // Debug log
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('Response data:', result); // Debug log
            
            if (result.success) {
                const message = result.is_custom 
                    ? 'Custom link created successfully!' 
                    : 'Link created successfully!';
                this.showAlert(message, 'success');
                this.displayCreatedLink(result.short_url, result.is_custom);
                form.reset();
                this.resetDestinationFields();
                this.hideCustomCodeError();
                // Refresh captcha after successful creation
                if (window.CaptchaManager) {
                    window.CaptchaManager.generateCaptcha();
                }
            } else {
                this.showAlert(result.error || 'Failed to create link', 'error');
                // Refresh captcha on error (likely wrong captcha)
                if (window.CaptchaManager) {
                    window.CaptchaManager.generateCaptcha();
                    const captchaInput = document.getElementById('captcha_answer');
                    if (captchaInput) {
                        captchaInput.value = '';
                    }
                }
            }
        } catch (error) {
            console.error('Create link error:', error); // Debug log
            
            // More specific error messages
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                this.showAlert('Cannot connect to server. Please check if the API endpoint is accessible.', 'error');
            } else if (error.message.includes('HTTP 404')) {
                this.showAlert('API endpoint not found. Please check your installation.', 'error');
            } else if (error.message.includes('HTTP 500')) {
                this.showAlert('Server error. Please check your PHP configuration.', 'error');
            } else {
                this.showAlert('Network error: ' + error.message, 'error');
            }
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    resetDestinationFields() {
        const container = document.getElementById('destinations-container');
        container.innerHTML = `
            <div class="destination-input">
                <input type="url" name="destinations[]" class="form-input destination-url" 
                       placeholder="https://example.com" required>
            </div>
        `;
        this.destinationCount = 1;
    }

    displayCreatedLink(shortUrl, isCustom = false) {
        const resultDiv = document.getElementById('link-result');
        if (resultDiv) {
            const customBadge = isCustom ? '<span class="custom-badge">Custom</span>' : '';
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h4>Your link is ready! ${customBadge}</h4>
                    <div class="mt-2">
                        <p><strong>Click the link below to copy it to your clipboard:</strong></p>
                        <div class="text-center">
                            <span class="short-link clickable-link" 
                                  data-url="${shortUrl}" 
                                  title="Click to copy link"
                                  style="font-size: 1.2rem; padding: 0.5rem 1rem; display: inline-block; border: 2px solid #fbbf24; border-radius: 8px; margin: 0.5rem 0;">
                                ${shortUrl.replace(window.location.origin, '')}
                            </span>
                        </div>
                    </div>
                </div>
            `;
            resultDiv.scrollIntoView({ behavior: 'smooth' });
        }
    }

    async handleLogin() {
        const form = document.getElementById('login-form');
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        
        this.setLoading(submitBtn, true);
        
        try {
            const response = await fetch(this.baseUrl + '/api/login.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = result.redirect || 'dashboard/index.php';
            } else {
                this.showAlert(result.error || 'Login failed', 'error');
            }
        } catch (error) {
            this.showAlert('Network error. Please try again.', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    async handleRegister() {
        const form = document.getElementById('register-form');
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        
        // Check password confirmation
        const password = formData.get('password');
        const confirmPassword = formData.get('confirm_password');
        
        if (password !== confirmPassword) {
            this.showAlert('Passwords do not match', 'error');
            return;
        }
        
        this.setLoading(submitBtn, true);
        
        try {
            const response = await fetch(this.baseUrl + '/api/register.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Registration successful! You can now log in.', 'success');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                this.showAlert(result.error || 'Registration failed', 'error');
            }
        } catch (error) {
            this.showAlert('Network error. Please try again.', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    async deleteLink(linkId) {
        if (!confirm('Are you sure you want to delete this link?')) {
            return;
        }
        
        try {
            const response = await fetch(this.baseUrl + '/api/delete-link.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ link_id: linkId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Link deleted successfully', 'success');
                // Remove the row from the table
                const row = document.querySelector(`tr[data-link-id="${linkId}"]`);
                if (row) {
                    row.remove();
                }
                // Refresh the page to update stats
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.showAlert(result.error || 'Failed to delete link', 'error');
            }
        } catch (error) {
            this.showAlert('Network error. Please try again.', 'error');
        }
    }

    editLink(linkId, destinations, rotationType, hasPassword = false) {
        // Remove any existing edit modals
        const existingModal = document.querySelector('.edit-link-modal-overlay');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create edit modal with unique IDs
        const modalId = 'edit-modal-' + linkId;
        const modal = document.createElement('div');
        modal.className = 'edit-modal-overlay edit-link-modal-overlay';
        modal.innerHTML = `
            <div class="edit-modal">
                <div class="edit-modal-header">
                    <h3>Edit Link Destinations</h3>
                    <button class="edit-modal-close">&times;</button>
                </div>
                <div class="edit-modal-body">
                    <form id="edit-link-form-${linkId}">
                        <input type="hidden" name="csrf_token" value="">
                        <input type="hidden" name="link_id" value="${linkId}">
                        
                        <div class="form-group">
                            <label class="form-label">Long ass URLs</label>
                            <div id="edit-destinations-container-${linkId}">
                                ${destinations.map((dest, index) => `
                                    <div class="destination-input">
                                        <input type="url" name="destinations[]" class="form-input destination-url" 
                                               value="${dest}" required>
                                        ${destinations.length > 1 ? '<button type="button" class="remove-destination remove-btn" title="Remove">×</button>' : ''}
                                    </div>
                                `).join('')}
                            </div>
                            <a href="#" id="edit-add-destination-${linkId}" class="add-destination">
                                <span>+</span> Add Another Destination
                            </a>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="edit_rotation_type_${linkId}">Rotation Type</label>
                            <select name="rotation_type" id="edit_rotation_type_${linkId}" class="form-input form-select">
                                <option value="round_robin" ${rotationType === 'round_robin' ? 'selected' : ''}>Round Robin (Sequential)</option>
                                <option value="random" ${rotationType === 'random' ? 'selected' : ''}>Random</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">🔒 Password Protection</label>
                            <div class="password-status-edit">
                                <p><strong>Current Status:</strong> 
                                    <span class="status-indicator ${hasPassword ? 'protected' : 'public'}">
                                        ${hasPassword ? '🔒 Password Protected' : '🌐 Public Access'}
                                    </span>
                                </p>
                            </div>
                            <select name="password_action" id="edit_password_action_${linkId}" class="form-input form-select">
                                <option value="keep">Keep current settings</option>
                                ${hasPassword ? 
                                    '<option value="remove">Remove password protection</option><option value="set">Change password</option>' :
                                    '<option value="set">Add password protection</option>'
                                }
                            </select>
                            <div class="form-group" id="edit-password-input-group-${linkId}" style="display: none; margin-top: 1rem;">
                                <input type="password" name="new_password" id="edit_new_password_${linkId}" class="form-input" 
                                       placeholder="Enter new password (min 4 characters)" minlength="4">
                                <small class="form-help">Minimum 4 characters required</small>
                            </div>
                        </div>

                        <div class="edit-modal-actions">
                            <button type="button" class="btn btn-secondary edit-modal-cancel">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Link</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        // Add CSRF token
        const csrfInput = modal.querySelector('input[name="csrf_token"]');
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        csrfInput.value = csrfToken;

        document.body.appendChild(modal);

        // Handle password action dropdown
        const passwordActionSelect = modal.querySelector(`#edit_password_action_${linkId}`);
        const passwordInputGroup = modal.querySelector(`#edit-password-input-group-${linkId}`);
        
        if (passwordActionSelect && passwordInputGroup) {
            passwordActionSelect.addEventListener('change', function() {
                if (this.value === 'set') {
                    passwordInputGroup.style.display = 'block';
                } else {
                    passwordInputGroup.style.display = 'none';
                }
            });
        }

        // Modal event listeners
        const closeModal = () => {
            try {
                if (modal && modal.parentNode === document.body) {
                    document.body.removeChild(modal);
                }
            } catch (error) {
                console.log('Modal already removed or not found');
            }
        };

        modal.querySelector('.edit-modal-close').addEventListener('click', closeModal);
        modal.querySelector('.edit-modal-cancel').addEventListener('click', closeModal);
        modal.querySelector('.edit-modal-overlay').addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Add destination functionality in modal
        const addDestinationBtn = modal.querySelector(`#edit-add-destination-${linkId}`);
        const destinationsContainer = modal.querySelector(`#edit-destinations-container-${linkId}`);
        
        if (addDestinationBtn && destinationsContainer) {
            addDestinationBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const div = document.createElement('div');
                div.className = 'destination-input';
                div.innerHTML = `
                    <input type="url" name="destinations[]" class="form-input destination-url" 
                           placeholder="https://example.com" required>
                    <button type="button" class="remove-destination remove-btn" title="Remove">×</button>
                `;
                destinationsContainer.appendChild(div);
            });
        }

        // Remove destination functionality in modal
        modal.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-destination')) {
                e.preventDefault();
                const container = modal.querySelector(`#edit-destinations-container-${linkId}`);
                const destinationInputs = container ? container.querySelectorAll('.destination-input') : [];
                
                if (destinationInputs.length > 1) {
                    e.target.closest('.destination-input').remove();
                } else {
                    this.showAlert('At least one destination URL is required', 'error');
                }
            }
        });

        // Form submission
        const editForm = modal.querySelector(`#edit-link-form-${linkId}`);
        if (editForm) {
            editForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updateLink(e.target, closeModal);
            });
        }
    }

    async updateLink(form, closeModal) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Show loading state
        this.setLoading(submitBtn, true);
        
        try {
            const response = await fetch(this.baseUrl + '/api/update-link.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Link updated successfully!', 'success');
                closeModal();
                // Reload the page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.showAlert(result.error || 'Failed to update link', 'error');
            }
        } catch (error) {
            this.showAlert('Network error. Please try again.', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    initClipboard() {
        // Fallback clipboard functionality
        if (!navigator.clipboard) {
            console.warn('Clipboard API not available');
        }
    }

    async copyToClipboard(text) {
        try {
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
            
            this.showAlert('Link copied to clipboard!', 'success');
        } catch (error) {
            this.showAlert('Failed to copy link', 'error');
        }
    }

    async copyToClipboardWithFeedback(text, element) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers or non-HTTPS
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
            
            // Show visual feedback on the element
            element.classList.add('copied');
            const originalTitle = element.title;
            element.title = 'Link copied to clipboard!';
            
            // Show simple alert message
            alert('Link copied to clipboard! ✓');
            
            setTimeout(() => {
                element.classList.remove('copied');
                element.title = originalTitle;
            }, 1000);
            
        } catch (error) {
            console.error('Failed to copy: ', error);
            alert('Failed to copy link. Please copy manually.');
            element.title = 'Failed to copy. Please copy manually.';
            setTimeout(() => {
                element.title = 'Click to copy link';
            }, 3000);
        }
    }

    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        // Insert at the top of the main content
        const main = document.querySelector('.main') || document.body;
        main.insertBefore(alertDiv, main.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
        
        // Scroll to alert
        alertDiv.scrollIntoView({ behavior: 'smooth' });
    }

    setLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.innerHTML = '<span class="loading"></span> Processing...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || 'Submit';
        }
    }

    scheduleCleanup() {
        // Clean up expired links every hour
        setInterval(() => {
            fetch(this.baseUrl + '/api/cleanup.php', { method: 'POST' })
                .catch(error => console.log('Cleanup failed:', error));
        }, 3600000); // 1 hour
    }

    // Utility function to format numbers
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    // Initialize tooltips if needed
    initTooltips() {
        const tooltipElements = document.querySelectorAll('[title]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                // Simple tooltip implementation
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = e.target.title;
                document.body.appendChild(tooltip);
                
                // Position tooltip
                const rect = e.target.getBoundingClientRect();
                tooltip.style.left = rect.left + 'px';
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                
                e.target.addEventListener('mouseleave', () => {
                    tooltip.remove();
                }, { once: true });
            });
        });
    }

    // Debug function to help troubleshoot network issues
    debugNetworkSettings() {
        console.log('=== Network Debug Info ===');
        console.log('Current URL:', window.location.href);
        console.log('Base URL:', this.baseUrl);
        console.log('Full API URL (create-link):', this.baseUrl + '/api/create-link.php');
        console.log('Full API URL (generate-captcha):', this.baseUrl + '/api/generate-captcha.php');
        console.log('========================');
    }
}

// Captcha functionality
class CaptchaManager {
    constructor() {
        this.baseUrl = this.getBaseUrl();
        this.init();
    }

    // Get base URL for XAMPP compatibility (same as LinkRotator)
    getBaseUrl() {
        let path = window.location.pathname;
        
        if (path.endsWith('.php') || path.endsWith('.html')) {
            path = path.substring(0, path.lastIndexOf('/'));
        }
        
        path = path.replace(/\/$/, '');
        
        if (path.endsWith('/dashboard') || path.endsWith('/admin') || path.endsWith('/settings')) {
            path = path.substring(0, path.lastIndexOf('/'));
        }
        
        return path || '';
    }

    async generateCaptcha() {
        const equationElement = document.getElementById('captcha-equation');
        if (!equationElement) return;
        
        try {
            // Try multiple API URL formats for nginx compatibility
            const currentPath = window.location.pathname;
            let apiUrls = [];
            
            if (currentPath.includes('/dashboard/') || currentPath.includes('/admin/') || currentPath.includes('/settings/')) {
                // We're in a subdirectory
                apiUrls = [
                    '../api/generate-captcha.php',
                    this.baseUrl + '/api/generate-captcha.php',
                    './api/generate-captcha.php',
                    'api/generate-captcha.php'
                ];
            } else {
                // We're in the root directory
                apiUrls = [
                    'api/generate-captcha.php',
                    './api/generate-captcha.php',
                    this.baseUrl + '/api/generate-captcha.php'
                ];
            }
            
            let lastError = null;
            
            for (const apiUrl of apiUrls) {
                try {
                    console.log('Trying captcha API:', apiUrl);
                    const response = await fetch(apiUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.equation) {
                        equationElement.textContent = data.equation;
                        console.log('Captcha generated successfully');
                        return; // Success, exit the function
                    } else {
                        throw new Error(data.message || 'Invalid captcha response');
                    }
                } catch (error) {
                    console.warn(`Failed to fetch from ${apiUrl}:`, error.message);
                    lastError = error;
                    continue; // Try next URL
                }
            }
            
            // If all URLs failed, throw the last error
            throw lastError || new Error('All captcha API URLs failed');
            
        } catch (error) {
            console.error('Error generating captcha:', error);
            
            // Show fallback captcha with session storage
            const fallbackEquations = [
                '2 + 3 = ?',
                '5 - 2 = ?', 
                '4 + 1 = ?',
                '7 - 3 = ?',
                '3 + 4 = ?'
            ];
            
            const randomEquation = fallbackEquations[Math.floor(Math.random() * fallbackEquations.length)];
            equationElement.textContent = randomEquation;
            
            // Store the expected answer for validation
            const answer = this.calculateFallbackAnswer(randomEquation);
            sessionStorage.setItem('fallback_captcha_answer', answer);
            sessionStorage.setItem('fallback_captcha_equation', randomEquation);
            
            console.log('Using fallback captcha:', randomEquation);
        }
    }
    
    calculateFallbackAnswer(equation) {
        // Extract numbers and operator from equation like "2 + 3 = ?"
        const match = equation.match(/(\d+)\s*([+\-])\s*(\d+)/);
        if (match) {
            const num1 = parseInt(match[1]);
            const operator = match[2];
            const num2 = parseInt(match[3]);
            
            return operator === '+' ? num1 + num2 : num1 - num2;
        }
        return 5; // Default fallback
    }

    validateCaptchaInput(input) {
        let value = input.value;
        
        // Allow only numbers and "-"
        value = value.replace(/[^0-9-]/g, '');
        
        // Prevent consecutive "-"
        value = value.replace(/--/g, '-');
        
        // Prevent "-" followed by "0"
        value = value.replace(/-0/g, '-');
        
        // "0" cannot be followed by anything except end of string
        value = value.replace(/0./g, '0');
        
        // "0-9" cannot be followed by "-"
        value = value.replace(/\d-/g, function(match) {
            return match[0];
        });
        
        // If no "-", make the field only max 2 digits
        if (!value.includes('-')) {
            value = value.substring(0, 2);
        }
        
        input.value = value;
    }

    init() {
        // Only generate captcha if it's still showing "Loading..." 
        const captchaEquation = document.getElementById('captcha-equation');
        if (captchaEquation && captchaEquation.textContent.includes('Loading')) {
            // Generate captcha only if it's still loading
            this.generateCaptcha();
        }
        
        // Setup refresh button
        const refreshButton = document.getElementById('refresh-captcha');
        if (refreshButton) {
            refreshButton.addEventListener('click', () => {
                this.generateCaptcha();
                const captchaInput = document.getElementById('captcha_answer');
                if (captchaInput) {
                    captchaInput.value = '';
                    captchaInput.focus();
                }
            });
        }
        
        // Setup input validation
        const captchaInput = document.getElementById('captcha_answer');
        if (captchaInput) {
            captchaInput.addEventListener('input', (e) => {
                this.validateCaptchaInput(e.target);
            });
            
            captchaInput.addEventListener('keydown', (e) => {
                if (e.keyCode === 13) { // Enter key
                    e.preventDefault();
                    // Check if we're on register or login page
                    const registerForm = document.getElementById('register-form');
                    const loginForm = document.getElementById('login-form');
                    const linkForm = document.getElementById('link-form');
                    
                    if (registerForm) {
                        registerForm.dispatchEvent(new Event('submit'));
                    } else if (loginForm) {
                        loginForm.dispatchEvent(new Event('submit'));
                    } else if (linkForm) {
                        linkForm.dispatchEvent(new Event('submit'));
                    }
                }
            });
        }
    }
}

// Mobile optimizations
class MobileOptimizer {
    constructor() {
        this.init();
    }

    init() {
        this.setupViewport();
        this.setupTouchOptimizations();
        this.setupKeyboardOptimizations();
        this.setupScrollOptimizations();
    }

    setupViewport() {
        // Prevent zoom on input focus for iOS
        const viewportMeta = document.querySelector('meta[name="viewport"]');
        if (viewportMeta && /iPhone|iPad|iPod/.test(navigator.userAgent)) {
            viewportMeta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
        }
    }

    setupTouchOptimizations() {
        // Add touch feedback for buttons
        document.addEventListener('touchstart', (e) => {
            if (e.target.matches('.btn, .nav a, .short-link')) {
                e.target.style.opacity = '0.8';
            }
        });

        document.addEventListener('touchend', (e) => {
            if (e.target.matches('.btn, .nav a, .short-link')) {
                setTimeout(() => {
                    e.target.style.opacity = '';
                }, 150);
            }
        });

        // Prevent double-tap zoom on buttons
        document.addEventListener('touchend', (e) => {
            if (e.target.matches('.btn, .nav a')) {
                e.preventDefault();
            }
        });
    }

    setupKeyboardOptimizations() {
        // Handle virtual keyboard on mobile
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => {
                const isKeyboardOpen = window.visualViewport.height < window.innerHeight * 0.75;
                document.body.classList.toggle('keyboard-open', isKeyboardOpen);
            });
        }

        // Auto-scroll to focused input
        document.addEventListener('focusin', (e) => {
            if (e.target.matches('input, textarea, select')) {
                setTimeout(() => {
                    e.target.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 300);
            }
        });
    }

    setupScrollOptimizations() {
        // Smooth scrolling for iOS
        document.documentElement.style.webkitOverflowScrolling = 'touch';
        
        // Prevent overscroll on iOS
        document.addEventListener('touchmove', (e) => {
            if (e.target.closest('.table-responsive, .edit-modal-body, .recent-clicks')) {
                return; // Allow scrolling in these containers
            }
            
            const scrollable = e.target.closest('[data-scrollable]');
            if (!scrollable) {
                const isAtTop = window.pageYOffset === 0;
                const isAtBottom = window.pageYOffset >= document.body.scrollHeight - window.innerHeight;
                
                if ((isAtTop && e.touches[0].clientY > e.touches[0].clientY) || 
                    (isAtBottom && e.touches[0].clientY < e.touches[0].clientY)) {
                    e.preventDefault();
                }
            }
        }, { passive: false });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const linkRotator = new LinkRotator();
    window.captchaManager = new CaptchaManager();
    new MobileOptimizer();
    
    // Make debug function available globally
    window.debugNetwork = () => linkRotator.debugNetworkSettings();
    
    // Auto-run debug on console if there are errors
    console.log('Link Rotator loaded. Type debugNetwork() in console to see network debug info.');
});

// Service Worker registration removed - not needed for this application 