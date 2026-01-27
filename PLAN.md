# Implementation Plan - LinkHub

## Refactor / Structure
- [x] Create standardized plugin structure
    - [x] `includes/`
    - [x] `assets/`
    - [x] `templates/` (Moved to `includes/templates`)
- [x] Implement Namespacing (`LinkHub\` prefix)
- [x] Create class autoloader

## Features
- [x] **Link Tree Builder** (Core)
    - [x] React-like DOM builder in Vanilla JS
    - [x] Drag & Drop sorting (SortableJS)
    - [x] Live Preview (iFrame)
- [x] **Post Types**
    - [x] `lh_link` (Individual links)
    - [x] `lh_tree` (Tree definitions)
- [x] **Collections** (v0.4.0)
    - [x] Group links into collapsible folders
    - [x] Recursive rendering
- [x] **Analytics** (v0.5.0)
    - [x] Custom DB Table (`wp_lh_analytics`)
    - [x] Click Logging (Anonymized IPs)
    - [x] REST API Endpoint for stats
    - [x] Admin Dashboard Chart (Chart.js)
    - [x] Top Links Report

## Admin UI
- [x] Single Page Application (SPA) feel
- [x] Tabbed Settings (Profile, Social, Links, Appearance, Analytics)
- [x] Color Pickers & Font Selectors
- [x] Import/Export (JSON & CSV)

## Distribution
- [x] Build scripts (PowerShell/Bash)
- [x] GitHub Updater integration
- [x] Version tagging
