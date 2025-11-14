@props(['post'])

<div 
    class="prose prose-lg dark:prose-invert max-w-none
           prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white
           prose-p:text-gray-700 dark:prose-p:text-gray-300 prose-p:leading-relaxed
           prose-a:text-indigo-600 dark:prose-a:text-indigo-400 prose-a:no-underline hover:prose-a:underline
           prose-strong:text-gray-900 dark:prose-strong:text-white prose-strong:font-semibold
           prose-code:text-indigo-600 dark:prose-code:text-indigo-400 prose-code:bg-gray-100 dark:prose-code:bg-gray-800 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:before:content-[''] prose-code:after:content-['']
           prose-pre:bg-gray-900 dark:prose-pre:bg-gray-950 prose-pre:text-gray-100
           prose-blockquote:border-l-indigo-600 dark:prose-blockquote:border-l-indigo-400 prose-blockquote:bg-gray-50 dark:prose-blockquote:bg-gray-800 prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:rounded-r
           prose-img:rounded-lg prose-img:shadow-md
           prose-hr:border-gray-300 dark:prose-hr:border-gray-700
           prose-table:border-collapse prose-th:bg-gray-100 dark:prose-th:bg-gray-800 prose-th:border prose-th:border-gray-300 dark:prose-th:border-gray-700 prose-td:border prose-td:border-gray-300 dark:prose-td:border-gray-700
           prose-ul:list-disc prose-ol:list-decimal
           prose-li:text-gray-700 dark:prose-li:text-gray-300"
    x-data="articleContent"
>
    {!! $post->content !!}
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('articleContent', () => ({
        init() {
            // Add lazy loading to images
            this.$el.querySelectorAll('img').forEach(img => {
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'lazy');
                }
                
                // Add figure wrapper if image has alt text (caption)
                if (img.alt && !img.parentElement.matches('figure')) {
                    const figure = document.createElement('figure');
                    const figcaption = document.createElement('figcaption');
                    figcaption.textContent = img.alt;
                    figcaption.className = 'text-center text-sm text-gray-600 dark:text-gray-400 mt-2 italic';
                    
                    img.parentNode.insertBefore(figure, img);
                    figure.appendChild(img);
                    figure.appendChild(figcaption);
                }
            });

            // Add syntax highlighting classes to code blocks
            this.$el.querySelectorAll('pre code').forEach(block => {
                // Detect language from class name
                const classes = block.className.split(' ');
                const langClass = classes.find(c => c.startsWith('language-'));
                
                if (langClass) {
                    const lang = langClass.replace('language-', '');
                    
                    // Add language label
                    const label = document.createElement('div');
                    label.textContent = lang.toUpperCase();
                    label.className = 'absolute top-2 right-2 text-xs text-gray-400 bg-gray-800 px-2 py-1 rounded';
                    
                    const pre = block.parentElement;
                    pre.style.position = 'relative';
                    pre.insertBefore(label, block);
                }
            });

            // Make external links open in new tab
            this.$el.querySelectorAll('a[href^="http"]').forEach(link => {
                if (!link.hostname.includes(window.location.hostname)) {
                    link.setAttribute('target', '_blank');
                    link.setAttribute('rel', 'noopener noreferrer');
                    
                    // Add external link icon
                    const icon = document.createElement('svg');
                    icon.className = 'inline-block w-4 h-4 ml-1';
                    icon.setAttribute('fill', 'none');
                    icon.setAttribute('stroke', 'currentColor');
                    icon.setAttribute('viewBox', '0 0 24 24');
                    icon.setAttribute('aria-hidden', 'true');
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />';
                    link.appendChild(icon);
                }
            });

            // Add copy button to code blocks
            this.$el.querySelectorAll('pre').forEach(pre => {
                const button = document.createElement('button');
                button.textContent = 'Copy';
                button.className = 'absolute top-2 right-2 text-xs text-gray-400 hover:text-gray-200 bg-gray-800 hover:bg-gray-700 px-3 py-1 rounded transition-colors';
                button.setAttribute('aria-label', 'Copy code to clipboard');
                
                button.addEventListener('click', async () => {
                    const code = pre.querySelector('code');
                    const text = code.textContent;
                    
                    try {
                        await navigator.clipboard.writeText(text);
                        button.textContent = 'Copied!';
                        button.classList.add('text-green-400');
                        
                        setTimeout(() => {
                            button.textContent = 'Copy';
                            button.classList.remove('text-green-400');
                        }, 2000);
                    } catch (err) {
                        console.error('Failed to copy:', err);
                        button.textContent = 'Failed';
                        
                        setTimeout(() => {
                            button.textContent = 'Copy';
                        }, 2000);
                    }
                });
                
                pre.style.position = 'relative';
                pre.appendChild(button);
            });

            // Add table wrapper for responsive scrolling
            this.$el.querySelectorAll('table').forEach(table => {
                if (!table.parentElement.classList.contains('table-wrapper')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-wrapper overflow-x-auto -mx-4 sm:mx-0';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });
        }
    }));
});
</script>
@endpush
