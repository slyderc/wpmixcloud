#!/bin/bash
# WP Mixcloud Archives - Build Tools Setup Script
# 
# This script initializes the build system for asset minification.
# Run this once after cloning the repository or when setting up development environment.

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

log_header() {
    echo ""
    echo -e "${BLUE}============================================${NC}"
    echo -e "${BLUE} WP Mixcloud Archives - Build Tools Setup${NC}"
    echo -e "${BLUE}============================================${NC}"
    echo ""
}

# Check if we're in the right directory
check_directory() {
    if [[ ! -f "package.json" ]]; then
        log_error "Error: package.json not found. Are you in the build-tools directory?"
        log_info "Please run: cd build-tools && ./setup.sh"
        exit 1
    fi
    
    if [[ ! -f "../wp-mixcloud-archives.php" ]]; then
        log_error "Error: Plugin main file not found. Are you in the correct project?"
        exit 1
    fi
    
    log_success "Directory structure verified"
}

# Check Node.js version
check_node() {
    if ! command -v node &> /dev/null; then
        log_error "Node.js is not installed. Please install Node.js 14.0.0 or higher."
        log_info "Download from: https://nodejs.org/"
        exit 1
    fi
    
    NODE_VERSION=$(node --version | cut -d'v' -f2)
    REQUIRED_VERSION="14.0.0"
    
    if [[ "$(printf '%s\n' "$REQUIRED_VERSION" "$NODE_VERSION" | sort -V | head -n1)" != "$REQUIRED_VERSION" ]]; then
        log_error "Node.js version $NODE_VERSION is too old. Please upgrade to $REQUIRED_VERSION or higher."
        exit 1
    fi
    
    log_success "Node.js $NODE_VERSION detected"
}

# Check npm
check_npm() {
    if ! command -v npm &> /dev/null; then
        log_error "npm is not installed. Please install npm."
        exit 1
    fi
    
    NPM_VERSION=$(npm --version)
    log_success "npm $NPM_VERSION detected"
}

# Install dependencies
install_dependencies() {
    log_info "Installing build dependencies..."
    
    if npm install; then
        log_success "Dependencies installed successfully"
    else
        log_error "Failed to install dependencies"
        exit 1
    fi
}

# Test the build system
test_build() {
    log_info "Testing build system..."
    
    # Check if source files exist
    if [[ ! -f "../assets/css/style.css" ]]; then
        log_warning "Source CSS file not found: ../assets/css/style.css"
        log_info "This is normal for new projects. Create the file first, then run build."
    fi
    
    if [[ ! -f "../assets/js/script.js" ]]; then
        log_warning "Source JS file not found: ../assets/js/script.js"
        log_info "This is normal for new projects. Create the file first, then run build."
    fi
    
    # Test build script
    if node build.js --help > /dev/null 2>&1; then
        log_success "Build script is working correctly"
    else
        log_error "Build script test failed"
        exit 1
    fi
}

# Create .gitignore if needed
setup_gitignore() {
    GITIGNORE_FILE="../.gitignore"
    
    if [[ ! -f "$GITIGNORE_FILE" ]]; then
        log_info "Creating .gitignore file..."
        cat > "$GITIGNORE_FILE" << 'EOF'
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/upgrade/

# Build tools (development only)
/build-tools/node_modules/
/build-tools/.npm/
/build-tools/npm-debug.log*

# OS generated files
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
Thumbs.db
ehthumbs.db

# IDE files
.vscode/
.idea/
*.swp
*.swo
*~

# Logs
logs
*.log
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Environment variables
.env
.env.local
.env.development.local
.env.test.local
.env.production.local

# Keep minified files (they are part of distribution)
# !*.min.css
# !*.min.js
EOF
        log_success "Created .gitignore file"
    else
        log_info ".gitignore already exists"
        
        # Check if build-tools is already ignored
        if ! grep -q "/build-tools/node_modules/" "$GITIGNORE_FILE"; then
            log_info "Adding build-tools entries to .gitignore..."
            cat >> "$GITIGNORE_FILE" << 'EOF'

# Build tools (development only)
/build-tools/node_modules/
/build-tools/.npm/
/build-tools/npm-debug.log*

# Distribution packages (regenerated on build)
/dist/
EOF
            log_success "Updated .gitignore file"
        else
            log_success ".gitignore already contains build-tools entries"
        fi
    fi
}

# Show usage instructions
show_instructions() {
    echo ""
    echo -e "${GREEN}🎉 Build system setup complete!${NC}"
    echo ""
    echo -e "${BLUE}Available commands:${NC}"
    echo "  npm run build      - Build all assets (CSS + JS)"
    echo "  npm run build:css  - Build CSS only"
    echo "  npm run build:js   - Build JavaScript only"
    echo "  npm run watch      - Watch for changes and rebuild"
    echo "  npm run clean      - Remove all minified files"
    echo "  npm run package    - Create plugin distribution package"
    echo "  npm run package:zip - Create plugin package + zip archive"
    echo ""
    echo -e "${BLUE}Quick start:${NC}"
    echo "  1. Create/edit your source files:"
    echo "     - ../assets/css/style.css"
    echo "     - ../assets/js/script.js"
    echo ""
    echo "  2. Build minified versions:"
    echo "     npm run build"
    echo ""
    echo "  3. For development (auto-rebuild on changes):"
    echo "     npm run watch"
    echo ""
    echo -e "${BLUE}Documentation:${NC}"
    echo "  Read README.md for detailed usage instructions"
    echo ""
    echo -e "${YELLOW}💡 Pro tip: Use 'npm run watch' during development!${NC}"
    echo ""
}

# Main execution
main() {
    log_header
    
    log_info "Checking environment..."
    check_directory
    check_node
    check_npm
    
    echo ""
    install_dependencies
    
    echo ""
    test_build
    
    echo ""
    setup_gitignore
    
    show_instructions
}

# Error handling
trap 'echo ""; log_error "Setup failed. Please check the error messages above."' ERR

# Run main function
main "$@"