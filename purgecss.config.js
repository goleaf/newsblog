export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
        './app/View/Components/**/*.php',
    ],
    css: ['./public/build/assets/*.css'],
    output: './public/build/assets/',
    defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
    safelist: {
        standard: [
            // Tailwind prose classes
            /^prose/,
            // Dark mode classes
            /^dark/,
            // Dynamic classes
            /^bg-/,
            /^text-/,
            /^border-/,
            /^hover:/,
            /^focus:/,
            /^active:/,
            /^disabled:/,
            /^group-hover:/,
            // Animation classes
            /^animate-/,
            /^transition-/,
            /^duration-/,
            /^ease-/,
            // Grid and flex classes
            /^grid-cols-/,
            /^gap-/,
            /^flex-/,
            // Spacing classes
            /^p-/,
            /^m-/,
            /^space-/,
        ],
        deep: [
            // Alpine.js directives
            /x-cloak/,
            /x-show/,
            /x-transition/,
            /x-data/,
            /x-bind/,
            /x-on/,
            /x-model/,
            /x-if/,
            /x-for/,
        ],
        greedy: [
            // Third-party libraries
            /^hljs/,        // Highlight.js
            /^flatpickr/,   // Flatpickr date picker
            /^tox/,         // TinyMCE
            /^ͼ/,           // CodeMirror
        ],
    },
    // Variables to keep
    variables: true,
    // Keyframes to keep
    keyframes: true,
    // Font faces to keep
    fontFace: true,
};
