# LinkHub Free - Development Plan

## Overview
Free version of LinkHub - standalone WordPress plugin for creating link tree pages without any page builder dependencies.

## Current Status: ✅ Rebranding Complete!

### Completed 
- [x] Created linkhub folder from linkhub copy
- [x] Renamed main plugin file to linkhub.php
- [x] Excluded Divi-dependent folders (visual-builder/, src/)
- [x] Global identifier replacement (DTOL → LH, dtol → lh, etc.)
- [x] Deleted LinkTypeDesignPostType.php and LinkTypeDesignHelp.php
- [x] Removed Divi hooks from linkhub.php
- [x] Stripped custom design rendering from LinkTypeRenderer.php
- [x] Removed design meta boxes from MetaBoxes.php
- [x] Updated composer.json, package.json, readme.txt, README.md
- [x] Removed META_LINK_TYPE_DESIGN constant from LinkPostType.php

### Next Steps (Testing Phase)
1. **Testing**
   - Install plugin without Divi theme
   - Test legacy styles (bar, card, heading)
   - Verify click tracking
   - Test import from Clickwhale CSV
   - Test export/import functionality

## Features (Free Version Only)
- Tree & Link CPTs
- Legacy display styles: bar, card, heading
- Click tracking (/go/{id}/)
- Per-link colors
- Tree styling (hero image, social links, backgrounds)
- Export/Import
- Clickwhale CSV importer

## Dependencies
- WordPress 6.0+
- PHP 7.4+
- NO Divi required

---
Created: January 23, 2026
