import autoprefixer from 'autoprefixer';
import tailwindcss from 'tailwindcss';
import cssnano from 'cssnano';

const isProduction = process.env.NODE_ENV === 'production';

export default {
    plugins: [
        // Process Tailwind CSS
        tailwindcss,
        
        // Add vendor prefixes
        autoprefixer,

        
        // Minify CSS in production
        ...(isProduction ? [
            cssnano({
                preset: ['default', {
                    discardComments: {
                        removeAll: true,
                    },
                    normalizeWhitespace: true,
                    colormin: true,
                    minifyFontValues: true,
                    minifyGradients: true,
                    minifySelectors: true,
                    reduceIdents: false, // Keep animation names
                    zindex: false, // Don't optimize z-index
                }],
            }),
        ] : []),
    ],
};
