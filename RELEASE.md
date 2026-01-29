# Release & Build Process

This document defines the standard procedures for building, testing, and releasing LinkHub. Agents should follow this checklist to ensure consistency.

## 1. Test Build (Local)
Used during active development to verify changes on the local test environment.

1.  **Run Build Script**:
    ```powershell
    .\build.ps1
    ```
    *   This script automatically builds the plugin and copies it to the local test site (`E:\laragon\www\linkhub\wp-content\plugins\linkhub`).
2.  **Verify**: Log in to the local WordPress admin and check the changes.

## 2. Release Process (Production)
Perform these steps when preparing a new version for public release.

### Step 1: Update Version Numbers
Update the version string (e.g., `0.4.5` -> `0.5.0`) in the following files:

1.  **`linkhub.php`**:
    *   Header: `* Version: 0.5.0`
    *   Constant: `define('LH_VERSION', '0.5.0');`
2.  **`package.json`**:
    *   `"version": "0.5.0"`
3.  **`readme.txt`**:
    *   `Stable tag: 0.5.0`

### Step 2: Update Changelog
1.  Open **`CHANGELOG.md`**.
2.  Add a new section at the top under `[Unreleased]` (or create a new header):
    ```markdown
    ## [0.5.0] - YYYY-MM-DD
    ### Added
    - New features...
    ### Changed
    - Updates...
    ### Fixed
    - Bug fixes...
    ```

### Step 3: Build
1.  Run the build script:
    ```powershell
    .\build.ps1
    ```
2.  **Verify Output**: Check `dist/` folder for the new ZIP file (e.g., `linkhub-0.5.0.zip`).
3.  **Check Contents**: Ensure the ZIP file structure is correct (root folder is `linkhub/`).

### Step 4: Git Commit & Tag
1.  Stage all changed files (including version bumps and changelog).
2.  Commit: `git commit -m "Release v0.5.0"`
3.  Tag (if applicable for the repo workflow): `git tag v0.5.0`
4.  Push: `git push origin main --tags`

---

## Agent Checklist
When asked to "release" or "deploy", always check:
- [ ] Has `CHANGELOG.md` been updated?
- [ ] Do versions match in `linkhub.php`, `package.json`, and `readme.txt`?
- [ ] Did `build.ps1` run successfully?
