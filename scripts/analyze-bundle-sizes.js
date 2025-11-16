#!/usr/bin/env node

/**
 * Bundle Size Analyzer
 * 
 * Analyzes the Vite build output and reports on bundle sizes,
 * code splitting effectiveness, and potential optimizations.
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BUILD_DIR = path.join(__dirname, '../public/build');
const MANIFEST_PATH = path.join(BUILD_DIR, 'manifest.json');

// Size limits (in bytes)
const LIMITS = {
    js: {
        individual: 500 * 1024, // 500KB per bundle
        total: 2 * 1024 * 1024,  // 2MB total
    },
    css: {
        individual: 200 * 1024, // 200KB per bundle
        total: 500 * 1024,      // 500KB total
    },
};

/**
 * Format bytes to human-readable size
 */
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Get all files in a directory recursively
 */
function getFiles(dir, fileList = []) {
    if (!fs.existsSync(dir)) {
        return fileList;
    }

    const files = fs.readdirSync(dir);
    
    files.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);
        
        if (stat.isDirectory()) {
            getFiles(filePath, fileList);
        } else {
            fileList.push({
                path: filePath,
                name: file,
                size: stat.size,
                relativePath: path.relative(BUILD_DIR, filePath),
            });
        }
    });
    
    return fileList;
}

/**
 * Analyze bundle sizes
 */
function analyzeBundles() {
    console.log('\n📦 Bundle Size Analysis\n');
    console.log('='.repeat(80));
    
    if (!fs.existsSync(BUILD_DIR)) {
        console.error('❌ Build directory not found. Run "npm run build" first.');
        process.exit(1);
    }

    const allFiles = getFiles(BUILD_DIR);
    const jsFiles = allFiles.filter(f => f.name.endsWith('.js'));
    const cssFiles = allFiles.filter(f => f.name.endsWith('.css'));

    // Analyze JavaScript bundles
    console.log('\n📄 JavaScript Bundles:\n');
    
    let totalJsSize = 0;
    const jsWarnings = [];
    
    jsFiles.sort((a, b) => b.size - a.size).forEach(file => {
        totalJsSize += file.size;
        const status = file.size > LIMITS.js.individual ? '⚠️ ' : '✅';
        console.log(`  ${status} ${file.relativePath}: ${formatBytes(file.size)}`);
        
        if (file.size > LIMITS.js.individual) {
            jsWarnings.push(`${file.relativePath} exceeds ${formatBytes(LIMITS.js.individual)}`);
        }
    });
    
    console.log(`\n  Total JS: ${formatBytes(totalJsSize)}`);
    
    if (totalJsSize > LIMITS.js.total) {
        console.log(`  ⚠️  Total JS size exceeds limit of ${formatBytes(LIMITS.js.total)}`);
    } else {
        console.log(`  ✅ Total JS size within limit of ${formatBytes(LIMITS.js.total)}`);
    }

    // Analyze CSS bundles
    console.log('\n🎨 CSS Bundles:\n');
    
    let totalCssSize = 0;
    const cssWarnings = [];
    
    cssFiles.sort((a, b) => b.size - a.size).forEach(file => {
        totalCssSize += file.size;
        const status = file.size > LIMITS.css.individual ? '⚠️ ' : '✅';
        console.log(`  ${status} ${file.relativePath}: ${formatBytes(file.size)}`);
        
        if (file.size > LIMITS.css.individual) {
            cssWarnings.push(`${file.relativePath} exceeds ${formatBytes(LIMITS.css.individual)}`);
        }
    });
    
    console.log(`\n  Total CSS: ${formatBytes(totalCssSize)}`);
    
    if (totalCssSize > LIMITS.css.total) {
        console.log(`  ⚠️  Total CSS size exceeds limit of ${formatBytes(LIMITS.css.total)}`);
    } else {
        console.log(`  ✅ Total CSS size within limit of ${formatBytes(LIMITS.css.total)}`);
    }

    // Count chunks by type (initialize outside for later use)
    const chunkTypes = {
        vendor: 0,
        page: 0,
        component: 0,
        other: 0,
    };

    // Analyze manifest
    if (fs.existsSync(MANIFEST_PATH)) {
        console.log('\n📋 Build Manifest Analysis:\n');
        
        const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf8'));
        const entries = Object.keys(manifest);
        
        console.log(`  Total entries: ${entries.length}`);
        
        entries.forEach(entry => {
            const file = manifest[entry].file || entry;
            
            if (file.includes('vendor')) {
                chunkTypes.vendor++;
            } else if (file.includes('page-') || file.includes('pages/')) {
                chunkTypes.page++;
            } else if (file.includes('component')) {
                chunkTypes.component++;
            } else {
                chunkTypes.other++;
            }
        });
        
        console.log(`  Vendor chunks: ${chunkTypes.vendor}`);
        console.log(`  Page chunks: ${chunkTypes.page}`);
        console.log(`  Component chunks: ${chunkTypes.component}`);
        console.log(`  Other chunks: ${chunkTypes.other}`);
        
        // Check for code splitting effectiveness
        if (chunkTypes.vendor > 0) {
            console.log('\n  ✅ Vendor code splitting is active');
        } else {
            console.log('\n  ⚠️  No vendor chunks found - check Vite config');
        }
        
        if (chunkTypes.page > 0) {
            console.log('  ✅ Route-based code splitting is active');
        } else {
            console.log('  ⚠️  No page chunks found - check entry points');
        }
    }

    // Summary
    console.log('\n' + '='.repeat(80));
    console.log('\n📊 Summary:\n');
    
    const totalSize = totalJsSize + totalCssSize;
    console.log(`  Total bundle size: ${formatBytes(totalSize)}`);
    console.log(`  JavaScript: ${formatBytes(totalJsSize)} (${jsFiles.length} files)`);
    console.log(`  CSS: ${formatBytes(totalCssSize)} (${cssFiles.length} files)`);
    
    // Warnings
    const allWarnings = [...jsWarnings, ...cssWarnings];
    
    if (allWarnings.length > 0) {
        console.log('\n⚠️  Warnings:\n');
        allWarnings.forEach(warning => {
            console.log(`  - ${warning}`);
        });
    } else {
        console.log('\n✅ All bundles are within size limits!');
    }
    
    // Recommendations
    console.log('\n💡 Recommendations:\n');
    
    if (totalJsSize > LIMITS.js.total * 0.8) {
        console.log('  - Consider further code splitting for large JavaScript bundles');
    }
    
    if (cssFiles.length === 1) {
        console.log('  - Consider splitting CSS by route for better caching');
    }
    
    if (chunkTypes.vendor === 0) {
        console.log('  - Enable vendor chunk splitting in Vite config');
    }
    
    if (jsFiles.length < 3) {
        console.log('  - Consider adding more entry points for route-based splitting');
    }
    
    console.log('\n' + '='.repeat(80) + '\n');
}

// Run analysis
try {
    analyzeBundles();
} catch (error) {
    console.error('\n❌ Error analyzing bundles:', error.message);
    process.exit(1);
}
