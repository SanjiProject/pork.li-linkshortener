// Mobile Touch Fix for Pork.li
// This script ensures all buttons and interactive elements work on mobile devices

(function() {
    'use strict';
    
    // Function to make an element mobile-touch friendly
    function makeTouchFriendly(element) {
        if (!element || element.hasAttribute('data-touch-enhanced')) return;
        
        // Mark element as enhanced to prevent duplicate processing
        element.setAttribute('data-touch-enhanced', 'true');
        
        // Add touch event listeners as fallback for click events
        element.addEventListener('touchstart', function(e) {
            // Add active state
            element.classList.add('touch-active');
        }, { passive: true });
        
        element.addEventListener('touchend', function(e) {
            // Remove active state
            element.classList.remove('touch-active');
            
            // Check if this is a modal close button or overlay
            const isModalClose = element.classList.contains('edit-modal-close') || 
                               element.classList.contains('edit-modal-overlay') ||
                               element.onclick !== null ||
                               element.getAttribute('onclick') !== null;
            
            // For modal close elements and elements with onclick handlers, 
            // let the normal click event handle it
            if (isModalClose) {
                return; // Don't prevent default or manually trigger click
            }
            
            // For other elements, prevent double-firing and manually trigger if needed
            e.preventDefault();
            
            // Fire a click event manually for non-modal elements
            setTimeout(() => {
                if (element.click && typeof element.click === 'function') {
                    element.click();
                }
            }, 10);
        }, { passive: false });
        
        element.addEventListener('touchcancel', function(e) {
            // Remove active state if touch is cancelled
            element.classList.remove('touch-active');
        }, { passive: true });
    }
    
    // Function to initialize touch fixes
    function initMobileTouchFixes() {
        // Select all interactive elements
        const interactiveElements = document.querySelectorAll(`
            .btn,
            button,
            input[type="submit"],
            input[type="button"],
            a[href],
            [role="button"],
            .clickable-link,
            .edit-link,
            .delete-link,
            .view-analytics,
            .manage-password,
            .add-destination,
            .remove-destination,
            .captcha-refresh
        `);
        
        // Also select modal elements separately with special handling
        const modalElements = document.querySelectorAll(`
            .edit-modal-close,
            .edit-modal-overlay,
            .edit-modal-cancel
        `);
        
        // Apply touch fixes to regular elements (excluding modal elements)
        interactiveElements.forEach(element => {
            // Skip modal elements as they have special handling
            if (!element.classList.contains('edit-modal-close') && 
                !element.classList.contains('edit-modal-overlay') && 
                !element.classList.contains('edit-modal-cancel')) {
                makeTouchFriendly(element);
            }
        });
        
        // Handle modal elements with minimal intervention (just visual feedback)
        modalElements.forEach(element => {
            if (!element.hasAttribute('data-modal-touch-enhanced')) {
                element.setAttribute('data-modal-touch-enhanced', 'true');
                
                element.addEventListener('touchstart', function(e) {
                    element.classList.add('touch-active');
                }, { passive: true });
                
                element.addEventListener('touchend', function(e) {
                    element.classList.remove('touch-active');
                    // Let the normal click event handle modal closing
                }, { passive: true });
                
                element.addEventListener('touchcancel', function(e) {
                    element.classList.remove('touch-active');
                }, { passive: true });
            }
        });
        
        console.log('Mobile touch fixes applied to', interactiveElements.length, 'elements and', modalElements.length, 'modal elements');
    }
    
    // Add CSS for touch active state
    function addTouchActiveStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .touch-active {
                opacity: 0.7 !important;
                transform: scale(0.98) !important;
                transition: all 0.1s ease !important;
            }
            
            /* Ensure buttons are always clickable */
            .btn, button, input[type="submit"], input[type="button"] {
                pointer-events: auto !important;
                position: relative !important;
            }
            
            /* Fix for mobile Safari */
            @supports (-webkit-touch-callout: none) {
                .btn, button {
                    cursor: pointer !important;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            addTouchActiveStyles();
            initMobileTouchFixes();
            
            // Re-apply fixes when new content is added (for modals, etc.)
            const observer = new MutationObserver(function(mutations) {
                let shouldUpdate = false;
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        // Only update if modal or significant content was added
                        for (let node of mutation.addedNodes) {
                            if (node.nodeType === 1 && // Element node
                                (node.classList.contains('edit-modal-overlay') ||
                                 node.classList.contains('btn') ||
                                 node.querySelector && node.querySelector('.btn'))) {
                                shouldUpdate = true;
                                break;
                            }
                        }
                    }
                });
                
                if (shouldUpdate) {
                    setTimeout(initMobileTouchFixes, 200);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: false // Only watch direct children to reduce overhead
            });
        });
    } else {
        addTouchActiveStyles();
        initMobileTouchFixes();
    }
    
    // Prevent iOS zoom on input focus
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        document.addEventListener('focusin', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') {
                e.target.style.fontSize = '16px';
            }
        });
    }
    
    // Fix for viewport height on mobile
    function fixViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }
    
    fixViewportHeight();
    window.addEventListener('resize', fixViewportHeight);
    window.addEventListener('orientationchange', function() {
        setTimeout(fixViewportHeight, 100);
    });
    
})();
