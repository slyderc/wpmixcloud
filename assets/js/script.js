/**
 * WP Mixcloud Archives - Frontend Scripts
 *
 * @package WPMixcloudArchives
 */

(function() {
    'use strict';

    /**
     * Initialize Mixcloud modal player functionality
     */
    function initMixcloudPlayers() {
        const artworkElements = document.querySelectorAll('.mixcloud-card-artwork');
        const modal = document.getElementById('mixcloud-player-modal');
        const modalContent = document.querySelector('.mixcloud-modal-player-container');
        const closeBtn = document.querySelector('.mixcloud-modal-close');
        
        if (!modal || !modalContent || !closeBtn) {
            return;
        }

        // Add click handlers to artwork elements
        artworkElements.forEach(function(artwork) {
            artwork.addEventListener('click', function() {
                const cloudcastKey = artwork.getAttribute('data-cloudcast-key');
                const cloudcastUrl = artwork.getAttribute('data-cloudcast-url');
                const cloudcastName = artwork.getAttribute('data-cloudcast-name');
                
                if (cloudcastUrl) {
                    openMixcloudModal(modal, modalContent, cloudcastUrl, cloudcastName);
                }
            });
        });

        // Close modal handlers
        closeBtn.addEventListener('click', function() {
            closeMixcloudModal(modal, modalContent);
        });

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeMixcloudModal(modal, modalContent);
            }
        });

        // ESC key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                closeMixcloudModal(modal, modalContent);
            }
        });
    }

    /**
     * Open Mixcloud player modal
     * 
     * @param {Element} modal Modal element
     * @param {Element} container Player container element  
     * @param {string} cloudcastUrl Mixcloud URL
     * @param {string} cloudcastName Cloudcast name
     */
    function openMixcloudModal(modal, container, cloudcastUrl, cloudcastName) {
        // Build embed URL
        const embedParams = {
            hide_cover: '1',
            mini: '0',
            light: '1',
            hide_artwork: '0',
            autoplay: '0'
        };
        
        const baseEmbedUrl = cloudcastUrl.replace('https://www.mixcloud.com/', 'https://www.mixcloud.com/widget/iframe/?feed=');
        const paramString = Object.keys(embedParams).map(key => key + '=' + embedParams[key]).join('&');
        const embedUrl = baseEmbedUrl + '&' + paramString;
        
        // Create iframe
        const iframe = document.createElement('iframe');
        iframe.src = embedUrl;
        iframe.width = '100%';
        iframe.height = '380';
        iframe.frameBorder = '0';
        iframe.allowFullscreen = true;
        iframe.title = 'Mixcloud player for ' + cloudcastName;
        
        // Clear container and add iframe
        container.innerHTML = '';
        container.appendChild(iframe);
        
        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    /**
     * Close Mixcloud player modal
     * 
     * @param {Element} modal Modal element
     * @param {Element} container Player container element
     */
    function closeMixcloudModal(modal, container) {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
        
        // Clear the iframe to stop playback
        container.innerHTML = '';
    }

    /**
     * Handle table responsiveness
     */
    function handleTableResponsiveness() {
        const tables = document.querySelectorAll('.mixcloud-archives-table');
        
        tables.forEach(function(table) {
            // Add data-label attributes for mobile view
            const headers = table.querySelectorAll('thead th');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const cells = row.querySelectorAll('td');
                cells.forEach(function(cell, index) {
                    if (headers[index]) {
                        cell.setAttribute('data-label', headers[index].textContent);
                    }
                });
            });
        });
    }

    /**
     * Initialize play count animations
     */
    function initPlayCountAnimations() {
        const stats = document.querySelectorAll('.mixcloud-stats');
        
        if ('IntersectionObserver' in window) {
            const statsObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('mixcloud-stats-visible');
                    }
                });
            }, {
                threshold: 0.5
            });

            stats.forEach(function(stat) {
                statsObserver.observe(stat);
            });
        }
    }

    /**
     * Initialize lazy loading for artwork images
     */
    function initLazyLoadingImages() {
        const lazyImages = document.querySelectorAll('.mixcloud-artwork[data-src]');
        
        if (lazyImages.length === 0) {
            return;
        }

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        loadLazyImage(img);
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '25px', // Start loading 25px before visible
                threshold: 0.1
            });

            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            lazyImages.forEach(function(img) {
                loadLazyImage(img);
            });
        }
    }

    /**
     * Load a lazy image
     * 
     * @param {Element} img Image element to load
     */
    function loadLazyImage(img) {
        const src = img.getAttribute('data-src');
        
        if (!src) {
            return;
        }

        // Add loading class
        img.classList.add('mixcloud-artwork-loading');
        
        // Create a new image to preload
        const tempImg = new Image();
        
        tempImg.onload = function() {
            img.src = src;
            img.classList.remove('mixcloud-artwork-loading');
            img.classList.add('mixcloud-artwork-loaded');
            img.removeAttribute('data-src');
        };
        
        tempImg.onerror = function() {
            img.classList.remove('mixcloud-artwork-loading');
            handleImageError(img);
        };
        
        tempImg.src = src;
    }

    /**
     * Handle artwork loading errors
     */
    function handleArtworkErrors() {
        const artworks = document.querySelectorAll('.mixcloud-artwork');
        
        artworks.forEach(function(img) {
            img.addEventListener('error', function() {
                handleImageError(img);
            }, { once: true });
        });
    }

    /**
     * Handle individual image error
     * 
     * @param {Element} img Image element that failed to load
     */
    function handleImageError(img) {
        const wrapper = img.parentElement;
        img.style.display = 'none';
        
        const placeholder = document.createElement('div');
        placeholder.className = 'mixcloud-artwork-error';
        placeholder.innerHTML = '<span class="dashicons dashicons-format-audio"></span>';
        placeholder.setAttribute('aria-label', wpMixcloudArchives.noArtworkText);
        
        wrapper.appendChild(placeholder);
    }

    /**
     * Initialize date filtering functionality
     */
    function initDateFiltering() {
        const dateFilters = document.querySelectorAll('.mixcloud-date-filter');
        
        dateFilters.forEach(function(filter) {
            const applyBtn = filter.querySelector('.mixcloud-date-apply');
            const clearBtn = filter.querySelector('.mixcloud-date-clear');
            const startDateInput = filter.querySelector('.mixcloud-start-date');
            const endDateInput = filter.querySelector('.mixcloud-end-date');
            
            if (!applyBtn || !clearBtn || !startDateInput || !endDateInput) {
                return;
            }
            
            // Apply filter button
            applyBtn.addEventListener('click', function() {
                const account = this.getAttribute('data-account');
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                
                // Validate date range
                if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                    alert(wpMixcloudArchives.invalidDateRangeText || 'End date must be after start date.');
                    return;
                }
                
                applyDateFilter(account, startDate, endDate);
            });
            
            // Clear filter button
            clearBtn.addEventListener('click', function() {
                const account = this.getAttribute('data-account');
                startDateInput.value = '';
                endDateInput.value = '';
                applyDateFilter(account, '', '');
            });
            
            // Auto-apply on date change (with debounce)
            let dateChangeTimeout;
            function handleDateChange() {
                clearTimeout(dateChangeTimeout);
                dateChangeTimeout = setTimeout(function() {
                    const account = startDateInput.getAttribute('data-account');
                    const startDate = startDateInput.value;
                    const endDate = endDateInput.value;
                    
                    // Only auto-apply if both dates are set
                    if (startDate && endDate) {
                        if (new Date(startDate) <= new Date(endDate)) {
                            applyDateFilter(account, startDate, endDate);
                        }
                    }
                }, 500);
            }
            
            startDateInput.addEventListener('change', handleDateChange);
            endDateInput.addEventListener('change', handleDateChange);
        });
    }
    
    /**
     * Request cache for deduplication
     */
    const activeRequests = new Map();
    
    /**
     * Enhanced AJAX request helper with timeout and retry logic
     * 
     * @param {string} url Request URL
     * @param {Object} data Request data
     * @param {Object} options Request options (timeout, retries, signal)
     * @returns {Promise} Promise that resolves to response data
     */
    async function makeAjaxRequest(url, data, options = {}) {
        const {
            timeout = 10000,
            retries = 2,
            signal
        } = options;
        
        let lastError;
        
        for (let attempt = 0; attempt <= retries; attempt++) {
            try {
                // Create timeout promise
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('Request timeout')), timeout);
                });
                
                // Create fetch promise
                const fetchPromise = fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data),
                    signal: signal
                });
                
                // Race between timeout and fetch
                const response = await Promise.race([fetchPromise, timeoutPromise]);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const responseData = await response.json();
                return responseData;
                
            } catch (error) {
                lastError = error;
                
                // Don't retry on abort
                if (error.name === 'AbortError') {
                    throw error;
                }
                
                // Don't retry on final attempt
                if (attempt === retries) {
                    throw error;
                }
                
                // Wait before retry with exponential backoff
                const delay = Math.min(1000 * Math.pow(2, attempt), 5000);
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
        
        throw lastError;
    }
    
    /**
     * Apply date filter via AJAX with enhanced error handling and deduplication
     * 
     * @param {string} account Account name
     * @param {string} startDate Start date (YYYY-MM-DD)
     * @param {string} endDate End date (YYYY-MM-DD)
     */
    function applyDateFilter(account, startDate, endDate) {
        const container = document.querySelector('.mixcloud-archives-container[data-account="' + account + '"]');
        const table = container ? container.querySelector('.mixcloud-archives-table tbody') : null;
        const applyBtn = container ? container.querySelector('.mixcloud-date-apply') : null;
        
        if (!container || !table || !applyBtn) {
            return;
        }
        
        // Create request key for deduplication
        const requestKey = `filter_${account}_${startDate}_${endDate}`;
        
        // Check if identical request is already in progress
        if (activeRequests.has(requestKey)) {
            return;
        }
        
        // Show loading state
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<span class="mixcloud-loading-spinner"></span>' + 
                           (wpMixcloudArchives.filteringText || 'Filtering...');
        
        // Add loading class to table
        table.classList.add('mixcloud-table-loading');
        
        // Prepare AJAX data
        const ajaxData = {
            action: 'mixcloud_filter_by_date',
            nonce: wpMixcloudArchives.nonce,
            account: account,
            start_date: startDate,
            end_date: endDate,
            limit: 20, // Could be made configurable
            lazy_load: 'true',
            mini_player: 'true'
        };
        
        // Track active request
        const abortController = new AbortController();
        activeRequests.set(requestKey, abortController);
        
        // Make AJAX request with timeout and retry logic
        makeAjaxRequest(wpMixcloudArchives.ajaxUrl, ajaxData, {
            signal: abortController.signal,
            timeout: 10000, // 10 second timeout
            retries: 2
        })
        .then(data => {
            if (data.success) {
                // Update table content
                table.innerHTML = data.data.html;
                
                // Re-initialize functionality for new content
                initMixcloudPlayers();
                initLazyLoadingImages();
                handleArtworkErrors();
                initPlayCountAnimations();
                
                // Show success message (optional)
                showFilterMessage(container, data.data.message, 'success');
            } else {
                // Show error message
                showFilterMessage(container, data.data.message || 'An error occurred while filtering.', 'error');
            }
        })
        .catch(error => {
            if (error.name !== 'AbortError') {
                console.error('Date filter error:', error);
                const errorMessage = error.message || wpMixcloudArchives.filterErrorText || 'Failed to filter results. Please try again.';
                showFilterMessage(container, errorMessage, 'error');
            }
        })
        .finally(() => {
            // Clean up
            activeRequests.delete(requestKey);
            
            // Reset button state
            applyBtn.disabled = false;
            applyBtn.innerHTML = wpMixcloudArchives.applyFilterText || 'Apply Filter';
            table.classList.remove('mixcloud-table-loading');
        });
    }
    
    /**
     * Show filter message
     * 
     * @param {Element} container Container element
     * @param {string} message Message text
     * @param {string} type Message type (success, error)
     */
    function showFilterMessage(container, message, type) {
        // Remove existing messages
        const existingMessages = container.querySelectorAll('.mixcloud-filter-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create message element
        const messageEl = document.createElement('div');
        messageEl.className = 'mixcloud-filter-message mixcloud-filter-message-' + type;
        messageEl.innerHTML = message;
        
        // Insert after date filter
        const dateFilter = container.querySelector('.mixcloud-date-filter');
        if (dateFilter) {
            dateFilter.parentNode.insertBefore(messageEl, dateFilter.nextSibling);
        } else {
            container.insertBefore(messageEl, container.firstChild);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.remove();
            }
        }, 5000);
    }
    
    /**
     * Initialize pagination functionality
     */
    function initPagination() {
        const paginationContainers = document.querySelectorAll('.mixcloud-pagination');
        
        paginationContainers.forEach(function(container) {
            const account = container.getAttribute('data-account');
            
            // Handle pagination button clicks
            const buttons = container.querySelectorAll('.mixcloud-pagination-btn, .mixcloud-pagination-link');
            buttons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const page = this.getAttribute('data-page');
                    if (page && !this.classList.contains('mixcloud-pagination-disabled')) {
                        navigateToPage(account, parseInt(page));
                    }
                });
            });
            
            // Handle keyboard navigation
            const numberButtons = container.querySelectorAll('.mixcloud-pagination-number');
            numberButtons.forEach(function(button) {
                button.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
        });
    }
    
    /**
     * Navigate to a specific page with enhanced AJAX handling
     * 
     * @param {string} account Account name
     * @param {number} page Page number
     */
    function navigateToPage(account, page) {
        const container = document.querySelector('.mixcloud-archives-container[data-account="' + account + '"]');
        const table = container ? container.querySelector('.mixcloud-archives-table tbody') : null;
        const pagination = container ? container.querySelector('.mixcloud-pagination') : null;
        
        if (!container || !table || !pagination) {
            return;
        }
        
        // Create request key for deduplication
        const requestKey = `paginate_${account}_${page}`;
        
        // Check if identical request is already in progress
        if (activeRequests.has(requestKey)) {
            return;
        }
        
        // Get current filter settings
        const startDateInput = container.querySelector('.mixcloud-start-date');
        const endDateInput = container.querySelector('.mixcloud-end-date');
        const startDate = startDateInput ? startDateInput.value : '';
        const endDate = endDateInput ? endDateInput.value : '';
        
        // Show loading state
        pagination.classList.add('mixcloud-pagination-loading');
        table.classList.add('mixcloud-table-loading');
        
        // Get per_page setting from current pagination info (or use default)
        const perPageFromInfo = pagination.querySelector('.mixcloud-pagination-info');
        let perPage = 10; // default
        
        // Prepare AJAX data
        const ajaxData = {
            action: 'mixcloud_paginate',
            nonce: wpMixcloudArchives.nonce,
            account: account,
            page: page,
            per_page: perPage,
            start_date: startDate,
            end_date: endDate,
            limit: 100, // API limit
            lazy_load: 'true',
            mini_player: 'true'
        };
        
        // Track active request
        const abortController = new AbortController();
        activeRequests.set(requestKey, abortController);
        
        // Make AJAX request with timeout and retry logic
        makeAjaxRequest(wpMixcloudArchives.ajaxUrl, ajaxData, {
            signal: abortController.signal,
            timeout: 15000, // 15 second timeout for pagination
            retries: 2
        })
        .then(data => {
            if (data.success) {
                // Update table content
                table.innerHTML = data.data.table_html;
                
                // Update pagination controls
                pagination.outerHTML = data.data.pagination_html;
                
                // Re-initialize functionality for new content
                initMixcloudPlayers();
                initLazyLoadingImages();
                handleArtworkErrors();
                initPlayCountAnimations();
                initPagination(); // Re-init pagination for new controls
                
                // Scroll to top of container with smooth animation
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Show success message (optional)
                showFilterMessage(container, data.data.message, 'success');
            } else {
                // Show error message
                showFilterMessage(container, data.data.message || 'An error occurred while loading the page.', 'error');
            }
        })
        .catch(error => {
            if (error.name !== 'AbortError') {
                console.error('Pagination error:', error);
                const errorMessage = error.message || wpMixcloudArchives.paginationErrorText || 'Failed to load page. Please try again.';
                showFilterMessage(container, errorMessage, 'error');
            }
        })
        .finally(() => {
            // Clean up
            activeRequests.delete(requestKey);
            
            // Reset loading states
            const currentPagination = document.querySelector('.mixcloud-pagination[data-account="' + account + '"]');
            if (currentPagination) {
                currentPagination.classList.remove('mixcloud-pagination-loading');
            }
            table.classList.remove('mixcloud-table-loading');
        });
    }
    
    /**
     * Initialize social sharing functionality
     */
    function initSocialSharing() {
        const socialButtons = document.querySelectorAll('.mixcloud-social-btn');
        
        socialButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const platform = this.getAttribute('data-platform');
                const container = this.closest('.mixcloud-social-sharing');
                
                if (!container) return;
                
                const url = container.getAttribute('data-url');
                const title = container.getAttribute('data-title');
                const description = container.getAttribute('data-description');
                
                handleSocialShare(platform, url, title, description, this);
            });
        });
    }
    
    /**
     * Handle social sharing for different platforms
     * 
     * @param {string} platform Platform name
     * @param {string} url URL to share
     * @param {string} title Title to share
     * @param {string} description Description to share
     * @param {Element} button Button element that was clicked
     */
    function handleSocialShare(platform, url, title, description, button) {
        let shareUrl = '';
        
        switch (platform) {
            case 'facebook':
                shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
                break;
                
            case 'twitter':
                const twitterText = title + ' - ' + description;
                shareUrl = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(twitterText) + '&url=' + encodeURIComponent(url);
                break;
                
            case 'bluesky':
                const blueskyText = title + ' - ' + description + ' ' + url;
                shareUrl = 'https://bsky.app/intent/compose?text=' + encodeURIComponent(blueskyText);
                break;
                
            case 'copy':
                copyToClipboard(url, button);
                return; // Don't open a window for copy
        }
        
        if (shareUrl) {
            // Track the share event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'share', {
                    method: platform,
                    content_type: 'mixcloud_track',
                    content_id: url
                });
            }
            
            // Open share dialog
            const popup = window.open(
                shareUrl,
                'social-share',
                'width=600,height=400,scrollbars=yes,resizable=yes'
            );
            
            // Focus the popup if it was successfully opened
            if (popup) {
                popup.focus();
            }
        }
    }
    
    /**
     * Copy URL to clipboard
     * 
     * @param {string} url URL to copy
     * @param {Element} button Button element
     */
    function copyToClipboard(url, button) {
        if (navigator.clipboard && window.isSecureContext) {
            // Use modern clipboard API
            navigator.clipboard.writeText(url).then(function() {
                showCopySuccess(button);
            }).catch(function(err) {
                console.warn('Failed to copy to clipboard:', err);
                fallbackCopyToClipboard(url, button);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyToClipboard(url, button);
        }
    }
    
    /**
     * Fallback copy method for older browsers
     * 
     * @param {string} url URL to copy
     * @param {Element} button Button element
     */
    function fallbackCopyToClipboard(url, button) {
        const textArea = document.createElement('textarea');
        textArea.value = url;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess(button);
            } else {
                showCopyError(button);
            }
        } catch (err) {
            console.warn('Fallback copy failed:', err);
            showCopyError(button);
        }
        
        document.body.removeChild(textArea);
    }
    
    /**
     * Show copy success feedback
     * 
     * @param {Element} button Button element
     */
    function showCopySuccess(button) {
        const originalHTML = button.innerHTML;
        const originalClass = button.className;
        
        button.classList.add('copied');
        button.innerHTML = '<span class="dashicons dashicons-yes"></span><span class="mixcloud-social-label">Copied!</span>';
        
        // Track the copy event
        if (typeof gtag !== 'undefined') {
            gtag('event', 'copy_link', {
                event_category: 'social_sharing',
                event_label: 'mixcloud_track'
            });
        }
        
        setTimeout(function() {
            button.className = originalClass;
            button.innerHTML = originalHTML;
        }, 2000);
    }
    
    /**
     * Show copy error feedback
     * 
     * @param {Element} button Button element
     */
    function showCopyError(button) {
        const originalHTML = button.innerHTML;
        
        button.innerHTML = '<span class="dashicons dashicons-no"></span><span class="mixcloud-social-label">Failed</span>';
        
        setTimeout(function() {
            button.innerHTML = originalHTML;
        }, 2000);
    }
    
    /**
     * Initialize all functionality when DOM is ready
     */
    function init() {
        initMixcloudPlayers();
        initLazyLoadingImages();
        handleTableResponsiveness();
        initPlayCountAnimations();
        handleArtworkErrors();
        initDateFiltering();
        initPagination();
        initSocialSharing();
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-initialize on AJAX content updates (for compatibility with page builders)
    document.addEventListener('wp-mixcloud-archives-refresh', init);

})();

// Expose refresh function for external use
window.wpMixcloudArchivesRefresh = function() {
    document.dispatchEvent(new Event('wp-mixcloud-archives-refresh'));
};