#!/usr/bin/env node
/**
 * WP Mixcloud Archives - Build Tool
 * 
 * Automated minification and optimization tool for plugin assets.
 * This tool is for development use only and should NOT be included in the plugin distribution.
 * 
 * @package WPMixcloudArchives
 * @version 1.0.0
 */

const fs = require('fs');
const path = require('path');
const { minify } = require('terser');
const postcss = require('postcss');
const cssnano = require('cssnano');
const chokidar = require('chokidar');
const chalk = require('chalk');
const archiver = require('archiver');
const { glob } = require('glob');
const ignore = require('ignore');

// Configuration
const CONFIG = {
    // AIDEV-NOTE: Paths relative to project root (one level up from build-tools)
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
    // Terser options for JavaScript minification
    terserOptions: {
        compress: {
            drop_console: false, // Keep console logs for debugging
            drop_debugger: true,
            pure_funcs: ['console.info', 'console.debug'],
            unsafe_arrows: true,
            module: false
        },
        mangle: {
            reserved: ['wpMixcloudArchives', 'wpMixcloudArchivesRefresh']
        },
        format: {
            comments: false
        }
    },
    // cssnano options for CSS minification  
    cssnanoOptions: {
        preset: ['default', {
            discardComments: {
                removeAll: true
            },
            normalizeWhitespace: true,
            mergeRules: true,
            minifySelectors: true
        }]
    },
    // Package configuration
    package: {
        outputDir: 'dist',
        pluginName: 'wp-mixcloud-archives',
        // Files to include in package
        include: [
            '*.php',
            'assets/**/*',
            'includes/**/*',
            'admin/**/*',
            'templates/**/*',
            'languages/**/*',
            'readme.txt',
            'LICENSE',
            'CHANGELOG.md'
        ],
        // Additional files to exclude beyond .gitignore (distribution-specific)
        additionalExcludes: [
            'BUILD-SYSTEM.md',
            'PRD.md',
            'AGENTS.md',
            'CLAUDE.md',
            '.taskmaster/**/*',
            '.claude/**/*',
            '.cursor/**/*',
            '.roo/**/*',
            '.windsurf/**/*',
            '.clinerules/**/*',
            '.trae/**/*',
            // Exclude source files - only include minified versions
            'assets/css/style.css',
            'assets/js/script.js'
        ]
    }
};

/**
 * Logger utility with colored output
 */
const logger = {
    info: (msg) => console.log(chalk.blue('ℹ'), msg),
    success: (msg) => console.log(chalk.green('✓'), msg),
    warning: (msg) => console.log(chalk.yellow('⚠'), msg),
    error: (msg) => console.log(chalk.red('✗'), msg),
    header: (msg) => console.log(chalk.bold.cyan(msg))
};

/**
 * Get file size in human readable format
 */
