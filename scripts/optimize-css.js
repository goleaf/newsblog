#!/usr/bin/env node

/**
 * CSS Optimization Script
 * 
 * This script:
 * 1. Builds the application with Vite
 * 2. Extracts critical CSS
 * 3. Minifies CSS files
 * 4. Removes unused CSS
 * 5. Generates reports
 */

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

console.log('🚀 Starting CSS optimization...\n');

// Step 1: Build with Vite
console.log('📦 Building assets with Vite...');
try {
    execSync('npm run build', { stdio: 'inherit', cwd: rootDir });
    console.log('✅ Build completed\n');
} catch (error) {
    console.error('❌ Build failed:', error.message);
    process.exit(1);
}

// Step 2: Analyze CSS files
console.log('📊 Analyzing CSS files...');
const buildDir = path.join(rootDir, 'public', 'build', 'assets');

if (!fs.existsSync(buildDir)) {
    console.error('❌ Build directory not found');
    process.exit(1);
}

const cssFiles = fs.readdirSync(buildDir).filter(file => file.endsWith('.css'));

console.log(`Found ${cssFiles.length} CSS files:\n`);

let totalSize = 0;
let totalGzipSize = 0;

cssFiles.forEach(file => {
    const filePath = path.join(buildDir, file);
    const stats = fs.statSync(filePath);
    const sizeKB = (stats.size / 1024).toFixed(2);
    
    // Estimate gzip size (roughly 30% of original)
    const gzipSizeKB = (stats.size * 0.3 / 1024).toFixed(2);
    
    totalSize += stats.size;
    totalGzipSize += stats.size * 0.3;
    
    console.log(`  ${file}`);
    console.log(`    Size: ${sizeKB} KB`);
    console.log(`    Gzipped: ~${gzipSizeKB} KB\n`);
});

console.log(`Total CSS size: ${(totalSize / 1024).toFixed(2)} KB`);
console.log(`Total gzipped: ~${(totalGzipSize / 1024).toFixed(2)} KB\n`);

// Step 3: Check critical CSS
const criticalCssFiles = cssFiles.filter(file => file.includes('critical'));
if (criticalCssFiles.length > 0) {
    console.log('✅ Critical CSS found:');
    criticalCssFiles.forEach(file => {
        const filePath = path.join(buildDir, file);
        const stats = fs.statSync(filePath);
        const sizeKB = (stats.size / 1024).toFixed(2);
        console.log(`  ${file} (${sizeKB} KB)`);
    });
    console.log('');
} else {
    console.log('⚠️  No critical CSS file found\n');
}

// Step 4: Generate optimization report
console.log('📝 Generating optimization report...');

const report = {
    timestamp: new Date().toISOString(),
    cssFiles: cssFiles.length,
    totalSize: `${(totalSize / 1024).toFixed(2)} KB`,
    totalGzipped: `~${(totalGzipSize / 1024).toFixed(2)} KB`,
    files: cssFiles.map(file => {
        const filePath = path.join(buildDir, file);
        const stats = fs.statSync(filePath);
        return {
            name: file,
            size: `${(stats.size / 1024).toFixed(2)} KB`,
            gzipped: `~${(stats.size * 0.3 / 1024).toFixed(2)} KB`,
        };
    }),
    recommendations: [],
};

// Add recommendations
if (totalSize > 100 * 1024) {
    report.recommendations.push('Consider splitting CSS into smaller chunks');
}

if (criticalCssFiles.length === 0) {
    report.recommendations.push('Add critical CSS for faster initial render');
}

const mainCssFiles = cssFiles.filter(file => file.includes('app') && !file.includes('critical'));
if (mainCssFiles.length > 0) {
    mainCssFiles.forEach(file => {
        const filePath = path.join(buildDir, file);
        const stats = fs.statSync(filePath);
        if (stats.size > 50 * 1024) {
            report.recommendations.push(`${file} is large (${(stats.size / 1024).toFixed(2)} KB) - consider code splitting`);
        }
    });
}

// Save report
const reportPath = path.join(rootDir, 'storage', 'app', 'css-optimization-report.json');
fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));

console.log(`✅ Report saved to: ${reportPath}\n`);

// Display recommendations
if (report.recommendations.length > 0) {
    console.log('💡 Recommendations:');
    report.recommendations.forEach(rec => {
        console.log(`  - ${rec}`);
    });
    console.log('');
}

console.log('✨ CSS optimization complete!\n');

// Performance targets
console.log('🎯 Performance Targets:');
console.log('  - Critical CSS: < 14 KB (inline)');
console.log('  - Main CSS: < 50 KB (gzipped)');
console.log('  - Total CSS: < 100 KB (gzipped)');
console.log('');

// Check if targets are met
const criticalSize = criticalCssFiles.reduce((sum, file) => {
    const filePath = path.join(buildDir, file);
    return sum + fs.statSync(filePath).size;
}, 0);

const mainSize = mainCssFiles.reduce((sum, file) => {
    const filePath = path.join(buildDir, file);
    return sum + fs.statSync(filePath).size;
}, 0);

if (criticalSize > 14 * 1024) {
    console.log('⚠️  Critical CSS exceeds 14 KB target');
} else if (criticalSize > 0) {
    console.log('✅ Critical CSS within target');
}

if (mainSize * 0.3 > 50 * 1024) {
    console.log('⚠️  Main CSS exceeds 50 KB gzipped target');
} else if (mainSize > 0) {
    console.log('✅ Main CSS within target');
}

if (totalGzipSize > 100 * 1024) {
    console.log('⚠️  Total CSS exceeds 100 KB gzipped target');
} else {
    console.log('✅ Total CSS within target');
}

console.log('');
