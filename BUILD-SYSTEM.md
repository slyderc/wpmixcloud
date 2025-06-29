# Build System Documentation

## Overview

This project includes an automated build system for minifying CSS and JavaScript assets. The build tools are located in the `build-tools/` directory and are **for development use only** - they should not be included in plugin distributions.

## Quick Start

### 1. One-time setup
```bash
cd build-tools
./setup.sh
```

### 2. Build assets
```bash
cd build-tools
npm run build
```

### 3. Development workflow
```bash
cd build-tools
npm run watch
```

## What This System Does

### Automated Minification
- **CSS**: `assets/css/style.css` → `assets/css/style.min.css`
- **JavaScript**: `assets/js/script.js` → `assets/js/script.min.js`

### Key Features
- ✅ Real-time file watching and rebuilding
- ✅ Compression ratio reporting
- ✅ Error handling and validation
- ✅ Selective building (CSS-only or JS-only)
- ✅ Clean command to remove minified files
- ✅ WordPress-optimized settings

### WordPress Integration
The build system is specifically configured for WordPress plugin development:

- **Preserves important globals**: `wpMixcloudArchives`, `wpMixcloudArchivesRefresh`
- **Keeps debugging**: console.log, console.warn, console.error
- **Removes**: console.debug, console.info in production
- **Optimizes**: File sizes while maintaining functionality

## Available Commands

```bash
# Build all assets
npm run build

# Build only CSS
npm run build:css

# Build only JavaScript  
npm run build:js

# Watch for changes (recommended for development)
npm run watch

# Remove all minified files
npm run clean

# Create plugin distribution package
npm run package

# Create plugin package + zip archive
npm run package:zip
```

## Plugin Packaging

The build system includes automated plugin packaging for distribution:

### Creating Distribution Packages

```bash
# Build assets and create clean plugin directory
cd build-tools
npm run package

# Build assets and create plugin directory + zip archive
cd build-tools  
npm run package:zip
```

### What Gets Packaged

✅ **Included Files:**
- All PHP files (plugin core functionality)
- Minified CSS and JS (automatically built first)
- Asset files (images, fonts, etc.)
- WordPress-specific files (readme.txt, languages/)
- Documentation (LICENSE, CHANGELOG.md)

❌ **Excluded Files:**
- **Everything in .gitignore** - Automatically uses your existing ignore rules
- **Additional distribution excludes** - Files appropriate for git but not distribution:
  - Development documentation (`BUILD-SYSTEM.md`, `PRD.md`)
  - AI assistant files (`CLAUDE.md`, `AGENTS.md`)
  - Development tool configs (`.taskmaster/`, `.claude/`, `.cursor/`)

### Automatic Processing

The packaging system automatically:

1. **Builds fresh assets** - Ensures minified files are current
2. **Reads plugin version** - From main PHP file header
3. **Respects .gitignore** - Uses your existing ignore rules (DRY principle)
4. **Creates clean directory** - Only distribution files included
5. **Validates critical files** - Ensures required files present
6. **Generates zip archive** - Ready for WordPress installation

### Output Structure

```
dist/
├── wp-mixcloud-archives-1.0.0/     # Clean plugin directory
│   ├── wp-mixcloud-archives.php    # Main plugin file
│   ├── assets/
│   │   ├── css/style.min.css       # Minified CSS
│   │   └── js/script.min.js        # Minified JS
│   ├── includes/                   # Plugin classes
│   ├── admin/                      # Admin functionality
│   └── readme.txt                  # WordPress plugin info
└── wp-mixcloud-archives-1.0.0.zip  # Installation-ready archive
```

## Development Workflow

### During Development
1. Run `npm run watch` in the `build-tools/` directory
2. Edit your source files (`assets/css/style.css`, `assets/js/script.js`)
3. Minified files are automatically updated when you save

### Before Committing
```bash
cd build-tools
npm run build
cd ..
git add assets/css/style.css assets/css/style.min.css
git add assets/js/script.js assets/js/script.min.js
git commit -m "Update styles and rebuild assets"
```

