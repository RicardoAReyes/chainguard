# WordPress Theme Development Workflow

## Overview

Specialized workflow for creating custom WordPress themes from scratch, including modern block editor (Gutenberg) support, template hierarchy, responsive design, and WordPress 7.0 enhancements.

## WordPress 7.0 Theme Features

1. **Admin Refresh** — New default color scheme, view transitions, modern typography
2. **Pattern Editing** — ContentOnly mode defaults for unsynced patterns, per-block instance custom CSS
3. **Navigation Overlays** — Customizable navigation overlays, improved mobile navigation
4. **New Blocks** — Icon block, Breadcrumbs block with filters, Responsive grid block
5. **Theme.json Enhancements** — Pseudo-element support, block-defined feature selectors, enhanced custom CSS
6. **Iframed Editor** — Block API v3+ enables iframed post editor

## When to Use This Workflow

- Creating custom WordPress themes
- Converting designs to WordPress themes
- Adding block editor support
- Implementing custom post types
- Building child themes
- Implementing WordPress 7.0 design features

## Workflow Phases

### Phase 1: Theme Setup
1. Create theme directory structure
2. Set up style.css with theme header
3. Create functions.php
4. Configure theme support
5. Set up enqueue scripts/styles

### Phase 2: Template Hierarchy
1. Create index.php (fallback template)
2. Implement header.php and footer.php
3. Create single.php for posts
4. Create page.php for pages
5. Add archive.php for archives
6. Implement search.php and 404.php

### Phase 3: Theme Functions
1. Register navigation menus
2. Add theme support (thumbnails, RSS, etc.)
3. Register widget areas
4. Create custom template tags
5. Implement helper functions

**theme.json v3 example:**
```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "appearanceTools": true,
    "layout": { "contentSize": "1200px", "wideSize": "1400px" }
  }
}
```

### Phase 4: Custom Post Types
1. Register custom post types with `show_in_rest: true`
2. Create custom taxonomies
3. Add custom meta boxes
4. Implement custom fields
5. Create archive templates

### Phase 5: Block Editor Support
1. Enable block editor support
2. Register custom blocks
3. Create block styles
4. Add block patterns
5. Configure block templates

**Navigation Overlay Template Part:**
```php
// template-parts/header-overlay.php
wp_nav_menu([
    'theme_location' => 'primary',
    'container' => false,
    'menu_class' => 'overlay-menu',
]);
```

### Phase 6: Styling and Design
1. Implement responsive design
2. Add CSS framework or custom styles
3. Create design system
4. Implement theme customizer
5. Add accessibility features

### Phase 7: WordPress 7.0 Features Integration
- Breadcrumbs block filters for custom post types
- Icon block pattern registration
- ContentOnly block patterns
- View transitions support

### Phase 8: Testing
1. Test across browsers
2. Verify responsive breakpoints
3. Test block editor
4. Check accessibility
5. Performance testing

## Theme Directory Structure

```
theme-name/
├── style.css
├── functions.php
├── index.php
├── header.php / footer.php
├── single.php / page.php / archive.php
├── template-parts/
├── patterns/
├── templates/
├── inc/
└── assets/
```

## Quality Gates

- All templates working
- Block editor supported
- Responsive design verified
- Accessibility checked (WCAG 2.1 AA)
- Performance optimized
- Cross-browser tested
- WordPress 7.0 compatibility verified
