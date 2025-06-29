# WP Mixcloud Archives - Build Tools

Automated build system for minifying and optimizing plugin assets during development.

> **⚠️ Important**: These build tools are for **development use only** and should **NOT** be included in the WordPress plugin distribution package.

## Features

- 🎨 **CSS Minification**: Automatically minifies CSS using cssnano
- ⚡ **JavaScript Minification**: Minifies JS with terser while preserving important globals
- 👀 **File Watching**: Real-time rebuilding when source files change
- 📊 **Compression Reports**: Shows file sizes and compression ratios
- 🧹 **Clean Command**: Removes all minified files
- 🎯 **Selective Building**: Build only CSS or JS when needed
- 📦 **Plugin Packaging**: Creates distribution-ready WordPress plugin packages
- 🗜️ **Zip Archives**: Automatically creates compressed plugin archives
- 🛡️ **Error Handling**: Comprehensive error reporting and validation
- 📋 **File Filtering**: Smart inclusion/exclusion of files for clean distributions

## Quick Start

### 1. Install Dependencies

```bash
cd build-tools
npm install
```

### 2. Build All Assets

```bash
npm run build
```

This will create:
- `assets/css/style.min.css` (minified from `assets/css/style.css`)
- `assets/js/script.min.js` (minified from `assets/js/script.js`)

## Available Commands

### NPM Scripts (Recommended)

```bash
# Build all assets (CSS + JS)
npm run build

# Build only CSS
npm run build:css

# Build only JavaScript  
npm run build:js

# Watch for changes and rebuild automatically
npm run watch

# Clean all minified files
npm run clean

# Create plugin distribution package
npm run package

# Create plugin package + zip archive
npm run package:zip
```

### Direct Node.js Commands

```bash
# Build all assets
node build.js

# Build specific asset types
node build.js --css-only
node build.js --js-only

# Watch mode
node build.js --watch

# Clean minified files
node build.js --clean

# Create distribution package
node build.js --package

# Create distribution package + zip
node build.js --package --zip

# Show help
node build.js --help
```

## Development Workflow

### During Active Development

1. **Start watching for changes:**
   ```bash
   npm run watch
   ```

2. **Edit your source files:**
   - `assets/css/style.css`
   - `assets/js/script.js`

3. **Files automatically minify** when you save changes

### Before Committing Changes

```bash
# Build fresh versions of all assets
npm run build

# Commit both source and minified files
git add assets/css/style.css assets/css/style.min.css
git add assets/js/script.js assets/js/script.min.js
git commit -m "Update styles and rebuild minified assets"
```

### Distribution Preparation

```bash
# Clean and rebuild everything
npm run clean
npm run build

# Create distribution package
npm run package:zip

# Verify package contents
ls -la ../dist/
```

### Plugin Packaging Workflow

The build system can create complete WordPress plugin packages ready for distribution:

```bash
# Create a distribution folder with clean plugin files
npm run package

# Create both folder and zip archive for easy distribution
npm run package:zip
```

**What gets packaged:**
- All PHP files (plugin core)
- Minified CSS and JS (automatically built first)
- Asset files (images, fonts, etc.)
- WordPress-specific files (readme.txt, languages/)
- Documentation files (LICENSE, CHANGELOG.md)

**What gets excluded:**
- **Everything in .gitignore** - Automatically respects your project's ignore rules
- **Additional distribution excludes** - Extra files not suitable for plugin distribution
  - BUILD-SYSTEM.md, PRD.md (development documentation)
  - AGENTS.md, CLAUDE.md (AI assistant files)
  - .taskmaster/, .claude/, .cursor/ (development tools)

The system automatically:
1. **Builds assets first** - Ensures minified files are up-to-date
2. **Reads plugin version** - From wp-mixcloud-archives.php header
3. **Respects .gitignore** - Uses your existing ignore rules (DRY principle)
4. **Creates clean directory** - Only distribution files included
5. **Validates critical files** - Ensures required files are present
6. **Generates zip archive** - Ready for WordPress installation

