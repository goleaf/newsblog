# Keyboard Navigation Guide

## Overview

This document provides comprehensive guidelines for implementing and testing keyboard navigation across the TechNewsHub platform to ensure WCAG 2.1 AA compliance (Success Criterion 2.1.1 - Keyboard).

## Keyboard Navigation Requirements

### WCAG 2.1 Success Criteria

**2.1.1 Keyboard (Level A)**
All functionality must be operable through a keyboard interface without requiring specific timings for individual keystrokes.

**2.1.2 No Keyboard Trap (Level A)**
If keyboard focus can be moved to a component, it must be possible to move focus away using only the keyboard.

**2.1.3 Keyboard (No Exception) (Level AAA)**
All functionality must be operable through a keyboard interface.

## Standard Keyboard Interactions

### Navigation Keys

| Key | Action |
|-----|--------|
| `Tab` | Move focus to next focusable element |
| `Shift + Tab` | Move focus to previous focusable element |
| `Enter` | Activate links and buttons |
| `Space` | Activate buttons, toggle checkboxes |
| `Escape` | Close modals, dropdowns, and overlays |
| `Arrow Keys` | Navigate within components (menus, lists, tabs) |
| `Home` | Move to first item in a list |
| `End` | Move to last item in a list |

### Application-Specific Shortcuts

| Key | Action | Context |
|-----|--------|---------|
| `/` | Focus search input | Global |
| `?` | Show keyboard shortcuts help | Global |
| `Escape` | Close current modal/dropdown | Global |
| `n` | Next article in series | Article page |
| `p` | Previous article in series | Article page |
| `b` | Toggle bookmark | Article page |
| `s` | Share article | Article page |

## Component-Specific Keyboard Navigation

### 1. Main Navigation

**Implementation**:
```blade
<nav aria-label="Main navigation">
    <ul role="list">
        <li>
            <a href="/" 
               class="focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
               aria-current="{{ request()->is('/') ? 'page' : 'false' }}">
                Home
            </a>
        </li>
        <li>
            <a href="/categories" 
               class="focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Categories
            </a>
        </li>
    </ul>
</nav>
```

**Keyboard Behavior**:
- `Tab`: Navigate through links
- `Enter`: Activate link
- `Shift + Tab`: Navigate backwards

### 2. Search Autocomplete

**Implementation**:
```javascript
Alpine.data('searchBar', () => ({
    query: '',
    results: [],
    selectedIndex: -1,
    
    handleKeydown(event) {
        switch(event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.selectedIndex = Math.min(
                    this.selectedIndex + 1,
                    this.results.length - 1
                );
                this.scrollToSelected();
                break;
                
            case 'ArrowUp':
                event.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.scrollToSelected();
                break;
                
            case 'Enter':
                event.preventDefault();
                if (this.selectedIndex >= 0) {
                    this.selectResult(this.results[this.selectedIndex]);
                } else {
                    this.submitSearch();
                }
                break;
                
            case 'Escape':
                this.closeResults();
                break;
        }
    },
    
    scrollToSelected() {
        const selected = this.$refs.results?.children[this.selectedIndex];
        selected?.scrollIntoView({ block: 'nearest' });
    }
}));
```

**Keyboard Behavior**:
- `Type`: Show autocomplete results
- `Arrow Down`: Move to next result
- `Arrow Up`: Move to previous result
- `Enter`: Select highlighted result or submit search
- `Escape`: Close autocomplete dropdown

### 3. Dropdown Menus

**Implementation**:
```blade
<div x-data="{ open: false }" @keydown.escape="open = false">
    <button 
        @click="open = !open"
        @keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.firstItem.focus())"
        :aria-expanded="open"
        aria-controls="dropdown-menu"
        class="focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
        Menu
    </button>
    
    <div 
        x-show="open"
        id="dropdown-menu"
        role="menu"
        @keydown.arrow-down.prevent="$focus.next()"
        @keydown.arrow-up.prevent="$focus.previous()"
        @keydown.home.prevent="$focus.first()"
        @keydown.end.prevent="$focus.last()"
    >
        <a href="#" role="menuitem" x-ref="firstItem">Item 1</a>
        <a href="#" role="menuitem">Item 2</a>
        <a href="#" role="menuitem">Item 3</a>
    </div>
</div>
```

**Keyboard Behavior**:
- `Enter/Space`: Open dropdown
- `Arrow Down`: Open dropdown and focus first item, or move to next item
- `Arrow Up`: Move to previous item
- `Home`: Move to first item
- `End`: Move to last item
- `Escape`: Close dropdown
- `Tab`: Close dropdown and move to next focusable element

