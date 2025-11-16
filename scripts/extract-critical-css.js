#!/usr/bin/env node

/**
 * Critical CSS Extraction Script
 * 
 * This script extracts critical CSS for different page types
 * and optimizes them for inline inclusion in the HTML head.
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

console.log('🎨 Extracting and optimizing critical CSS...\n');

// Define critical CSS files to process
const criticalFiles = [
    'critical.css',
    'critical-home.css',
    'critical-article.css',
];

const buildDir = path.join(rootDir, 'public', 'build', 'assets');

if (!fs.existsSync(buildDir)) {
    console.error('❌ Build directory not found. Run npm run build first.');
    process.exit(1);
}

// Process each critical CSS file
criticalFiles.forEach(filename => {
    const sourcePattern = new RegExp(`${filename.replace('.css', '')}-[a-f0-9]+\\.css`);
    const files = fs.readdirSync(buildDir).filter(file => sourcePattern.test(file));
    
    if (files.length === 0) {
        console.log(`⚠️  ${filename} not found in build`);
        return;
    }
    
    const sourceFile = files[0];
    const sourcePath = path.join(buildDir, sourceFile);
    const targetPath = path.join(buildDir, filename);
    
    // Read the CSS content
    let css = fs.readFileSync(sourcePath, 'utf8');
    
    // Additional optimizations for inline CSS
    css = css
        // Remove source map comments
        .replace(/\/\*# sourceMappingURL=.*?\*\//g, '')
        // Remove extra whitespace
        .replace(/\s+/g, ' ')
        // Remove spaces around selectors and braces
        .replace(/\s*{\s*/g, '{')
        .replace(/\s*}\s*/g, '}')
        .replace(/\s*:\s*/g, ':')
        .replace(/\s*;\s*/g, ';')
        .replace(/;\s*}/g, '}')
        // Remove leading/trailing whitespace
        .trim();
    
    // Write the optimized CSS
    fs.writeFileSync(targetPath, css);
    
    const originalSize = fs.statSync(sourcePath).size;
    const optimizedSize = fs.statSync(targetPath).size;
    const savings = ((1 - optimizedSize / originalSize) * 100).toFixed(1);
    
    console.log(`✅ ${filename}`);
    console.log(`   Original: ${(originalSize / 1024).toFixed(2)} KB`);
    console.log(`   Optimized: ${(optimizedSize / 1024).toFixed(2)} KB`);
    console.log(`   Savings: ${savings}%`);
    
    // Check if it's small enough to inline (target: < 14 KB)
    if (optimizedSize > 14 * 1024) {
        console.log(`   ⚠️  Warning: File exceeds 14 KB inline target`);
    } else {
        console.log(`   ✓ Within 14 KB inline target`);
    }
    console.log('');
});

console.log('✨ Critical CSS extraction complete!\n');

// Generate a report
const report = {
    timestamp: new Date().toISOString(),
    files: [],
};

criticalFiles.forEach(filename => {
    const filePath = path.join(buildDir, filename);
    if (fs.existsSync(filePath)) {
        const stats = fs.statSync(filePath);
        report.files.push({
            name: filename,
            size: stats.size,
            sizeKB: (stats.size / 1024).toFixed(2),
            withinTarget: stats.size <= 14 * 1024,
        });
    }
});

const reportPath = path.join(rootDir, 'storage', 'app', 'critical-css-report.json');
fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));

console.log(`📊 Report saved to: ${reportPath}\n`);