**Example output:**
```
📦 Creating WordPress Plugin Package
✓ CSS minified: 45.2 KB → 12.8 KB (71.7% reduction)
✓ JS minified: 28.4 KB → 15.1 KB (46.8% reduction)
ℹ Loaded .gitignore with 23 exclusion rules
ℹ Added 7 additional distribution exclusions
ℹ Found 62 files, filtered to 47 (15 ignored by .gitignore rules)
✓ Copied 47 files to package directory
✓ All critical files present in package
✓ Zip archive created: wp-mixcloud-archives-1.0.0.zip (156.2 KB)
✨ Package created in 1247ms

📊 Package Summary:
   Name: wp-mixcloud-archives-1.0.0
   Version: 1.0.0
   Files: 47
   Location: dist/wp-mixcloud-archives-1.0.0
   Archive: dist/wp-mixcloud-archives-1.0.0.zip (156.2 KB)

🚀 Package ready for WordPress installation!
```

## File Structure

```
wp-mixcloud-archives/
├── build-tools/                 # ← Development build tools (DO NOT DISTRIBUTE)
│   ├── package.json            # Dependencies
│   ├── build.js                # Main build script
│   ├── setup.sh                # One-time setup script
│   ├── README.md               # This file
│   └── node_modules/           # Dependencies (auto-generated)
├── dist/                       # Distribution packages (auto-generated)
│   ├── wp-mixcloud-archives-1.0.0/  # Clean plugin directory
│   └── wp-mixcloud-archives-1.0.0.zip  # Ready-to-install archive
├── assets/
│   ├── css/
│   │   ├── style.css           # Source CSS
│   │   └── style.min.css       # Minified CSS (auto-generated)
│   └── js/
│       ├── script.js           # Source JS
│       └── script.min.js       # Minified JS (auto-generated)
├── includes/                   # Plugin classes
├── admin/                      # Admin functionality
├── wp-mixcloud-archives.php    # Main plugin file
└── BUILD-SYSTEM.md            # Build system documentation (DO NOT DISTRIBUTE)
```

## File Exclusion Strategy (DRY Principle)