### 4. Modal Dialogs

**Implementation**:
```blade
<div 
    x-data="{ open: false }"
    x-trap="open"
    @keydown.escape="open = false"
>
    <button @click="open = true">Open Modal</button>
    
    <div 
        x-show="open"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        class="fixed inset-0 z-50"
    >
        <div class="modal-content">
            <h2 id="modal-title">Modal Title</h2>
            
            <button 
                @click="open = false"
                aria-label="Close modal"
                class="focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                Close
            </button>
        </div>
    </div>
</div>
```

**Keyboard Behavior**:
- `Tab`: Cycle through focusable elements within modal (focus trap)
- `Shift + Tab`: Cycle backwards through modal elements
- `Escape`: Close modal
- Focus returns to trigger element when closed

### 5. Tabs

**Implementation**:
```blade
<div x-data="{ activeTab: 0 }">
    <div role="tablist" aria-label="Content tabs">
        <button 
            role="tab"
            :aria-selected="activeTab === 0"
            :tabindex="activeTab === 0 ? 0 : -1"
            @click="activeTab = 0"
            @keydown.arrow-right.prevent="activeTab = 1; $nextTick(() => $refs.tab1.focus())"
            class="focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            Tab 1
        </button>
        <button 
            role="tab"
            :aria-selected="activeTab === 1"
            :tabindex="activeTab === 1 ? 0 : -1"
            @click="activeTab = 1"
            @keydown.arrow-left.prevent="activeTab = 0; $nextTick(() => $refs.tab0.focus())"
            x-ref="tab1"
            class="focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            Tab 2
        </button>
    </div>
    
    <div role="tabpanel" x-show="activeTab === 0">Panel 1</div>
    <div role="tabpanel" x-show="activeTab === 1">Panel 2</div>
</div>
```

**Keyboard Behavior**:
- `Tab`: Move focus into tab list, then to tab panel
- `Arrow Left/Right`: Navigate between tabs
- `Home`: Move to first tab
- `End`: Move to last tab
- `Enter/Space`: Activate focused tab

### 6. Accordion

**Implementation**:
```blade
<div x-data="{ open: false }">
    <button
        @click="open = !open"
        :aria-expanded="open"
        aria-controls="panel-1"
        class="focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
        Section Title
    </button>
    
    <div 
        x-show="open"
        id="panel-1"
        role="region"
        aria-labelledby="button-1"
    >
        Content
    </div>
</div>
```

**Keyboard Behavior**:
- `Enter/Space`: Toggle accordion panel
- `Tab`: Move to next focusable element

### 7. Form Controls

**Implementation**:
```blade
<form>
    <label for="email" class="block">Email</label>
    <input 
        type="email" 
        id="email"
        class="focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        aria-describedby="email-help"
        aria-required="true"
    />
    <p id="email-help" class="text-sm text-gray-600">
        We'll never share your email
    </p>
    
    <button 
        type="submit"
        class="focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
    >
        Submit
    </button>
</form>
```

**Keyboard Behavior**:
- `Tab`: Navigate between form fields
- `Enter`: Submit form (when focus is on submit button)
- `Space`: Toggle checkboxes and radio buttons
- `Arrow Keys`: Navigate radio button groups

## Focus Management

### Focus Indicators

All interactive elements must have visible focus indicators:

```css
/* Global focus styles */
*:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Tailwind focus utilities */
.focus\:ring-2:focus {
    --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
    --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
    box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}
```

### Focus Trap

Modals and dialogs must trap focus:

```javascript
// Alpine.js focus trap
<div x-trap="open">
    <!-- Focus is trapped within this element when open is true -->
</div>
```

### Skip Links

Provide skip links for keyboard users:

```blade
<a 
    href="#main-content" 
    class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded"
>
    Skip to main content
</a>

<main id="main-content" tabindex="-1">
    <!-- Main content -->
</main>
```

## Testing Checklist

### Manual Testing

- [ ] Unplug mouse and navigate entire site using only keyboard
- [ ] Verify all interactive elements are reachable via Tab
- [ ] Verify focus indicators are visible on all elements
- [ ] Test that Tab order follows logical reading order
- [ ] Verify Escape closes all modals and dropdowns
- [ ] Test that focus is trapped in modals
- [ ] Verify focus returns to trigger element when closing modals
- [ ] Test search autocomplete with arrow keys
- [ ] Test dropdown menus with arrow keys
- [ ] Verify forms can be completed using only keyboard
- [ ] Test that Enter activates links and buttons
- [ ] Test that Space activates buttons and toggles
- [ ] Verify no keyboard traps exist
- [ ] Test skip to main content link