function getFileSize(filePath) {
    const stats = fs.statSync(filePath);
    const bytes = stats.size;
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

/**
 * Calculate compression ratio
 */
function getCompressionRatio(originalSize, minifiedSize) {
    const ratio = ((originalSize - minifiedSize) / originalSize * 100).toFixed(1);
    return `${ratio}%`;
}

/**
 * Minify CSS file
 */
async function minifyCSS() {
    const sourcePath = path.join(CONFIG.projectRoot, CONFIG.assets.css.source);
    const outputPath = path.join(CONFIG.projectRoot, CONFIG.assets.css.output);
    
    try {
        if (!fs.existsSync(sourcePath)) {
            logger.error(`CSS source file not found: ${sourcePath}`);
            return false;
        }
        
        logger.info('Minifying CSS...');
        
        const css = fs.readFileSync(sourcePath, 'utf8');
        const originalSize = Buffer.byteLength(css, 'utf8');
        
        const result = await postcss([cssnano(CONFIG.cssnanoOptions)])
            .process(css, { from: sourcePath, to: outputPath });
        
        fs.writeFileSync(outputPath, result.css);
        
        const minifiedSize = Buffer.byteLength(result.css, 'utf8');
        const compressionRatio = getCompressionRatio(originalSize, minifiedSize);
        
        logger.success(`CSS minified: ${getFileSize(sourcePath)} → ${getFileSize(outputPath)} (${compressionRatio} reduction)`);
        return true;
        
    } catch (error) {
        logger.error(`CSS minification failed: ${error.message}`);
        return false;
    }
}

/**
 * Minify JavaScript file
 */
async function minifyJS() {
    const sourcePath = path.join(CONFIG.projectRoot, CONFIG.assets.js.source);
    const outputPath = path.join(CONFIG.projectRoot, CONFIG.assets.js.output);
    
    try {
        if (!fs.existsSync(sourcePath)) {
            logger.error(`JS source file not found: ${sourcePath}`);
            return false;
        }
        
        logger.info('Minifying JavaScript...');
        
        const js = fs.readFileSync(sourcePath, 'utf8');
        const originalSize = Buffer.byteLength(js, 'utf8');
        
        const result = await minify(js, CONFIG.terserOptions);
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        fs.writeFileSync(outputPath, result.code);
        
        const minifiedSize = Buffer.byteLength(result.code, 'utf8');
        const compressionRatio = getCompressionRatio(originalSize, minifiedSize);
        
        logger.success(`JS minified: ${getFileSize(sourcePath)} → ${getFileSize(outputPath)} (${compressionRatio} reduction)`);
        return true;
        
    } catch (error) {
        logger.error(`JS minification failed: ${error.message}`);
        return false;
    }
}

/**
 * Clean minified files
 */
function clean() {
    const files = [
        path.join(CONFIG.projectRoot, CONFIG.assets.css.output),
        path.join(CONFIG.projectRoot, CONFIG.assets.js.output)
    ];
    
    let cleaned = 0;
    files.forEach(file => {
        if (fs.existsSync(file)) {
            fs.unlinkSync(file);
            cleaned++;
            logger.info(`Removed: ${path.relative(CONFIG.projectRoot, file)}`);
        }
    });
    
    if (cleaned > 0) {
        logger.success(`Cleaned ${cleaned} minified file(s)`);
    } else {
        logger.info('No minified files to clean');
    }
}

/**
 * Get plugin version from main PHP file
 */
function getPluginVersion() {
    try {
        const mainFile = path.join(CONFIG.projectRoot, 'wp-mixcloud-archives.php');
        const content = fs.readFileSync(mainFile, 'utf8');
        const versionMatch = content.match(/Version:\s*([^\r\n]+)/);
        return versionMatch ? versionMatch[1].trim() : '1.0.0';
    } catch (error) {
        logger.warning('Could not read plugin version, using default: 1.0.0');
        return '1.0.0';
    }
}

/**
 * Read and parse .gitignore file
 */
function readGitIgnore() {
    const gitignorePath = path.join(CONFIG.projectRoot, '.gitignore');
    
    if (!fs.existsSync(gitignorePath)) {
        logger.warning('.gitignore file not found, using minimal exclusions');
        return ignore();
    }
    
    try {
        const gitignoreContent = fs.readFileSync(gitignorePath, 'utf8');
        const ig = ignore().add(gitignoreContent);
        
        logger.info(`Loaded .gitignore with ${gitignoreContent.split('\n').filter(line => line.trim() && !line.startsWith('#')).length} exclusion rules`);
        return ig;
        
    } catch (error) {
        logger.warning(`Failed to read .gitignore: ${error.message}`);
        return ignore();
    }
}

/**
 * Create combined ignore filter (gitignore + additional excludes)
 */
function createIgnoreFilter() {
    const ig = readGitIgnore();
    
    // Add distribution-specific excludes
    const additionalExcludes = CONFIG.package.additionalExcludes;
    if (additionalExcludes.length > 0) {
        ig.add(additionalExcludes);
        logger.info(`Added ${additionalExcludes.length} additional distribution exclusions`);
    }
    
    return ig;
}

/**
 * Copy files to package directory
 */
async function copyFilesToPackage(packageDir) {
    const includePatterns = CONFIG.package.include;
    
    logger.info('Gathering files for package...');
    
    // Create ignore filter from .gitignore + additional excludes
    const ignoreFilter = createIgnoreFilter();
    
    // Get all files matching include patterns
    let allFiles = [];
    for (const pattern of includePatterns) {
        const files = await glob(pattern, {
            cwd: CONFIG.projectRoot,
            nodir: true,
            dot: false
        });
        allFiles = allFiles.concat(files);
    }
    
    // Remove duplicates
    allFiles = [...new Set(allFiles)];
    
    // Filter using .gitignore rules + additional excludes
    const filteredFiles = allFiles.filter(file => {
        const shouldIgnore = ignoreFilter.ignores(file);
        return !shouldIgnore;
    });
    
    const ignoredCount = allFiles.length - filteredFiles.length;
    logger.info(`Found ${allFiles.length} files, filtered to ${filteredFiles.length} (${ignoredCount} ignored by .gitignore rules)`);
    
    // Copy files to package directory
    let copiedCount = 0;
    for (const file of filteredFiles) {
        const sourcePath = path.join(CONFIG.projectRoot, file);
        const destPath = path.join(packageDir, file);
        
        // Ensure destination directory exists
        const destDir = path.dirname(destPath);
        if (!fs.existsSync(destDir)) {
            fs.mkdirSync(destDir, { recursive: true });
        }
        
        // Copy file
        fs.copyFileSync(sourcePath, destPath);
        copiedCount++;
    }
    
    logger.success(`Copied ${copiedCount} files to package directory`);
    return filteredFiles;
}

/**
 * Create zip archive from package directory
 */
async function createZipArchive(packageDir, zipPath) {
    return new Promise((resolve, reject) => {
        logger.info('Creating zip archive...');
        
        const output = fs.createWriteStream(zipPath);
        const archive = archiver('zip', {
            zlib: { level: 9 } // Maximum compression
        });
        
        output.on('close', () => {
            const sizeStr = getFileSize(zipPath);
            logger.success(`Zip archive created: ${path.basename(zipPath)} (${sizeStr})`);
            resolve();
        });
        
        archive.on('error', (err) => {
            logger.error(`Zip creation failed: ${err.message}`);
            reject(err);
        });
        
        archive.pipe(output);
        archive.directory(packageDir, false);
        archive.finalize();
    });
}

/**
 * Create plugin distribution package
 */
async function createPackage(createZip = false) {
    logger.header('📦 Creating WordPress Plugin Package');
    
    const startTime = Date.now();
    
    try {
        // Step 1: Ensure assets are built first
        logger.info('Building assets before packaging...');
        await buildAll();
        
        // Step 2: Setup package directory
        const version = getPluginVersion();
        const packageName = `${CONFIG.package.pluginName}-${version}`;
        const distDir = path.join(CONFIG.projectRoot, CONFIG.package.outputDir);
        const packageDir = path.join(distDir, packageName);
        
        // Clean and create package directory
        if (fs.existsSync(packageDir)) {
            fs.rmSync(packageDir, { recursive: true, force: true });
        }
        fs.mkdirSync(packageDir, { recursive: true });
        
        logger.info(`Package directory: ${path.relative(CONFIG.projectRoot, packageDir)}`);
        
        // Step 3: Copy files to package
        const packagedFiles = await copyFilesToPackage(packageDir);
        
        // Step 4: Validate critical files
        const criticalFiles = [
            'wp-mixcloud-archives.php',
            'assets/css/style.min.css',
            'assets/js/script.min.js'
        ];
        
        const missingFiles = criticalFiles.filter(file => {
            return !fs.existsSync(path.join(packageDir, file));
        });
        
        if (missingFiles.length > 0) {
            logger.warning(`Missing critical files: ${missingFiles.join(', ')}`);
        } else {
            logger.success('All critical files present in package');
        }
        
        // Step 5: Create zip archive if requested
        if (createZip) {
            const zipPath = path.join(distDir, `${packageName}.zip`);
            await createZipArchive(packageDir, zipPath);
        }
        
        // Step 6: Show package summary
        const duration = Date.now() - startTime;
        logger.success(`✨ Package created in ${duration}ms`);
        
        console.log('');
        logger.info('📊 Package Summary:');
        logger.info(`   Name: ${packageName}`);
        logger.info(`   Version: ${version}`);
        logger.info(`   Files: ${packagedFiles.length}`);
        logger.info(`   Location: ${path.relative(CONFIG.projectRoot, packageDir)}`);
        
        if (createZip) {
            const zipPath = path.join(distDir, `${packageName}.zip`);
            logger.info(`   Archive: ${path.relative(CONFIG.projectRoot, zipPath)} (${getFileSize(zipPath)})`);
        }
        
        console.log('');
        logger.info('🚀 Package ready for WordPress installation!');
        
        if (!createZip) {
            logger.info('💡 Tip: Use --zip flag to create a zip archive automatically');
        }
        
    } catch (error) {
        logger.error(`Package creation failed: ${error.message}`);
        process.exit(1);
    }
}

/**
 * Watch for file changes
 */
function watch() {
    const watchPaths = [
        path.join(CONFIG.projectRoot, CONFIG.assets.css.source),
        path.join(CONFIG.projectRoot, CONFIG.assets.js.source)
    ];
    
    logger.info('👀 Watching for file changes...');
    logger.info('Press Ctrl+C to stop watching');
    
    const watcher = chokidar.watch(watchPaths, {
        persistent: true,
        ignoreInitial: true
    });
    
    watcher.on('change', async (filePath) => {
        const relativePath = path.relative(CONFIG.projectRoot, filePath);
        logger.info(`📝 Changed: ${relativePath}`);
        
        if (filePath.includes('style.css')) {
            await minifyCSS();
        } else if (filePath.includes('script.js')) {
            await minifyJS();
        }
    });
    
    watcher.on('error', (error) => {
        logger.error(`Watcher error: ${error}`);
    });
    
    // Keep the process running
    process.on('SIGINT', () => {
        logger.info('\n👋 Stopping file watcher...');
        watcher.close();
        process.exit(0);
    });
}

/**
 * Build all assets
 */
async function buildAll() {
    logger.header('🚀 WP Mixcloud Archives - Asset Builder');
    logger.info(`Project root: ${CONFIG.projectRoot}`);
    
    const startTime = Date.now();
    let success = 0;
    let total = 0;
    
    // Build CSS
    total++;
    if (await minifyCSS()) success++;
    
    // Build JS
    total++;
    if (await minifyJS()) success++;
    
    const duration = Date.now() - startTime;
    
    if (success === total) {
        logger.success(`✨ Build completed in ${duration}ms (${success}/${total} files processed)`);
    } else {
        logger.warning(`⚠️ Build completed with errors in ${duration}ms (${success}/${total} files processed)`);
        process.exit(1);
    }
}

/**
 * Main execution
 */
async function main() {
    const args = process.argv.slice(2);
    
    // Parse command line arguments
    const options = {
        cssOnly: args.includes('--css-only'),
        jsOnly: args.includes('--js-only'),
        watch: args.includes('--watch'),
        clean: args.includes('--clean'),
        package: args.includes('--package'),
        zip: args.includes('--zip'),
        help: args.includes('--help') || args.includes('-h')
    };
    
    if (options.help) {
        console.log(`
WP Mixcloud Archives - Build Tool

Usage: node build.js [options]

Options:
  --css-only    Minify CSS files only
  --js-only     Minify JavaScript files only  
  --watch       Watch for file changes and rebuild automatically
  --clean       Remove all minified files
  --package     Create plugin distribution package
  --zip         Create zip archive (use with --package)
  --help, -h    Show this help message

Examples:
  node build.js                # Build all assets
  node build.js --css-only     # Build CSS only
  node build.js --watch        # Watch and rebuild on changes
  node build.js --clean        # Clean minified files
  node build.js --package      # Create distribution package
  node build.js --package --zip # Create distribution zip archive
        `);
        return;
    }
    
    if (options.clean) {
        clean();
        return;
    }
    
    if (options.package) {
        await createPackage(options.zip);
        return;
    }
    
    if (options.watch) {
        // Build once first, then watch
        await buildAll();
        watch();
        return;
    }
    
    if (options.cssOnly) {
        logger.header('🎨 Building CSS only...');
        await minifyCSS();
        return;
    }
    
    if (options.jsOnly) {
        logger.header('⚡ Building JavaScript only...');
        await minifyJS();
        return;
    }
    
    // Default: build all
    await buildAll();
}

// AIDEV-NOTE: Handle uncaught errors gracefully
process.on('uncaughtException', (error) => {
    logger.error(`Uncaught exception: ${error.message}`);
    process.exit(1);
});

process.on('unhandledRejection', (reason) => {
    logger.error(`Unhandled rejection: ${reason}`);
    process.exit(1);
});

// Run the main function
if (require.main === module) {
    main().catch(error => {
        logger.error(`Build failed: ${error.message}`);
        process.exit(1);
    });
}

module.exports = { minifyCSS, minifyJS, clean, watch, buildAll, createPackage };