The build system uses a **DRY (Don't Repeat Yourself)** approach to file exclusion:

### Primary Source: .gitignore
- **Automatically reads and respects your `.gitignore` file**
- No need to maintain duplicate exclusion lists
- Ensures consistency between development and packaging
- Standard git ignore patterns work seamlessly

### Additional Distribution Excludes
Only files that are **appropriate for git but not for plugin distribution**:
- Development documentation (BUILD-SYSTEM.md, PRD.md)
- AI assistant files (CLAUDE.md, AGENTS.md)
- Development tool configs (.taskmaster/, .claude/, etc.)

### Benefits of This Approach
- ✅ **Single source of truth** - .gitignore is the authority
- ✅ **Automatic updates** - Changes to .gitignore affect packaging
- ✅ **No duplication** - Don't maintain two exclusion lists
- ✅ **Standard practices** - Works with any git workflow
- ✅ **Flexible** - Easy to override for special cases

### Example .gitignore Integration
```gitignore
# Your existing .gitignore
node_modules/
.env*
dist/
*.log

# Build tools (development only)
/build-tools/node_modules/
```

The packaging system will automatically exclude all these files without any configuration changes needed.

## Configuration

The build tool configuration is in `build.js` under the `CONFIG` object:

```javascript
const CONFIG = {
    projectRoot: path.resolve(__dirname, '..'),
    assets: {
        css: {
            source: 'assets/css/style.css',
            output: 'assets/css/style.min.css'
        },
        js: {
            source: 'assets/js/script.js', 
            output: 'assets/js/script.min.js'
        }
    },
    // ... terser and cssnano options
};
```

### JavaScript Minification Options

- **Preserves**: `wpMixcloudArchives` and `wpMixcloudArchivesRefresh` globals
- **Removes**: `console.debug` and `console.info` calls
- **Keeps**: `console.log`, `console.warn`, `console.error` for debugging
- **Mangles**: Variable names for size reduction

### CSS Minification Options

- **Removes**: All comments
- **Normalizes**: Whitespace
- **Merges**: Duplicate rules
- **Minifies**: Selectors and values

## Git Integration

### .gitignore Recommendations

Add to your `.gitignore`:

```gitignore
# Build tools (development only)
/build-tools/node_modules/
/build-tools/.npm/

# Distribution packages (regenerated on build)
/dist/

# Keep minified files in git for distribution
# (Don't ignore *.min.css or *.min.js)
```

### Pre-commit Hook (Optional)

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash
# Auto-rebuild assets before commit

if [ -d "build-tools" ]; then
    echo "Rebuilding assets..."
    cd build-tools
    npm run build
    cd ..
    
    # Add updated minified files to commit
    git add assets/css/style.min.css
    git add assets/js/script.min.js
fi
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

## Troubleshooting

### Common Issues

**1. "Module not found" errors**
```bash
cd build-tools
npm install
```

**2. "Permission denied" errors**
```bash
chmod +x build.js
```

**3. Source file not found**
- Verify paths in `CONFIG` object
- Ensure source files exist in expected locations

**4. Build fails silently**
```bash
# Run with verbose output
node build.js --help
```

### Dependencies

| Package | Purpose | Version |
|---------|---------|---------|
| terser | JavaScript minification | ^5.24.0 |
| cssnano | CSS minification | ^6.0.1 |
| postcss | CSS processing | ^8.4.31 |
| chokidar | File watching | ^3.5.3 |
| chalk | Colored console output | ^4.1.2 |
| archiver | Zip archive creation | ^6.0.1 |
| glob | File pattern matching | ^10.3.10 |
| ignore | .gitignore parsing | ^5.3.0 |

### Requirements

- **Node.js**: 14.0.0 or higher
- **npm**: Latest stable version
- **Operating System**: Cross-platform (Windows, macOS, Linux)

## Integration with IDEs

### VS Code

Add to `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Build Assets",
            "type": "shell",
            "command": "npm",
            "args": ["run", "build"],
            "options": {
                "cwd": "${workspaceFolder}/build-tools"
            },
            "group": "build",
            "presentation": {
                "echo": true,
                "reveal": "always",
                "focus": false,
                "panel": "shared"
            }
        },
        {
            "label": "Watch Assets",
            "type": "shell", 
            "command": "npm",
            "args": ["run", "watch"],
            "options": {
                "cwd": "${workspaceFolder}/build-tools"
            },
            "isBackground": true,
            "runOptions": {
                "runOn": "folderOpen"
            }
        }
    ]
}
```

## Comparison: Before vs After

### Manual Process (Before)
1. ✋ Edit source file
2. ✋ Manually copy content
3. ✋ Use online minifier or manual tools
4. ✋ Copy minified content back
5. ✋ Paste into .min files
6. ✋ Repeat for each file type
7. ❌ Prone to human error
8. ❌ Time consuming
9. ❌ Inconsistent results

### Automated Process (After)  
1. ✅ Edit source file
2. ✅ Run `npm run build` or use watch mode
3. ✅ Minified files automatically updated
4. ✅ Consistent compression settings
5. ✅ Error reporting and validation
6. ✅ File size reports
7. ✅ Can be integrated into CI/CD
8. ✅ Zero human error in minification

## Best Practices

### Development
- Always use **watch mode** during active development
- Keep source files clean and well-commented
- Test both source and minified versions
- Don't edit minified files directly

### Production
- Always rebuild before releasing
- Verify compression ratios are reasonable
- Test minified files in actual WordPress environment
- Include both source and minified in distribution

### Team Collaboration
- Document any config changes in commit messages
- Share build tool updates via version control
- Ensure all developers use same Node.js version
- Consider using npm scripts in package.json for consistency

---

**💡 Pro Tip**: Use `npm run watch` during development and `npm run build` before commits for the most efficient workflow!