## File Structure

```
wp-mixcloud-archives/
├── build-tools/              # Build system (DEV ONLY - DO NOT DISTRIBUTE)
│   ├── package.json         # Dependencies
│   ├── build.js            # Main build script
│   ├── setup.sh            # One-time setup script
│   ├── README.md           # Detailed documentation
│   └── node_modules/       # Dependencies (auto-generated)
├── assets/
│   ├── css/
│   │   ├── style.css       # Source CSS (edit this)
│   │   └── style.min.css   # Minified CSS (auto-generated)
│   └── js/
│       ├── script.js       # Source JavaScript (edit this)
│       └── script.min.js   # Minified JS (auto-generated)
└── wp-mixcloud-archives.php
```

## Why Use This System?

### Before (Manual Process)
- ❌ Copy content to online minifiers
- ❌ Manually paste minified code back
- ❌ Maintain separate exclusion lists
- ❌ Prone to human error
- ❌ Time-consuming
- ❌ Inconsistent results
- ❌ Easy to forget updating minified files

### After (Automated System)
- ✅ Single command builds everything
- ✅ Watch mode rebuilds automatically
- ✅ **Respects .gitignore (DRY principle)**
- ✅ Consistent compression settings
- ✅ Error reporting and validation
- ✅ File size reports
- ✅ Zero human error in minification
- ✅ Can be integrated into CI/CD

### DRY Principle Implementation
The system follows **Don't Repeat Yourself** principles:
- **Single source of truth** - `.gitignore` defines what's excluded
- **No duplicate lists** - Don't maintain packaging exclusions separately
- **Automatic consistency** - Changes to `.gitignore` apply to packaging
- **Standard practices** - Works with any git workflow

## Distribution Notes

### What to Include in Plugin Distribution
```
✅ assets/css/style.css       (source)
✅ assets/css/style.min.css   (minified)
✅ assets/js/script.js        (source)  
✅ assets/js/script.min.js    (minified)
✅ wp-mixcloud-archives.php   (plugin file)
✅ includes/                  (plugin classes)
✅ admin/                     (admin classes)
```

### What NOT to Include in Plugin Distribution
```
❌ build-tools/               (entire directory)
❌ dist/                      (build outputs - use contents only)
❌ BUILD-SYSTEM.md           (this file)
❌ package.json              (if in root)
❌ node_modules/             (anywhere)
❌ .npm/                     (cache files)
```

**💡 Note**: Instead of manually managing distribution files, use `npm run package:zip` to create clean, ready-to-install plugin archives automatically!

## Requirements

- **Node.js**: 14.0.0 or higher
- **npm**: Latest stable version
- **Operating System**: Cross-platform

## Troubleshooting

### Setup Issues
```bash
# If setup fails:
cd build-tools
rm -rf node_modules package-lock.json
./setup.sh
```

### Permission Issues
```bash
# Make setup script executable:
chmod +x build-tools/setup.sh
```

### Missing Source Files
The build system will warn you if source files don't exist. Create them first:
- `assets/css/style.css`
- `assets/js/script.js`

## IDE Integration

### VS Code
The system can be integrated with VS Code tasks. See `build-tools/README.md` for detailed IDE setup instructions.

### Command Palette
You can add VS Code tasks to run builds directly from the command palette.

## Performance Impact

### Typical Compression Results
- **CSS**: 60-80% size reduction
- **JavaScript**: 40-60% size reduction

### Example Output
```
✓ CSS minified: 45.2 KB → 12.8 KB (71.7% reduction)
✓ JS minified: 28.4 KB → 15.1 KB (46.8% reduction)
```

## Support

- **Detailed Documentation**: See `build-tools/README.md`
- **Setup Script**: Run `build-tools/setup.sh`
- **Help Command**: `node build-tools/build.js --help`

---

**💡 Remember**: Always test both source and minified versions before releasing!