### Automated Testing

```bash
# Run keyboard navigation tests
php artisan test --filter=KeyboardNavigationTest

# Run accessibility audit
npm run test:a11y
```

### Browser Testing

Test keyboard navigation in:
- Chrome (Windows/Mac)
- Firefox (Windows/Mac)
- Safari (Mac)
- Edge (Windows)

## Common Issues and Solutions

### Issue 1: Div Buttons

**Problem**: Using `<div>` with click handlers instead of `<button>`

**Solution**:
```blade
<!-- Bad -->
<div @click="doSomething()">Click me</div>

<!-- Good -->
<button @click="doSomething()">Click me</button>
```

### Issue 2: Missing Focus Indicators

**Problem**: Focus indicators removed with `outline: none`

**Solution**:
```css
/* Bad */
button:focus {
    outline: none;
}

/* Good */
button:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Or use Tailwind */
<button class="focus:outline-none focus:ring-2 focus:ring-blue-500">
```

### Issue 3: Positive Tabindex

**Problem**: Using positive tabindex values breaks natural tab order

**Solution**:
```blade
<!-- Bad -->
<button tabindex="1">First</button>
<button tabindex="2">Second</button>

<!-- Good -->
<button>First</button>
<button>Second</button>

<!-- Or use tabindex="0" to add to tab order -->
<div role="button" tabindex="0">Custom button</div>

<!-- Or use tabindex="-1" to remove from tab order -->
<div tabindex="-1">Not focusable</div>
```

### Issue 4: Keyboard Trap

**Problem**: Focus gets stuck in a component

**Solution**:
```javascript
// Ensure Escape key always works
@keydown.escape="closeComponent()"

// Use Alpine.js focus trap correctly
<div x-trap.noscroll="open">
    <!-- Content -->
</div>
```

### Issue 5: Missing Arrow Key Navigation

**Problem**: Dropdowns and menus don't support arrow keys

**Solution**:
```javascript
@keydown.arrow-down.prevent="$focus.next()"
@keydown.arrow-up.prevent="$focus.previous()"
@keydown.home.prevent="$focus.first()"
@keydown.end.prevent="$focus.last()"
```

## Keyboard Shortcuts Help Modal

Provide a help modal showing available keyboard shortcuts:

```blade
<div x-data="{ showHelp: false }" @keydown.shift./.window="showHelp = true">
    <!-- Keyboard shortcuts help modal -->
    <div x-show="showHelp" x-trap="showHelp" @keydown.escape="showHelp = false">
        <h2>Keyboard Shortcuts</h2>
        <dl>
            <dt>/</dt>
            <dd>Focus search</dd>
            
            <dt>?</dt>
            <dd>Show this help</dd>
            
            <dt>Escape</dt>
            <dd>Close modals and dropdowns</dd>
            
            <dt>n</dt>
            <dd>Next article in series</dd>
            
            <dt>p</dt>
            <dd>Previous article in series</dd>
        </dl>
    </div>
</div>
```

## Resources

- [WCAG 2.1 Keyboard Guidelines](https://www.w3.org/WAI/WCAG21/Understanding/keyboard)
- [ARIA Authoring Practices - Keyboard Navigation](https://www.w3.org/WAI/ARIA/apg/practices/keyboard-interface/)
- [WebAIM Keyboard Accessibility](https://webaim.org/techniques/keyboard/)
- [MDN Keyboard-navigable JavaScript widgets](https://developer.mozilla.org/en-US/docs/Web/Accessibility/Keyboard-navigable_JavaScript_widgets)

## Next Steps

1. Implement keyboard navigation patterns in all components
2. Add focus indicators to all interactive elements
3. Implement focus trap in modals
4. Add skip to main content link
5. Test keyboard navigation manually
6. Run automated keyboard navigation tests
7. Document keyboard shortcuts for users
8. Create keyboard shortcuts help modal

## Conclusion

Proper keyboard navigation is essential for accessibility. All interactive elements must be keyboard accessible, have visible focus indicators, and follow standard keyboard interaction patterns. Regular testing with keyboard-only navigation ensures compliance with WCAG 2.1 AA standards.
