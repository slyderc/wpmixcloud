<?php
/**
 * Test configuration for WP Mixcloud Archives
 */

// Test Mixcloud account for testing (should be a real, public account)
define('TEST_MIXCLOUD_ACCOUNT', 'NowWaveRadio');

// Test configuration
define('WP_TESTS_DOMAIN', 'localhost:8888');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'WP Mixcloud Archives Test Site');

// Test API endpoints (use real Mixcloud API)
define('TEST_API_BASE', 'https://api.mixcloud.com');

// Skip tests that require network access if needed
define('WP_TESTS_SKIP_NETWORK', false);

// Enable debugging for tests
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);