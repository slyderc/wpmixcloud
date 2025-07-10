/**
 * WP Mixcloud Archives - Frontend Scripts (Production Fix)
 *
 * @package WPMixcloudArchives
 */

(function($) {
    'use strict';
    
    // AIDEV-NOTE: Namespace all jQuery operations for theme compatibility
    var $ = window.jQuery || window.$;
    if (!$) {
        console.error('WP Mixcloud Archives: jQuery not found');
        return;
    }

    // Store current player reference globally within plugin scope
    var currentlyPlaying = null;

    /**
     * Initialize Mixcloud inline player functionality
     */
    function initMixcloudPlayers() {
        // AIDEV-NOTE: Use event delegation for dynamic content and theme compatibility
        // Remove any existing handlers to prevent duplicates
        $(document).off('click.wpmixcloud', '.mixcloud-play-button');
        
        // Attach new handler with namespace
        $(document).on('click.wpmixcloud', '.mixcloud-play-button', function(e) {
            e.preventDefault();
            e.stopPropagation(); // AIDEV-NOTE: Prevent theme event conflicts
            e.stopImmediatePropagation(); // AIDEV-NOTE: Stop all other handlers
            
            const button = this;
            const $listItem = $(button).closest('.mixcloud-list-item');
            const $waveformContainer = $listItem.find('.mixcloud-list-waveform').first();
            const cloudcastUrl = $(button).attr('data-cloudcast-url');
            const cloudcastName = $(button).attr('data-cloudcast-name');
            const cloudcastKey = $(button).attr('data-cloudcast-key');
            
            if ($waveformContainer.length && cloudcastUrl && cloudcastKey) {
                toggleInlinePlayer($listItem[0], $waveformContainer[0], cloudcastUrl, cloudcastName, cloudcastKey);
            }
            
            return false; // Extra safety
        });
        
        // Function to toggle inline player
        function toggleInlinePlayer(listItem, container, url, name, key) {
            const hasPlayer = container.querySelector('iframe.mixcloud-player-iframe');
            
            // Close any currently open player
            if (currentlyPlaying && currentlyPlaying !== container) {
                closeInlinePlayer(currentlyPlaying);
            }
            
            if (hasPlayer) {
                // Close this player
                closeInlinePlayer(container);
                currentlyPlaying = null;
            } else {
                // Open this player
                openInlinePlayer(container, url, name, key);
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
        function openInlinePlayer(container, url, name, key) {
            // Store original waveform content
            const originalContent = container.innerHTML;
            container.setAttribute('data-original-content', originalContent);
            
            // Build embed URL - use the cloudcast key as feed parameter
            const embedParams = {
                feed: encodeURIComponent(key),
                hide_cover: '1',
                mini: '1', // Use mini player
                light: '0', // Dark theme player
                hide_artwork: '1', // Hide artwork in player
                hide_follow: '1' // Hide follow button for cleaner look
                // Note: Removed autoplay for Safari compatibility
            };
            
            const paramString = Object.keys(embedParams).map(key => key + '=' + embedParams[key]).join('&');
            const embedUrl = 'https://www.mixcloud.com/widget/iframe/?' + paramString;
            
            // Create iframe
            const iframe = document.createElement('iframe');
            iframe.src = embedUrl;
            iframe.className = 'mixcloud-player-iframe';
            iframe.width = '100%';
            iframe.height = '60'; // Mini player height
            iframe.frameBorder = '0';
            iframe.allowFullscreen = true;
            iframe.title = 'Mixcloud player for ' + name;
            
            // Clear container and add iframe immediately
            container.innerHTML = '';
            container.appendChild(iframe);
            container.classList.add('mixcloud-player-active');
            
            // Animate in
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
                // Restore original waveform content
                const originalContent = container.getAttribute('data-original-content');
                if (originalContent) {
                    container.innerHTML = originalContent;
                    container.removeAttribute('data-original-content');
                }
                
                // Remove active class
                container.classList.remove('mixcloud-player-active');
                
                // Reset opacity
                container.style.opacity = '1';
            }, 300);
        }
    }

    /**
     * Initialize filter functionality with better event handling
     */
    function initFilters() {
        // AIDEV-NOTE: Use event delegation for dropdown handling
        $(document).off('click.wpmixcloud-dropdown');
        
        // Handle dropdown toggle clicks
        $(document).on('click.wpmixcloud-dropdown', '.mixcloud-dropdown-selected', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const $dropdown = $(this).closest('.mixcloud-custom-dropdown');
            const $selected = $(this);
            const $options = $dropdown.find('.mixcloud-dropdown-options');
            const isOpen = $selected.attr('aria-expanded') === 'true';
            
            if (isOpen) {
                closeDropdown($selected, $options);
            } else {
                openDropdown($selected, $options);
            }
            
            return false;
        });
        
        // Handle option clicks
        $(document).on('click.wpmixcloud-dropdown', '.mixcloud-dropdown-option', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const $option = $(this);
            const $dropdown = $option.closest('.mixcloud-custom-dropdown');
            const $selected = $dropdown.find('.mixcloud-dropdown-selected');
            const $options = $dropdown.find('.mixcloud-dropdown-options');
            const $text = $dropdown.find('.mixcloud-dropdown-text');
            
            selectOption($option, $dropdown, $selected, $options, $text);
            
            return false;
        });
        
        // Close dropdowns on outside click
        $(document).on('click.wpmixcloud-outside', function(e) {
            if (!$(e.target).closest('.mixcloud-custom-dropdown').length) {
                $('.mixcloud-dropdown-selected[aria-expanded="true"]').each(function() {
                    const $selected = $(this);
                    const $options = $selected.siblings('.mixcloud-dropdown-options');
                    closeDropdown($selected, $options);
                });
            }
        });
        
        function openDropdown($selected, $options) {
            $selected.attr('aria-expanded', 'true');
            $options.addClass('mixcloud-dropdown-open');
            
            // Focus first option
            const $firstOption = $options.find('.mixcloud-dropdown-option').first();
            if ($firstOption.length) {
                $firstOption.focus();
            }
        }
        
        function closeDropdown($selected, $options) {
            $selected.attr('aria-expanded', 'false');
            $options.removeClass('mixcloud-dropdown-open');
            $selected.focus();
        }
        
        function selectOption($option, $dropdown, $selected, $options, $text) {
            const value = $option.attr('data-value');
            const optionText = $option.text();
            
            // Update selected display
            $text.text(optionText);
            
            // Update active state
            $dropdown.find('.mixcloud-dropdown-option').removeClass('mixcloud-dropdown-option-active').attr('aria-selected', 'false');
            $option.addClass('mixcloud-dropdown-option-active').attr('aria-selected', 'true');
            
            // Update data attribute
            $dropdown.attr('data-current-filter', value);
            
            // Apply filter
            applyFilter(value);
            
            // Close dropdown
            closeDropdown($selected, $options);
        }
        
        function applyFilter(filter) {
            const listItems = document.querySelectorAll('.mixcloud-list-item');
            let matchCount = 0;
            
            listItems.forEach(item => {
                const title = item.querySelector('.mixcloud-list-title').textContent;
                
                // RIGHT-TO-LEFT APPROACH: Work backwards from the end to find dates
                // Match the PHP implementation
                let cleanTitle = title;
                const separators = ['•', '–', '-', '|', ':'];
                let dateRemoved = false;
                
                for (const sep of separators) {
                    if (dateRemoved) break;
                    
                    const lastPos = cleanTitle.lastIndexOf(sep);
                    if (lastPos !== -1) {
                        const beforeSep = cleanTitle.substring(0, lastPos).trim();
                        const afterSep = cleanTitle.substring(lastPos + sep.length).trim();
                        
                        // Check if what's after the separator looks like a date
                        const datePatterns = [
                            /^(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01])-(20\d{2}|19\d{2})$/,  // MM-DD-YYYY
                            /^\d{1,2}\/\d{1,2}\/\d{2,4}$/,                                        // M/D/YY
                            /^\d{4}-\d{1,2}-\d{1,2}$/,                                           // YYYY-MM-DD
                            /^\d{1,2}\.\d{1,2}\.\d{2,4}$/,                                       // M.D.YY
                            /^(0?[1-9]|1[0-2])–(0?[1-9]|[12][0-9]|3[01])-(20\d{2}|19\d{2})$/,  // MM–DD-YYYY
                            /^(20\d{2}|19\d{2})$/                                                // Just year
                        ];
                        
                        for (const pattern of datePatterns) {
                            if (pattern.test(afterSep)) {
                                cleanTitle = beforeSep;
                                dateRemoved = true;
                                break;
                            }
                        }
                    }
                }
                
                // Normalize characters for consistent filtering - convert en dash and bullet to regular dash
                cleanTitle = cleanTitle.replace(/[–•]/g, '-');
                
                if (filter === 'all' || cleanTitle === filter) {
                    item.style.display = 'flex';
                    matchCount++;
                } else {
                    item.style.display = 'none';
                }
            });
        }
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
        initSocialSharing();
    }

    // AIDEV-NOTE: Use jQuery document ready for theme compatibility
    $(document).ready(function() {
        // Small delay to ensure theme scripts have initialized
        setTimeout(init, 100);
    });

    // Re-initialize on AJAX content updates (for compatibility with page builders)
    $(document).on('wp-mixcloud-archives-refresh', init);

})(window.jQuery);

// Expose refresh function for external use
window.wpMixcloudArchivesRefresh = function() {
    jQuery(document).trigger('wp-mixcloud-archives-refresh');
};