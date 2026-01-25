/**
 * LinkHub Tree Builder Admin UI
 */
(function($) {
    'use strict';

    const TreeBuilder = {
        // State
        treeId: null,
        tree: null,
        allLinks: [],
        isDirty: false,
        saveTimeout: null,
        currentView: 'links',

        // DOM elements cache
        els: {},

        /**
         * Initialize the builder
         */
        init() {
            this.treeId = parseInt($('#lh-tree-builder').data('tree-id')) || 0;
            this.cacheElements();
            this.bindEvents();
            this.initColorPickers();
            this.populateFontSelects();
            this.loadTreeList();
            this.loadAllLinks();

            if (this.treeId) {
                this.loadTree(this.treeId);
            }
        },

        /**
         * Cache DOM elements
         */
        cacheElements() {
            this.els = {
                builder: $('#lh-tree-builder'),
                treeList: $('#lh-tree-list'),
                emptyState: $('#lh-builder-empty'),
                content: $('#lh-builder-content'),
                linksContainer: $('#lh-links-container'),
                previewFrame: $('#lh-preview-frame'),
                treeTitle: $('#lh-tree-title'),
                treeStatus: $('#lh-tree-status'),
                saveStatus: $('#lh-save-status'),
                viewBtn: $('#lh-view-tree-btn'),
                classicEditorBtn: $('#lh-classic-editor-btn'),
                addLinkBtn: $('#lh-add-link-btn'),
                addHeadingBtn: $('#lh-add-heading-btn'),
                addExistingBtn: $('#lh-add-existing-btn'),
                newTreeBtn: $('#lh-new-tree-btn'),
                refreshPreview: $('#lh-refresh-preview'),
                settingsNav: $('#lh-tree-settings-nav'),
                settingsNavList: $('.lh-settings-nav-list'),
                socialLinksList: $('#lh-social-links-list'),
                views: {
                    links: $('#lh-view-links'),
                    profile: $('#lh-view-profile'),
                    social: $('#lh-view-social'),
                    appearance: $('#lh-view-appearance'),
                    display: $('#lh-view-display'),
                },
            };
        },

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Tree list
            this.els.treeList.on('click', 'button[data-tree-id]', (e) => {
                e.preventDefault();
                const id = $(e.currentTarget).data('tree-id');
                this.loadTree(id);
            });

            // New tree button
            this.els.newTreeBtn.on('click', () => this.openModal('create-tree'));

            // Add buttons
            this.els.addLinkBtn.on('click', () => this.openModal('create-link'));
            this.els.addHeadingBtn.on('click', () => this.openModal('add-heading'));
            this.els.addExistingBtn.on('click', () => this.openExistingLinkModal());

            // Refresh preview
            this.els.refreshPreview.on('click', () => this.refreshPreview());

            // Title editing
            this.els.treeTitle.on('blur', () => this.onTitleChange());
            this.els.treeTitle.on('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.els.treeTitle.blur();
                }
            });

            // Links container events (delegated)
            this.els.linksContainer
                .on('click', '.lh-edit-btn', (e) => this.onEditLink(e))
                .on('click', '.lh-edit-heading-btn', (e) => this.onEditHeading(e))
                .on('click', '.lh-remove-btn', (e) => this.onRemoveItem(e))
                .on('blur', '.lh-card-title[contenteditable]', (e) => this.onCardTitleChange(e))
                .on('keydown', '.lh-card-title[contenteditable]', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        $(e.target).blur();
                    }
                });

            // Drag and drop
            this.initDragDrop();

            // Settings nav view switching
            this.els.settingsNavList.on('click', 'button[data-view]', (e) => {
                e.preventDefault();
                const view = $(e.currentTarget).data('view');
                this.switchView(view);
            });

            // Settings changes
            this.bindSettingsEvents();

            // Modal events
            this.bindModalEvents();

            // Social links
            $('#lh-add-social-btn').on('click', () => this.addSocialLinkRow());
            this.els.socialLinksList.on('click', '.lh-remove-social', (e) => {
                $(e.currentTarget).closest('.lh-social-link-row').remove();
                this.markDirty();
                this.debounceSaveSettings();
            });
            this.els.socialLinksList.on('change', 'select, input', () => {
                this.markDirty();
                this.debounceSaveSettings();
            });

            // Image uploads
            this.bindImageUploads();
        },

        /**
         * Initialize drag and drop
         */
        initDragDrop() {
            let draggedItem = null;

            this.els.linksContainer.on('dragstart', '.lh-link-card', (e) => {
                draggedItem = e.currentTarget;
                $(draggedItem).addClass('dragging');
                e.originalEvent.dataTransfer.effectAllowed = 'move';
            });

            this.els.linksContainer.on('dragend', '.lh-link-card', (e) => {
                $(e.currentTarget).removeClass('dragging');
                $('.lh-link-card').removeClass('drag-over');
                draggedItem = null;
            });

            this.els.linksContainer.on('dragover', '.lh-link-card', (e) => {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                const target = $(e.currentTarget);
                if (!target.is(draggedItem)) {
                    target.addClass('drag-over');
                }
            });

            this.els.linksContainer.on('dragleave', '.lh-link-card', (e) => {
                $(e.currentTarget).removeClass('drag-over');
            });

            this.els.linksContainer.on('drop', '.lh-link-card', (e) => {
                e.preventDefault();
                const target = $(e.currentTarget);
                target.removeClass('drag-over');

                if (draggedItem && !target.is(draggedItem)) {
                    const $dragged = $(draggedItem);
                    const targetIndex = target.index();
                    const draggedIndex = $dragged.index();

                    if (draggedIndex < targetIndex) {
                        target.after($dragged);
                    } else {
                        target.before($dragged);
                    }

                    this.saveLinksOrder();
                }
            });
        },

        /**
         * Bind settings field events
         */
        bindSettingsEvents() {
            // Text inputs
            $('#lh-about-text').on('change', () => this.debounceSaveSettings());

            // Selects
            $('#lh-hero-shape, #lh-social-style, #lh-title-font, #lh-body-font, #lh-heading-size')
                .on('change', () => this.debounceSaveSettings());

            // Checkboxes
            $('#lh-hero-fade, #lh-hide-header-footer').on('change', () => this.debounceSaveSettings());
        },

        /**
         * Initialize color pickers
         */
        initColorPickers() {
            $('.lh-color-picker').wpColorPicker({
                change: () => {
                    this.markDirty();
                    this.debounceSaveSettings();
                },
                clear: () => {
                    this.markDirty();
                    this.debounceSaveSettings();
                }
            });
        },

        /**
         * Populate font select dropdowns
         */
        populateFontSelects() {
            const fonts = lhTreeBuilder.fonts;
            const options = Object.entries(fonts)
                .map(([value, label]) => `<option value="${value}">${label}</option>`)
                .join('');

            $('#lh-title-font, #lh-body-font').html(options);
        },

        /**
         * Bind modal events
         */
        bindModalEvents() {
            // Close modal on overlay click or close button
            $('.lh-modal-overlay, .lh-modal-close, .lh-modal-cancel').on('click', () => {
                this.closeModals();
            });

            // Prevent close when clicking modal content
            $('.lh-modal-content').on('click', (e) => e.stopPropagation());

            // Create link submit
            $('#lh-create-link-submit').on('click', () => this.createLink());

            // Edit link submit
            $('#lh-edit-link-submit').on('click', () => this.saveEditedLink());

            // Edit heading submit
            $('#lh-edit-heading-submit').on('click', () => this.saveEditedHeading());

            // Add heading submit
            $('#lh-add-heading-submit').on('click', () => this.addHeading());

            // Add existing link submit
            $('#lh-add-existing-submit').on('click', () => this.addExistingLink());

            // Create tree submit
            $('#lh-create-tree-submit').on('click', () => this.createTree());

            // Enter key in modals
            $('.lh-modal input').on('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $(e.target).closest('.lh-modal').find('.lh-btn-primary').click();
                }
            });
        },

        /**
         * Bind image upload buttons
         */
        bindImageUploads() {
            // Header image
            this.createMediaUploader('header-image', (id, url) => {
                $('#lh-header-image-id').val(id);
                $('#lh-header-image-preview').html(`<img src="${url}" alt="">`).addClass('has-image');
                $('#lh-header-image-remove').show();
                this.debounceSaveSettings();
            });

            $('#lh-header-image-remove').on('click', () => {
                $('#lh-header-image-id').val('');
                $('#lh-header-image-preview').html('').removeClass('has-image');
                $('#lh-header-image-remove').hide();
                this.debounceSaveSettings();
            });

            // Background image
            this.createMediaUploader('bg-image', (id, url) => {
                $('#lh-bg-image-id').val(id);
                $('#lh-bg-image-preview').html(`<img src="${url}" alt="">`).addClass('has-image');
                $('#lh-bg-image-remove').show();
                this.debounceSaveSettings();
            });

            $('#lh-bg-image-remove').on('click', () => {
                $('#lh-bg-image-id').val('');
                $('#lh-bg-image-preview').html('').removeClass('has-image');
                $('#lh-bg-image-remove').hide();
                this.debounceSaveSettings();
            });

            // New link image
            this.createMediaUploader('new-link-image', (id, url) => {
                $('#lh-new-link-image-id').val(id);
                $('#lh-new-link-image-preview').html(`<img src="${url}" alt="">`).addClass('has-image');
            });

            // Edit link image
            this.createMediaUploader('edit-link-image', (id, url) => {
                $('#lh-edit-link-image-id').val(id);
                $('#lh-edit-link-image-preview').html(`<img src="${url}" alt="">`).addClass('has-image');
                $('#lh-edit-link-image-remove').show();
            });

            $('#lh-edit-link-image-remove').on('click', () => {
                $('#lh-edit-link-image-id').val('');
                $('#lh-edit-link-image-preview').html('').removeClass('has-image');
                $('#lh-edit-link-image-remove').hide();
            });
        },

        /**
         * Create media uploader for an image field
         */
        createMediaUploader(prefix, callback) {
            let frame;

            $(`#lh-${prefix}-btn`).on('click', () => {
                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: lhTreeBuilder.strings.selectImage,
                    button: { text: lhTreeBuilder.strings.useImage },
                    multiple: false
                });

                frame.on('select', () => {
                    const attachment = frame.state().get('selection').first().toJSON();
                    callback(attachment.id, attachment.sizes?.thumbnail?.url || attachment.url);
                });

                frame.open();
            });
        },

        /**
         * API helper
         */
        async api(endpoint, options = {}) {
            const response = await fetch(lhTreeBuilder.apiBase + endpoint, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': lhTreeBuilder.nonce,
                    ...options.headers
                }
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || `API Error: ${response.status}`);
            }

            return response.json();
        },

        /**
         * Load tree list for sidebar
         */
        async loadTreeList() {
            try {
                const trees = await this.api('/trees');
                this.renderTreeList(trees);
            } catch (error) {
                console.error('Failed to load trees:', error);
                this.els.treeList.html(`<li class="lh-error">${lhTreeBuilder.strings.error}</li>`);
            }
        },

        /**
         * Render tree list in sidebar
         */
        renderTreeList(trees) {
            if (!trees.length) {
                this.els.treeList.html(`<li class="lh-empty">${lhTreeBuilder.strings.noTrees}</li>`);
                return;
            }

            const html = trees.map(tree => `
                <li class="${tree.id === this.treeId ? 'active' : ''}">
                    <button type="button" data-tree-id="${tree.id}">
                        ${this.escapeHtml(tree.title || '(Untitled)')}
                    </button>
                </li>
            `).join('');

            this.els.treeList.html(html);
        },

        /**
         * Load all links for the "Add Existing" dropdown
         */
        async loadAllLinks() {
            try {
                this.allLinks = await this.api('/links');
            } catch (error) {
                console.error('Failed to load links:', error);
            }
        },

        /**
         * Load a specific tree
         */
        async loadTree(id) {
            this.treeId = id;
            this.showLoading();

            try {
                this.tree = await this.api(`/trees/${id}`);
                this.renderTree();
                this.refreshPreview();

                // Update URL without reload
                const url = new URL(window.location);
                url.searchParams.set('tree_id', id);
                window.history.replaceState({}, '', url);

                // Update sidebar active state
                this.els.treeList.find('li').removeClass('active');
                this.els.treeList.find(`button[data-tree-id="${id}"]`).closest('li').addClass('active');

            } catch (error) {
                console.error('Failed to load tree:', error);
                alert('Failed to load tree: ' + error.message);
            }
        },

        /**
         * Show loading state
         */
        showLoading() {
            this.els.emptyState.hide();
            this.els.content.show();
            this.els.linksContainer.html(`<div class="lh-loading">${lhTreeBuilder.strings.loading}</div>`);
        },

        /**
         * Render the loaded tree
         */
        renderTree() {
            // Show content, hide empty state
            this.els.emptyState.hide();
            this.els.content.show();

            // Show settings nav in sidebar
            this.els.settingsNav.show();

            // Update header
            this.els.treeTitle.text(this.tree.title || '(Untitled)');
            this.els.treeStatus.text(this.tree.status).attr('class', `lh-status-badge ${this.tree.status}`);
            this.els.viewBtn.attr('href', this.tree.permalink);
            this.els.classicEditorBtn.attr('href', this.tree.edit_url);

            // Render link cards
            this.renderCards();

            // Populate settings
            this.populateSettings();

            // Switch to links view
            this.switchView('links');

            // Clear dirty state
            this.isDirty = false;
            this.els.saveStatus.text('');
        },

        /**
         * Switch between views (links, profile, social, appearance, display)
         */
        switchView(viewName) {
            this.currentView = viewName;

            // Hide all views
            Object.values(this.els.views).forEach(view => view.hide());

            // Show selected view
            if (this.els.views[viewName]) {
                this.els.views[viewName].show();
            }

            // Update nav active state
            this.els.settingsNavList.find('li').removeClass('active');
            this.els.settingsNavList.find(`button[data-view="${viewName}"]`).closest('li').addClass('active');
        },

        /**
         * Render link/heading cards
         */
        renderCards() {
            if (!this.tree.items || !this.tree.items.length) {
                this.els.linksContainer.html('<p class="lh-empty-links">No links yet. Add your first link above.</p>');
                return;
            }

            const html = this.tree.items.map((item, index) => this.createCardHtml(item, index)).join('');
            this.els.linksContainer.html(html);
        },

        /**
         * Create HTML for a card
         */
        createCardHtml(item, index) {
            if (item.type === 'heading') {
                return `
                    <div class="lh-link-card lh-heading-card" draggable="true" data-index="${index}" data-type="heading" data-text="${this.escapeAttr(item.text)}" data-size="${item.size || 'medium'}">
                        <span class="lh-drag-handle dashicons dashicons-menu"></span>
                        <span class="dashicons dashicons-editor-textcolor lh-heading-icon"></span>
                        <div class="lh-card-info">
                            <div class="lh-card-title" contenteditable="true" spellcheck="false">${this.escapeHtml(item.text)}</div>
                            <div class="lh-card-meta">Size: ${item.size || 'medium'}</div>
                        </div>
                        <div class="lh-card-actions">
                            <button type="button" class="lh-edit-heading-btn" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="lh-remove-btn" title="Remove">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    </div>
                `;
            }

            // Link card
            const thumbHtml = item.image_url
                ? `<img src="${this.escapeAttr(item.image_url)}" alt="">`
                : '<span class="dashicons dashicons-admin-links"></span>';

            return `
                <div class="lh-link-card" draggable="true" data-index="${index}" data-type="link" data-link-id="${item.link_id}">
                    <span class="lh-drag-handle dashicons dashicons-menu"></span>
                    <div class="lh-card-thumb">${thumbHtml}</div>
                    <div class="lh-card-info">
                        <div class="lh-card-title" contenteditable="true" spellcheck="false">${this.escapeHtml(item.title)}</div>
                        <div class="lh-card-url">${this.escapeHtml(item.url || '')}</div>
                    </div>
                    <div class="lh-card-stats">
                        <span class="dashicons dashicons-chart-bar"></span>
                        ${item.click_count || 0}
                    </div>
                    <div class="lh-card-actions">
                        <button type="button" class="lh-edit-btn" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="lh-remove-btn" title="Remove">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
            `;
        },

        /**
         * Populate settings panel with tree data
         */
        populateSettings() {
            const s = this.tree.settings;

            // Header image
            if (s.header_image_url) {
                $('#lh-header-image-id').val(s.header_image_id);
                $('#lh-header-image-preview').html(`<img src="${s.header_image_url}" alt="">`).addClass('has-image');
                $('#lh-header-image-remove').show();
            } else {
                $('#lh-header-image-id').val('');
                $('#lh-header-image-preview').html('').removeClass('has-image');
                $('#lh-header-image-remove').hide();
            }

            // Background image
            if (s.background_image_url) {
                $('#lh-bg-image-id').val(s.background_image_id);
                $('#lh-bg-image-preview').html(`<img src="${s.background_image_url}" alt="">`).addClass('has-image');
                $('#lh-bg-image-remove').show();
            } else {
                $('#lh-bg-image-id').val('');
                $('#lh-bg-image-preview').html('').removeClass('has-image');
                $('#lh-bg-image-remove').hide();
            }

            // Text fields
            $('#lh-about-text').val(s.about_text || '');

            // Selects
            $('#lh-hero-shape').val(s.hero_shape || 'round');
            $('#lh-social-style').val(s.social_style || 'circle');
            $('#lh-title-font').val(s.title_font || 'system');
            $('#lh-body-font').val(s.body_font || 'system');
            $('#lh-heading-size').val(s.heading_size || 'medium');

            // Checkboxes
            $('#lh-hero-fade').prop('checked', s.hero_fade);
            $('#lh-hide-header-footer').prop('checked', s.hide_header_footer);

            // Colors - need to use wpColorPicker API
            this.setColorPickerValue('#lh-background-color', s.background_color || '#8b8178');
            this.setColorPickerValue('#lh-tree-background-color', s.tree_background_color || '#f5f5f5');
            this.setColorPickerValue('#lh-title-color', s.title_color || '#1a1a1a');
            this.setColorPickerValue('#lh-bio-color', s.bio_color || '#555555');
            this.setColorPickerValue('#lh-link-background-color', s.link_background_color || '#eeeeee');
            this.setColorPickerValue('#lh-link-text-color', s.link_text_color || '#000000');
            this.setColorPickerValue('#lh-social-color', s.social_color || '#333333');

            // Social links
            this.renderSocialLinks(s.social_links || []);
        },

        /**
         * Set color picker value
         */
        setColorPickerValue(selector, value) {
            $(selector).wpColorPicker('color', value);
        },

        /**
         * Render social links
         */
        renderSocialLinks(links) {
            if (!links.length) {
                this.els.socialLinksList.html('');
                return;
            }

            const html = links.map(link => this.createSocialLinkRowHtml(link.platform, link.url)).join('');
            this.els.socialLinksList.html(html);
        },

        /**
         * Create social link row HTML
         */
        createSocialLinkRowHtml(platform = '', url = '') {
            const options = Object.entries(lhTreeBuilder.socialPlatforms)
                .map(([value, label]) => `<option value="${value}" ${value === platform ? 'selected' : ''}>${label}</option>`)
                .join('');

            return `
                <div class="lh-social-link-row">
                    <select class="lh-social-platform">${options}</select>
                    <input type="url" class="lh-social-url" value="${this.escapeAttr(url)}" placeholder="https://...">
                    <button type="button" class="lh-remove-social"><span class="dashicons dashicons-no-alt"></span></button>
                </div>
            `;
        },

        /**
         * Add a new social link row
         */
        addSocialLinkRow() {
            this.els.socialLinksList.append(this.createSocialLinkRowHtml());
        },

        /**
         * Refresh the preview iframe
         */
        refreshPreview() {
            if (this.tree?.preview_url) {
                const url = this.tree.preview_url + '&_t=' + Date.now();
                this.els.previewFrame.attr('src', url);
            }
        },

        /**
         * Mark state as dirty (unsaved changes)
         */
        markDirty() {
            this.isDirty = true;
            this.els.saveStatus.text(lhTreeBuilder.strings.saving).attr('class', 'lh-save-status saving');
        },

        /**
         * Show saved status
         */
        showSaved() {
            this.isDirty = false;
            this.els.saveStatus.text(lhTreeBuilder.strings.saved).attr('class', 'lh-save-status saved');
            setTimeout(() => {
                if (!this.isDirty) {
                    this.els.saveStatus.text('');
                }
            }, 2000);
        },

        /**
         * Show error status
         */
        showError() {
            this.els.saveStatus.text(lhTreeBuilder.strings.error).attr('class', 'lh-save-status error');
        },

        /**
         * Debounce save settings
         */
        debounceSaveSettings() {
            this.markDirty();
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => this.saveSettings(), 800);
        },

        /**
         * Save tree settings
         */
        async saveSettings() {
            if (!this.treeId) return;

            const settings = this.collectSettings();

            try {
                await this.api(`/trees/${this.treeId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ settings })
                });
                this.showSaved();
                this.refreshPreview();
            } catch (error) {
                console.error('Failed to save settings:', error);
                this.showError();
            }
        },

        /**
         * Collect all settings from the form
         */
        collectSettings() {
            return {
                title: this.els.treeTitle.text().trim(),
                header_image_id: parseInt($('#lh-header-image-id').val()) || null,
                about_text: $('#lh-about-text').val(),
                hero_shape: $('#lh-hero-shape').val(),
                hero_fade: $('#lh-hero-fade').is(':checked'),
                social_style: $('#lh-social-style').val(),
                social_links: this.collectSocialLinks(),
                background_color: $('#lh-background-color').wpColorPicker('color'),
                tree_background_color: $('#lh-tree-background-color').wpColorPicker('color'),
                background_image_id: parseInt($('#lh-bg-image-id').val()) || null,
                title_color: $('#lh-title-color').wpColorPicker('color'),
                bio_color: $('#lh-bio-color').wpColorPicker('color'),
                social_color: $('#lh-social-color').wpColorPicker('color'),
                link_background_color: $('#lh-link-background-color').wpColorPicker('color'),
                link_text_color: $('#lh-link-text-color').wpColorPicker('color'),
                title_font: $('#lh-title-font').val(),
                body_font: $('#lh-body-font').val(),
                heading_size: $('#lh-heading-size').val(),
                hide_header_footer: $('#lh-hide-header-footer').is(':checked'),
            };
        },

        /**
         * Collect social links from the form
         */
        collectSocialLinks() {
            const links = [];
            $('.lh-social-link-row').each(function() {
                const platform = $(this).find('.lh-social-platform').val();
                const url = $(this).find('.lh-social-url').val().trim();
                if (platform && url) {
                    links.push({ platform, url });
                }
            });
            return links;
        },

        /**
         * Save links order
         */
        async saveLinksOrder() {
            if (!this.treeId) return;

            this.markDirty();

            const items = [];
            this.els.linksContainer.find('.lh-link-card').each(function() {
                const $card = $(this);
                const type = $card.data('type');

                if (type === 'link') {
                    items.push({
                        type: 'link',
                        link_id: parseInt($card.data('link-id'))
                    });
                } else if (type === 'heading') {
                    items.push({
                        type: 'heading',
                        text: $card.find('.lh-card-title').text().trim(),
                        size: $card.data('size') || 'medium'
                    });
                }
            });

            try {
                await this.api(`/trees/${this.treeId}/links`, {
                    method: 'PUT',
                    body: JSON.stringify({ items })
                });
                this.tree.items = items;
                this.showSaved();
                this.refreshPreview();
            } catch (error) {
                console.error('Failed to save links order:', error);
                this.showError();
            }
        },

        /**
         * Handle title change
         */
        onTitleChange() {
            const newTitle = this.els.treeTitle.text().trim();
            if (newTitle !== this.tree.title) {
                this.tree.title = newTitle;
                this.debounceSaveSettings();
                // Update sidebar
                this.els.treeList.find(`button[data-tree-id="${this.treeId}"]`).text(newTitle || '(Untitled)');
            }
        },

        /**
         * Handle card title change (inline edit)
         */
        async onCardTitleChange(e) {
            const $card = $(e.target).closest('.lh-link-card');
            const type = $card.data('type');
            const newTitle = $(e.target).text().trim();

            if (type === 'link') {
                const linkId = $card.data('link-id');
                const item = this.tree.items.find(i => i.type === 'link' && i.link_id === linkId);
                if (item && item.title !== newTitle) {
                    try {
                        await this.api(`/links/${linkId}`, {
                            method: 'PUT',
                            body: JSON.stringify({ title: newTitle })
                        });
                        item.title = newTitle;
                        this.refreshPreview();
                    } catch (error) {
                        console.error('Failed to update link title:', error);
                    }
                }
            } else if (type === 'heading') {
                $card.data('text', newTitle);
                this.saveLinksOrder();
            }
        },

        /**
         * Handle edit link click - opens edit modal
         */
        onEditLink(e) {
            const $card = $(e.currentTarget).closest('.lh-link-card');
            const linkId = $card.data('link-id');
            const item = this.tree.items.find(i => i.type === 'link' && i.link_id === linkId);

            if (!item) return;

            // Populate edit modal with link data
            $('#lh-edit-link-id').val(linkId);
            $('#lh-edit-link-title').val(item.title || '');
            $('#lh-edit-link-url').val(item.url || '');
            $('#lh-edit-link-style').val(item.display_style || 'bar');

            // Handle image
            if (item.image_id && item.image_url) {
                $('#lh-edit-link-image-id').val(item.image_id);
                $('#lh-edit-link-image-preview').html(`<img src="${item.image_url}" alt="">`).addClass('has-image');
                $('#lh-edit-link-image-remove').show();
            } else {
                $('#lh-edit-link-image-id').val('');
                $('#lh-edit-link-image-preview').html('').removeClass('has-image');
                $('#lh-edit-link-image-remove').hide();
            }

            this.openModal('edit-link');
        },

        /**
         * Save edited link
         */
        async saveEditedLink() {
            const linkId = parseInt($('#lh-edit-link-id').val());
            const title = $('#lh-edit-link-title').val().trim();
            const url = $('#lh-edit-link-url').val().trim();
            const displayStyle = $('#lh-edit-link-style').val();
            const imageId = $('#lh-edit-link-image-id').val();

            if (!title) {
                alert(lhTreeBuilder.strings.enterTitle);
                return;
            }

            if (!url) {
                alert(lhTreeBuilder.strings.enterUrl);
                return;
            }

            try {
                const updatedLink = await this.api(`/links/${linkId}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        title,
                        url,
                        display_style: displayStyle,
                        image_id: imageId ? parseInt(imageId) : null
                    })
                });

                // Update the item in tree.items
                const itemIndex = this.tree.items.findIndex(i => i.type === 'link' && i.link_id === linkId);
                if (itemIndex !== -1) {
                    this.tree.items[itemIndex] = {
                        ...this.tree.items[itemIndex],
                        title: updatedLink.title,
                        url: updatedLink.url,
                        display_style: updatedLink.display_style,
                        image_id: updatedLink.image_id,
                        image_url: updatedLink.image_url
                    };
                }

                // Update allLinks as well
                const allLinksIndex = this.allLinks.findIndex(l => l.link_id === linkId);
                if (allLinksIndex !== -1) {
                    this.allLinks[allLinksIndex] = {
                        ...this.allLinks[allLinksIndex],
                        ...updatedLink
                    };
                }

                // Re-render cards and refresh preview
                this.renderCards();
                this.refreshPreview();
                this.closeModals();

            } catch (error) {
                console.error('Failed to update link:', error);
                alert('Failed to update link: ' + error.message);
            }
        },

        /**
         * Handle edit heading click - opens edit modal
         */
        onEditHeading(e) {
            const $card = $(e.currentTarget).closest('.lh-link-card');
            const index = $card.data('index');
            const item = this.tree.items[index];

            if (!item || item.type !== 'heading') return;

            // Populate edit modal with heading data
            $('#lh-edit-heading-index').val(index);
            $('#lh-edit-heading-text').val(item.text || '');
            $('#lh-edit-heading-size').val(item.size || 'medium');

            this.openModal('edit-heading');
        },

        /**
         * Save edited heading
         */
        saveEditedHeading() {
            const index = parseInt($('#lh-edit-heading-index').val());
            const text = $('#lh-edit-heading-text').val().trim();
            const size = $('#lh-edit-heading-size').val();

            if (!text) {
                alert(lhTreeBuilder.strings.enterHeading);
                return;
            }

            // Update the item in tree.items
            if (this.tree.items[index] && this.tree.items[index].type === 'heading') {
                this.tree.items[index].text = text;
                this.tree.items[index].size = size;

                // Re-render cards, save, and refresh preview
                this.renderCards();
                this.saveLinksOrder();
                this.closeModals();
            }
        },

        /**
         * Handle remove item click
         */
        onRemoveItem(e) {
            if (!confirm(lhTreeBuilder.strings.confirmDelete)) {
                return;
            }

            const $card = $(e.currentTarget).closest('.lh-link-card');
            $card.remove();
            this.saveLinksOrder();
        },

        /**
         * Open a modal
         */
        openModal(name) {
            $(`#lh-${name}-modal`).addClass('open');
        },

        /**
         * Close all modals
         */
        closeModals() {
            $('.lh-modal').removeClass('open');
            // Clear modal inputs
            $('.lh-modal input[type="text"], .lh-modal input[type="url"], .lh-modal textarea').val('');
            $('.lh-modal select').prop('selectedIndex', 0);
            $('#lh-new-link-image-id').val('');
            $('#lh-new-link-image-preview').html('').removeClass('has-image');
        },

        /**
         * Open existing link modal with populated options
         */
        openExistingLinkModal() {
            const $select = $('#lh-existing-link-select');
            const currentLinkIds = this.tree.items
                .filter(i => i.type === 'link')
                .map(i => i.link_id);

            const availableLinks = this.allLinks.filter(l => !currentLinkIds.includes(l.link_id));

            if (!availableLinks.length) {
                alert('All links are already in this tree.');
                return;
            }

            const options = availableLinks.map(l =>
                `<option value="${l.link_id}">${this.escapeHtml(l.title)}</option>`
            ).join('');

            $select.html(`<option value="">-- Select a link --</option>${options}`);
            this.openModal('add-existing');
        },

        /**
         * Create a new link
         */
        async createLink() {
            const title = $('#lh-new-link-title').val().trim();
            const url = $('#lh-new-link-url').val().trim();
            const displayStyle = $('#lh-new-link-style').val();
            const imageId = $('#lh-new-link-image-id').val();

            if (!title) {
                alert(lhTreeBuilder.strings.enterTitle);
                return;
            }

            if (!url) {
                alert(lhTreeBuilder.strings.enterUrl);
                return;
            }

            try {
                const link = await this.api('/links', {
                    method: 'POST',
                    body: JSON.stringify({
                        title,
                        url,
                        display_style: displayStyle,
                        image_id: imageId ? parseInt(imageId) : null
                    })
                });

                // Add to tree
                this.tree.items.push(link);
                this.allLinks.push(link);

                // Re-render and save
                this.renderCards();
                this.saveLinksOrder();

                this.closeModals();
            } catch (error) {
                console.error('Failed to create link:', error);
                alert('Failed to create link: ' + error.message);
            }
        },

        /**
         * Add a heading
         */
        addHeading() {
            const text = $('#lh-new-heading-text').val().trim();
            const size = $('#lh-new-heading-size').val();

            if (!text) {
                alert(lhTreeBuilder.strings.enterHeading);
                return;
            }

            this.tree.items.push({
                type: 'heading',
                text,
                size
            });

            this.renderCards();
            this.saveLinksOrder();
            this.closeModals();
        },

        /**
         * Add existing link to tree
         */
        addExistingLink() {
            const linkId = parseInt($('#lh-existing-link-select').val());
            if (!linkId) return;

            const link = this.allLinks.find(l => l.link_id === linkId);
            if (!link) return;

            this.tree.items.push(link);
            this.renderCards();
            this.saveLinksOrder();
            this.closeModals();
        },

        /**
         * Create a new tree
         */
        async createTree() {
            const title = $('#lh-new-tree-title').val().trim();

            if (!title) {
                alert('Please enter a tree name.');
                return;
            }

            try {
                const tree = await this.api('/trees', {
                    method: 'POST',
                    body: JSON.stringify({ title })
                });

                this.closeModals();
                this.loadTreeList();
                this.loadTree(tree.id);
            } catch (error) {
                console.error('Failed to create tree:', error);
                alert('Failed to create tree: ' + error.message);
            }
        },

        /**
         * Escape HTML
         */
        escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * Escape attribute value
         */
        escapeAttr(str) {
            if (!str) return '';
            return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
    };

    // Initialize when DOM ready
    $(document).ready(() => TreeBuilder.init());

})(jQuery);
