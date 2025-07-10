/**
 * WP Mixcloud Archives - Frontend Scripts
 *
 * @package WPMixcloudArchives
 */

(function() {
    'use strict';

    /**
     * Initialize Mixcloud inline player functionality
     */
    function initMixcloudPlayers() {
        const playButtons = document.querySelectorAll('.mixcloud-play-button');
        let currentlyPlaying = null;
        
        // Add click handlers to play buttons
        playButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const listItem = button.closest('.mixcloud-list-item');
                const playerContainer = listItem.querySelector('.mixcloud-inline-player');
                const cloudcastUrl = button.getAttribute('data-cloudcast-url');
                const cloudcastName = button.getAttribute('data-cloudcast-name');
                const cloudcastKey = button.getAttribute('data-cloudcast-key');
                
                if (playerContainer && cloudcastUrl) {
                    toggleInlinePlayer(listItem, playerContainer, cloudcastUrl, cloudcastName, cloudcastKey);
                }
            });
        });
        
        // Function to toggle inline player
        function toggleInlinePlayer(listItem, container, url, name, key) {
            const isCurrentlyOpen = container.style.display === 'block';
            
            // Close any currently open player
            if (currentlyPlaying && currentlyPlaying !== container) {
                closeInlinePlayer(currentlyPlaying);
            }
            
            if (isCurrentlyOpen) {
                // Close this player
                closeInlinePlayer(container);
                currentlyPlaying = null;
            } else {
                // Open this player
                openInlinePlayer(container, url, name);
                currentlyPlaying = container;
                
                // Scroll to player if needed
                setTimeout(() => {
                    const rect = listItem.getBoundingClientRect();
                    if (rect.bottom > window.innerHeight) {
                        listItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            }
        }
        
        // Open inline player
        function openInlinePlayer(container, url, name) {
            // Build embed URL - use the full URL as feed parameter
            const embedParams = {
                feed: encodeURIComponent(url),
                hide_cover: '1',
                mini: '1', // Use mini player
                light: '0', // Dark theme player
                hide_artwork: '1' // Hide artwork in player
                // Note: Removed autoplay for Safari compatibility
            };
            
            const paramString = Object.keys(embedParams).map(key => key + '=' + embedParams[key]).join('&');
            const embedUrl = 'https://www.mixcloud.com/widget/iframe/?' + paramString;
            
            // Create iframe
            const iframe = document.createElement('iframe');
            iframe.src = embedUrl;
            iframe.width = '100%';
            iframe.height = '60'; // Reduced height for mini player
            iframe.frameBorder = '0';
            iframe.allowFullscreen = true;
            iframe.title = 'Mixcloud player for ' + name;
            
            // Clear container and add iframe
            container.innerHTML = '';
            container.appendChild(iframe);
            
            // Show container with animation
            container.style.display = 'block';
            container.style.opacity = '0';
            setTimeout(() => {
                container.style.transition = 'opacity 0.3s ease';
                container.style.opacity = '1';
            }, 10);
        }
        
        // Close inline player
        function closeInlinePlayer(container) {
            container.style.opacity = '0';
            setTimeout(() => {
                container.style.display = 'none';
                container.innerHTML = '';
            }, 300);
        }
    }

    /**
     * Initialize filter functionality
     */
    function initFilters() {
        const customDropdowns = document.querySelectorAll('.mixcloud-custom-dropdown');
        
        customDropdowns.forEach(dropdown => {
            const selected = dropdown.querySelector('.mixcloud-dropdown-selected');
            const options = dropdown.querySelector('.mixcloud-dropdown-options');
            const optionItems = dropdown.querySelectorAll('.mixcloud-dropdown-option');
            const text = dropdown.querySelector('.mixcloud-dropdown-text');
            
            if (!selected || !options || !text) return;
            
            // Toggle dropdown open/close
            function toggleDropdown() {
                const isOpen = selected.getAttribute('aria-expanded') === 'true';
                
                if (isOpen) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            }
            
            function openDropdown() {
                selected.setAttribute('aria-expanded', 'true');
                options.classList.add('mixcloud-dropdown-open');
                
                // Focus first option
                const firstOption = options.querySelector('.mixcloud-dropdown-option');
                if (firstOption) {
                    firstOption.focus();
                }
                
                // Close on outside click
                setTimeout(() => {
                    document.addEventListener('click', outsideClickHandler);
                }, 0);
            }
            
            function closeDropdown() {
                selected.setAttribute('aria-expanded', 'false');
                options.classList.remove('mixcloud-dropdown-open');
                document.removeEventListener('click', outsideClickHandler);
                selected.focus();
            }
            
            function outsideClickHandler(e) {
                if (!dropdown.contains(e.target)) {
                    closeDropdown();
                }
            }
            
            function selectOption(option) {
                const value = option.getAttribute('data-value');
                const optionText = option.textContent;
                
                // Update selected display
                text.textContent = optionText;
                
                // Update active state
                optionItems.forEach(opt => {
                    opt.classList.remove('mixcloud-dropdown-option-active');
                    opt.setAttribute('aria-selected', 'false');
                });
                option.classList.add('mixcloud-dropdown-option-active');
                option.setAttribute('aria-selected', 'true');
                
                // Update data attribute
                dropdown.setAttribute('data-current-filter', value);
                
                // Apply filter
                applyFilter(value);
                
                // Close dropdown
                closeDropdown();
            }
            
            function applyFilter(filter) {
                const listItems = document.querySelectorAll('.mixcloud-list-item');
                
                listItems.forEach(item => {
                    const title = item.querySelector('.mixcloud-list-title').textContent;
                    const cleanTitle = title.replace(/\s*[–-•]\s*\d{1,2}\/\d{1,2}\/\d{4}$/, '')        // MM/DD/YYYY or M/D/YYYY
                                           .replace(/\s*[–-•]\s*\d{4}-\d{2}-\d{2}$/, '')             // YYYY-MM-DD
                                           .replace(/\s*[–-•]\s*\d{1,2}-\d{1,2}-\d{4}$/, '')        // MM-DD-YYYY or M-D-YYYY
                                           .trim();
                    
                    if (filter === 'all' || cleanTitle === filter) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
                
            }
            
            // Click events
            selected.addEventListener('click', toggleDropdown);
            
            optionItems.forEach(option => {
                option.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectOption(option);
                });
            });
            
            // Keyboard navigation
            selected.addEventListener('keydown', (e) => {
                switch (e.key) {
                    case 'Enter':
                    case ' ':
                    case 'ArrowDown':
                        e.preventDefault();
                        openDropdown();
                        break;
                    case 'Escape':
                        closeDropdown();
                        break;
                }
            });
            
            options.addEventListener('keydown', (e) => {
                const focusedOption = document.activeElement;
                const currentIndex = Array.from(optionItems).indexOf(focusedOption);
                
                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        if (currentIndex < optionItems.length - 1) {
                            optionItems[currentIndex + 1].focus();
                        }
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        if (currentIndex > 0) {
                            optionItems[currentIndex - 1].focus();
                        }
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        if (focusedOption && focusedOption.classList.contains('mixcloud-dropdown-option')) {
                            selectOption(focusedOption);
                        }
                        break;
                    case 'Escape':
                        e.preventDefault();
                        closeDropdown();
                        break;
                }
            });
            
            // Make options focusable
            optionItems.forEach(option => {
                option.setAttribute('tabindex', '-1');
            });
        });
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
     * Cross-browser AJAX request helper with timeout and retry logic
     * Falls back to XMLHttpRequest for older Safari versions
     * 
     * @param {string} url Request URL
     * @param {Object} data Request data
     * @param {Object} options Request options (timeout, retries, signal)
     * @returns {Promise} Promise that resolves to response data
     */
    function makeAjaxRequest(url, data, options = {}) {
        const {
            timeout = 10000,
            retries = 2,
            signal
        } = options;
        
        // Check if fetch is available (Safari 10.1+)
        if (typeof fetch !== 'undefined' && typeof AbortController !== 'undefined') {
            return makeFetchRequest(url, data, options);
        } else {
            // Fallback to XMLHttpRequest for older browsers
            return makeXHRRequest(url, data, options);
        }
    }
    
    /**
     * Modern fetch-based request
     */
    async function makeFetchRequest(url, data, options = {}) {
        const {
            timeout = 10000,
            retries = 2,
            signal
        } = options;
        
        let lastError;
        
        for (let attempt = 0; attempt <= retries; attempt++) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), timeout);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data),
                    signal: signal || controller.signal
                });
                
                clearTimeout(timeoutId);
                
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
     * XMLHttpRequest fallback for older browsers
     */
    function makeXHRRequest(url, data, options = {}) {
        const {
            timeout = 10000,
            retries = 2
        } = options;
        
        return new Promise((resolve, reject) => {
            let attempt = 0;
            
            function tryRequest() {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.timeout = timeout;
                
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const responseData = JSON.parse(xhr.responseText);
                            resolve(responseData);
                        } catch (e) {
                            reject(new Error('Invalid JSON response'));
                        }
                    } else {
                        const error = new Error(`HTTP ${xhr.status}: ${xhr.statusText}`);
                        handleError(error);
                    }
                };
                
                xhr.onerror = function() {
                    handleError(new Error('Network error'));
                };
                
                xhr.ontimeout = function() {
                    handleError(new Error('Request timeout'));
                };
                
                function handleError(error) {
                    if (attempt < retries) {
                        attempt++;
                        const delay = Math.min(1000 * Math.pow(2, attempt - 1), 5000);
                        setTimeout(tryRequest, delay);
                    } else {
                        reject(error);
                    }
                }
                
                // Convert data object to form-encoded string
                const formData = Object.keys(data).map(key => 
                    encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
                ).join('&');
                
                xhr.send(formData);
            }
            
            tryRequest();
        });
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
                initFilters();
                
                // Show success message (optional)
                showFilterMessage(container, data.data.message, 'success');
            } else {
                // Show error message
                showFilterMessage(container, data.data.message || 'An error occurred while filtering.', 'error');
            }
        })
        .catch(error => {
            if (error.name !== 'AbortError') {
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
        initFilters();
        initLazyLoadingImages();
        initPlayCountAnimations();
        handleArtworkErrors();
        initDateFiltering();
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