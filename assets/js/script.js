/**
 * WP Mixcloud Archives - Frontend Scripts (Modal Player)
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

    // Store current modal and player reference
    var currentModal = null;
    var currentPlayer = null;

    /**
     * Initialize Mixcloud modal player functionality
     */
    function initMixcloudPlayers() {
        // AIDEV-NOTE: Use event delegation for dynamic content and theme compatibility
        // Remove any existing handlers to prevent duplicates
        $(document).off('click.wpmixcloud', '.mixcloud-play-button');
        $(document).off('click.wpmixcloud', '.mixcloud-modal-close');
        $(document).off('click.wpmixcloud', '.mixcloud-modal-overlay');
        
        // Debug: Log how many play buttons are found
        var playButtons = $('.mixcloud-play-button');
        if (window.console && window.console.log) {
            console.log('WP Mixcloud Archives: Found ' + playButtons.length + ' play buttons');
        }
        
        // Attach play button handler with better event handling
        $(document).on('click.wpmixcloud', '.mixcloud-play-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            if (window.console && window.console.log) {
                console.log('WP Mixcloud Archives: Play button clicked');
            }
            
            const button = this;
            const $button = $(button);
            
            // Ensure button is not disabled
            if ($button.hasClass('disabled') || $button.prop('disabled')) {
                if (window.console && window.console.log) {
                    console.log('WP Mixcloud Archives: Button is disabled, ignoring click');
                }
                return false;
            }
            
            const cloudcastUrl = $button.attr('data-cloudcast-url') || $button.data('cloudcast-url');
            const cloudcastName = $button.attr('data-cloudcast-name') || $button.data('cloudcast-name');
            const cloudcastKey = $button.attr('data-cloudcast-key') || $button.data('cloudcast-key');
            const cloudcastImage = $button.attr('data-cloudcast-image') || $button.data('cloudcast-image');
            
            if (window.console && window.console.log) {
                console.log('WP Mixcloud Archives: Button data - Key:', cloudcastKey, 'URL:', cloudcastUrl);
            }
            
            if (cloudcastUrl && cloudcastKey) {
                openPlayerModal(cloudcastUrl, cloudcastName, cloudcastKey, cloudcastImage);
            } else {
                if (window.console && window.console.log) {
                    console.log('WP Mixcloud Archives: Missing required data attributes');
                }
            }
            
            return false;
        });
        
        // Attach modal close handlers
        $(document).on('click.wpmixcloud', '.mixcloud-modal-close', function(e) {
            e.preventDefault();
            closePlayerModal();
            return false;
        });
        
        $(document).on('click.wpmixcloud', '.mixcloud-modal-overlay', function(e) {
            e.preventDefault();
            closePlayerModal();
            return false;
        });
        
        // Close modal on Escape key
        $(document).on('keydown.wpmixcloud', function(e) {
            if (e.key === 'Escape' && currentModal) {
                closePlayerModal();
            }
        });
    }
    
    /**
     * Open player modal with Mixcloud iframe
     */
    function openPlayerModal(url, name, key, imageUrl) {
        const modal = $('#mixcloud-player-modal');
        if (!modal.length) {
            console.error('WP Mixcloud Archives: Modal container not found');
            return;
        }
        
        // Close any existing modal first
        if (currentModal) {
            closePlayerModal();
        }
        
        // Set up modal image
        const modalImage = modal.find('.mixcloud-modal-image');
        if (imageUrl && imageUrl !== '') {
            modalImage.attr('src', imageUrl).attr('alt', name || 'Show artwork');
        } else {
            // Use fallback or hide image container
            modalImage.attr('src', '').attr('alt', '');
        }
        
        // Build embed URL - show mini player with controls
        const embedParams = {
            feed: encodeURIComponent(key),
            mini: '1', // Use mini player
            light: '0', // Dark theme player
            hide_cover: '1', // Hide cover in player since we show it separately
            hide_artwork: '1' // Hide artwork in player
        };
        
        const paramString = Object.keys(embedParams).map(key => key + '=' + embedParams[key]).join('&');
        const embedUrl = 'https://www.mixcloud.com/widget/iframe/?' + paramString;
        
        // Create iframe
        const iframe = $('<iframe>')
            .attr('src', embedUrl)
            .attr('width', '100%')
            .attr('height', '120')
            .attr('frameborder', '0')
            .attr('allowfullscreen', true)
            .attr('title', 'Mixcloud player for ' + (name || 'show'));
        
        // Insert iframe into modal
        const playerContainer = modal.find('.mixcloud-modal-player-container');
        playerContainer.empty().append(iframe);
        
        // Show modal
        modal.addClass('active');
        currentModal = modal;
        currentPlayer = iframe[0];
        
        // Prevent body scroll
        $('body').addClass('mixcloud-modal-open');
        
        // Focus management for accessibility
        modal.attr('tabindex', '-1').focus();
    }
    
    /**
     * Close player modal and stop playback
     */
    function closePlayerModal() {
        if (!currentModal) return;
        
        // Stop player by removing iframe
        if (currentPlayer) {
            $(currentPlayer).remove();
            currentPlayer = null;
        }
        
        // Hide modal
        currentModal.removeClass('active');
        currentModal = null;
        
        // Restore body scroll
        $('body').removeClass('mixcloud-modal-open');
        
        // Return focus to the page
        $(document).focus();
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
            
            const $dropdown = $(this).closest('.mixcloud-custom-dropdown');
            const $options = $dropdown.find('.mixcloud-dropdown-options');
            const isOpen = $dropdown.hasClass('open');
            
            // Close all other dropdowns first
            $('.mixcloud-custom-dropdown').removeClass('open');
            $('.mixcloud-dropdown-options').hide();
            
            if (!isOpen) {
                $dropdown.addClass('open');
                $options.show();
                
                // Update ARIA attributes
                $(this).attr('aria-expanded', 'true');
                $options.attr('aria-hidden', 'false');
            } else {
                $(this).attr('aria-expanded', 'false');
                $options.attr('aria-hidden', 'true');
            }
        });
        
        // Handle option selection
        $(document).on('click.wpmixcloud-dropdown', '.mixcloud-dropdown-option', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $option = $(this);
            const $dropdown = $option.closest('.mixcloud-custom-dropdown');
            const $selected = $dropdown.find('.mixcloud-dropdown-selected');
            const $options = $dropdown.find('.mixcloud-dropdown-options');
            const value = $option.data('value');
            const text = $option.text();
            
            // Update selected display
            $selected.find('.mixcloud-dropdown-text').text(text);
            $dropdown.attr('data-current-filter', value);
            
            // Update active option
            $options.find('.mixcloud-dropdown-option').removeClass('mixcloud-dropdown-option-active').attr('aria-selected', 'false');
            $option.addClass('mixcloud-dropdown-option-active').attr('aria-selected', 'true');
            
            // Close dropdown
            $dropdown.removeClass('open');
            $options.hide();
            $selected.attr('aria-expanded', 'false');
            $options.attr('aria-hidden', 'true');
            
            // Apply filter
            applyShowFilter(value);
        });
        
        // Close dropdown when clicking outside
        $(document).on('click.wpmixcloud-dropdown', function(e) {
            if (!$(e.target).closest('.mixcloud-custom-dropdown').length) {
                $('.mixcloud-custom-dropdown').removeClass('open');
                $('.mixcloud-dropdown-options').hide();
                $('.mixcloud-dropdown-selected').attr('aria-expanded', 'false');
                $('.mixcloud-dropdown-options').attr('aria-hidden', 'true');
            }
        });
        
        // Keyboard navigation for accessibility
        $(document).on('keydown.wpmixcloud-dropdown', '.mixcloud-dropdown-selected', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        $(document).on('keydown.wpmixcloud-dropdown', '.mixcloud-dropdown-option', function(e) {
            const $options = $(this).closest('.mixcloud-dropdown-options');
            const $allOptions = $options.find('.mixcloud-dropdown-option');
            const currentIndex = $allOptions.index(this);
            
            switch(e.key) {
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    $(this).click();
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    const nextIndex = (currentIndex + 1) % $allOptions.length;
                    $allOptions.eq(nextIndex).focus();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    const prevIndex = (currentIndex - 1 + $allOptions.length) % $allOptions.length;
                    $allOptions.eq(prevIndex).focus();
                    break;
                case 'Escape':
                    e.preventDefault();
                    const $dropdown = $(this).closest('.mixcloud-custom-dropdown');
                    $dropdown.removeClass('open');
                    $options.hide();
                    $dropdown.find('.mixcloud-dropdown-selected').focus();
                    break;
            }
        });
    }
    
    /**
     * Apply show filter based on selected value
     */
    function applyShowFilter(filterValue) {
        const $listItems = $('.mixcloud-list-item');
        
        if (filterValue === 'all') {
            // Show all items
            $listItems.show();
        } else {
            // Filter items by show title
            $listItems.each(function() {
                const $item = $(this);
                const title = $item.find('.mixcloud-list-title').text();
                
                // Normalize title for comparison (convert en dash and bullet to regular dash)
                const normalizedTitle = title.replace(/[–•]/g, '-');
                const normalizedFilter = filterValue.replace(/[–•]/g, '-');
                
                if (normalizedTitle.toLowerCase().includes(normalizedFilter.toLowerCase())) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        }
        
        // Trigger custom event for other scripts
        $(document).trigger('wp-mixcloud-filter-applied', [filterValue]);
    }

    /**
     * Initialize immediately and handle various loading scenarios
     */
    function initializePlugin() {
        initMixcloudPlayers();
        initFilters();
    }
    
    // Multiple initialization strategies to ensure the plugin works
    // 1. Immediate execution (in case DOM is already ready)
    if (document.readyState === 'loading') {
        // DOM is still loading
        $(document).ready(initializePlugin);
    } else {
        // DOM is already ready
        initializePlugin();
    }
    
    // 2. DOM ready event (standard)
    $(document).ready(initializePlugin);
    
    // 3. Window load event (everything including images loaded)
    $(window).on('load', initializePlugin);
    
    // 4. Delayed initialization to catch late-loaded content
    setTimeout(initializePlugin, 100);
    setTimeout(initializePlugin, 500);
    setTimeout(initializePlugin, 1000);
    
    // 5. Watch for AJAX content updates
    $(document).on('wp-mixcloud-refresh', initializePlugin);
    
    // 6. Watch for DOM mutations (dynamically added content)
    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            var shouldReinit = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && ($(node).hasClass('mixcloud-play-button') || $(node).find('.mixcloud-play-button').length)) {
                            shouldReinit = true;
                        }
                    });
                }
            });
            if (shouldReinit) {
                setTimeout(initMixcloudPlayers, 100);
            }
        });
        
        // Start observing when DOM is ready
        $(document).ready(function() {
            if (document.body) {
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        });
    }
    
    // 7. Global refresh function for external use
    window.wpMixcloudArchivesRefresh = initializePlugin;
    
    // 8. Force initialization on any user interaction (failsafe)
    $(document).one('click touchstart', function() {
        setTimeout(initializePlugin, 50);
    });

})(jQuery);