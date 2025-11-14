# Component Documentation

This directory contains comprehensive documentation for Laravel Blade components used in the TechNewsHub application.

## Available Components

### Layout Components

- **[Header](./header.md)** - Main navigation header with sticky positioning, scroll behavior, and dark mode support

## Documentation Structure

Each component documentation includes:

1. **Overview** - Component purpose and key features
2. **Props** - Available properties with types and defaults
3. **Usage Examples** - Code examples for common use cases
4. **Behavior** - Interactive behavior and state management
5. **Styling** - Responsive design and dark mode support
6. **Accessibility** - ARIA attributes and keyboard navigation
7. **Dependencies** - Required child components and JavaScript
8. **Testing** - How to run component tests
9. **Customization** - Common modifications and adjustments
10. **Troubleshooting** - Common issues and solutions

## Testing Components

All components have comprehensive PHPUnit test coverage. Run tests for a specific component:

```bash
php artisan test --filter=HeaderComponentTest
```

Run all frontend component tests:

```bash
php artisan test tests/Feature/Frontend/
```

## Component Standards

### Blade Components

- Use Alpine.js for reactive behavior
- Support dark mode with `dark:` classes
- Include proper ARIA attributes
- Responsive design (mobile-first)
- Semantic HTML structure

### Testing

- Test all props and variants
- Verify accessibility attributes
- Check responsive classes
- Test dark mode support
- Verify Alpine.js integration

### Documentation

- Include usage examples
- Document all props
- Show customization options
- List dependencies
- Provide troubleshooting tips

## Contributing

When adding new components:

1. Create the Blade component file
2. Write comprehensive tests
3. Document in this directory
4. Update this README

## Related Documentation

- [Frontend Architecture](../frontend/README.md)
- [Testing Guide](../project/testing.md)
- [Accessibility Guidelines](../project/accessibility.md)
