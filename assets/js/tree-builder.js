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
        currentView: 'profile',

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
            this.loadAllLinks();

            // Auto-load the tree (single tree mode)
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
                emptyState: $('#lh-builder-empty'),
                content: $('#lh-builder-content'),
                linksContainer: $('#lh-links-container'),
                previewFrame: $('#lh-preview-frame'),
                treeTitle: $('#lh-tree-title'),
                treeStatus: $('#lh-tree-status'),
                viewBtn: $('#lh-view-tree-btn'),
                classicEditorBtn: $('#lh-classic-editor-btn'),
                addLinkBtn: $('#lh-add-link-btn'),
                addHeadingBtn: $('#lh-add-heading-btn'),
                addExistingBtn: $('#lh-add-existing-btn'),
                refreshPreview: $('#lh-refresh-preview'),
                settingsNavList: $('.lh-settings-nav-list'),
                socialLinksList: $('#lh-social-links-list'),
                views: {
                    profile: $('#lh-view-profile'),
                    social: $('#lh-view-social'),
                    links: $('#lh-view-links'),
                    appearance: $('#lh-view-appearance'),
                    'import-export': $('#lh-view-import-export'),
                },
            };
        },

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Add buttons
            this.els.addLinkBtn.on('click', () => this.openModal('create-link'));
            this.els.addHeadingBtn.on('click', () => this.openModal('add-heading'));
            this.els.addExistingBtn.on('click', () => this.openExistingLinkModal());

            // Refresh preview
            this.els.refreshPreview.on('click', () => this.refreshPreview());

            // Import/Export buttons
            $('#lh-export-btn').on('click', () => this.exportTree());
            $('#lh-import-file').on('change', (e) => {
                $('#lh-import-btn').prop('disabled', !e.target.files.length);
            });
            $('#lh-import-btn').on('click', () => this.importTree());
            $('#lh-import-clickwhale-file').on('change', (e) => {
                $('#lh-import-clickwhale-btn').prop('disabled', !e.target.files.length);
            });
            $('#lh-import-clickwhale-btn').on('click', () => this.importFromClickwhale());

            // Reset/Delete all data
            $('#lh-reset-confirm').on('input', (e) => {
                const isValid = $(e.target).val().trim() === 'DELETE';
                $('#lh-reset-all-btn').prop('disabled', !isValid);
            });
            $('#lh-reset-all-btn').on('click', () => this.resetAllData());

            // Save button
            $('#lh-save-btn').on('click', () => this.saveSettings());

            // Publish button
            $('#lh-publish-btn').on('click', () => this.togglePublish());

            // Title editing (Profile View)
            $('#lh-profile-title').on('input', (e) => {
                const val = $(e.target).val();
                this.els.treeTitle.text(val || '(Untitled)');
                this.markDirty();
            });

            // Update iframe preview title in real-time if postMessage supported (or simple DOM access if same origin)
            $('#lh-profile-title').on('input', (e) => {
                 try {
                     const val = $(e.target).val();
                     const iframe = document.getElementById('lh-preview-frame');
                     if (iframe && iframe.contentWindow) {
                         // Attempt direct DOM manipulation (same origin)
                         const titleEl = iframe.contentWindow.document.querySelector('.lh-tree-title');
                         if (titleEl) {
                             titleEl.innerText = val;
                         }
                     }
                 } catch (err) {
                     // Cross-origin restriction or element not found
                 }
            });

            // Links container events (delegated)
            this.els.linksContainer
                .on('click', '.lh-edit-btn', (e) => this.onEditLink(e))
                .on('click', '.lh-toggle-collection-btn', (e) => {
                     const $card = $(e.currentTarget).closest('.lh-collection-card');
                     $card.find('.lh-collection-content').toggleClass('collapsed');
                     $card.find('.lh-toggle-collection-btn span').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-right-alt2');
                     // State updated via saveLinksOrder() eventually or findItemById
                })
                .on('click', '.lh-remove-collection-btn', (e) => {
                    if(!confirm('Delete collection and all links inside?')) return;
                    $(e.currentTarget).closest('.lh-collection-card').remove();
                    this.saveLinksOrder(); // Syncs DOM removal to tree.items
                })
                .on('change', '.lh-collection-title-input', (e) => {
                     this.saveLinksOrder(); // Syncs title change
                })
                .on('click', '.lh-collection-settings-btn', (e) => {
                    // Placeholder for future UI
                    alert('Collection styling coming in next update.');
                })
                .on('click', '.lh-edit-heading-btn', (e) => this.onEditHeading(e))
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
            });
            this.els.socialLinksList.on('change', 'select, input', () => {
                this.markDirty();
            });

            // Image uploads
            this.bindImageUploads();
        },

        /**
         * Initialize drag and drop (SortableJS)
         */
        initDragDrop() {
           // Cleanup old instances
            if (this.sortables) {
                this.sortables.forEach(s => s.destroy());
            }
            this.sortables = [];

            const options = {
                group: 'nested-tree',
                animation: 150,
                handle: '.lh-drag-handle',
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'lh-sortable-ghost',
                chosenClass: 'lh-sortable-chosen',
                onEnd: () => {
                    this.saveLinksOrder();
                }
            };

            // Init on root
            // Ensure global Sortable is available (enqueued via script)
            if (typeof Sortable === 'undefined') {
                console.error('SortableJS not loaded');
                return;
            }

            const root = new Sortable(this.els.linksContainer[0], options);
            this.sortables.push(root);

            // Init on all collection children containers
            this.els.linksContainer.find('.lh-collection-children').each((i, el) => {
                 this.sortables.push(new Sortable(el, options));
            });
        },

        /**
         * Bind settings field events
         */
        bindSettingsEvents() {
            // Text inputs
            $('#lh-about-text, #lh-tree-slug').on('change', () => this.markDirty());

            // Selects
            $('#lh-hero-shape, #lh-social-style, #lh-title-font, #lh-body-font, #lh-heading-size')
                .on('change', () => this.markDirty());

            // Checkboxes
            $('#lh-hero-fade, #lh-hide-header-footer').on('change', () => this.markDirty());
        },

        /**
         * Initialize color pickers
         */
        initColorPickers() {
            $('.lh-color-picker').wpColorPicker({
                change: () => {
                    this.markDirty();
                },
                clear: () => {
                    this.markDirty();
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
                this.markDirty();
            });

            $('#lh-header-image-remove').on('click', () => {
                $('#lh-header-image-id').val('');
                $('#lh-header-image-preview').html('').removeClass('has-image');
                $('#lh-header-image-remove').hide();
                this.markDirty();
            });

            // Background image
            this.createMediaUploader('bg-image', (id, url) => {
                $('#lh-bg-image-id').val(id);
                $('#lh-bg-image-preview').html(`<img src="${url}" alt="">`).addClass('has-image');
                $('#lh-bg-image-remove').show();
                this.markDirty();
            });

            $('#lh-bg-image-remove').on('click', () => {
                $('#lh-bg-image-id').val('');
                $('#lh-bg-image-preview').html('').removeClass('has-image');
                $('#lh-bg-image-remove').hide();
                this.markDirty();
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
                
                // Auto-migrate if flat structure detected
                if (this.tree.items && this.tree.items.some(i => i.type === 'heading')) {
                    console.log('Migrating flat headings to collections...');
                    this.tree.items = this.migrateLinksToCollections(this.tree.items);
                    this.markDirty();
                }

                this.renderTree();
                this.refreshPreview();
            } catch (error) {
                console.error('Failed to load tree:', error);
                alert('Failed to load tree: ' + error.message);
            }
        },

        /**
         * Migrate flat headings to nested collections
         */
        migrateLinksToCollections(flatItems) {
            const nested = [];
            let currentCollection = null;

            flatItems.forEach(item => {
                if (item.type === 'heading') {
                    // Start new collection
                    currentCollection = {
                        type: 'collection',
                        id: 'col_' + Date.now() + Math.floor(Math.random() * 1000),
                        title: item.text,
                        settings: {
                            borderEnabled: true,
                            borderColor: '#000000',
                            borderWidth: '2px',
                            headingSize: item.size || 'medium'
                        },
                        children: [],
                        isExpanded: true
                    };
                    nested.push(currentCollection);
                } else {
                    // It's a link
                    if (currentCollection) {
                        currentCollection.children.push(item);
                    } else {
                        // Orphan link (before any heading) -> Top level
                        nested.push(item);
                    }
                }
            });

            return nested;
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

            // Update header info
            this.els.treeTitle.text(this.tree.title || '(Untitled)');
            this.els.treeStatus.text(this.tree.status).attr('class', `lh-status-badge ${this.tree.status}`);
            this.els.viewBtn.attr('href', this.tree.permalink);
            this.els.classicEditorBtn.attr('href', this.tree.edit_url);
            $('#lh-tree-slug').val(this.tree.slug || '');

            // Update Add Heading button to "Add Collection"
            this.els.addHeadingBtn.html('<span class="dashicons dashicons-editor-textcolor"></span> Add Collection');

            // Render items
            if (!this.tree.items || !this.tree.items.length) {
                this.els.linksContainer.html('<p class="lh-empty-links">No links yet. Add your first link above.</p>');
            } else {
                 this.els.linksContainer.empty();
                 this.renderItems(this.tree.items, this.els.linksContainer);
                 this.initDragDrop();
            }

            // Populate settings
            this.populateSettings();

            // Refresh Publish Button state
            this.updatePublishButton();

            // Switch to profile view by default if no view selected
            // But if we are reloading (e.g. after save), keep current view
            if (!this.currentView) {
                this.switchView('links'); // Default to Links view as it is the main feature
            } else {
                this.switchView(this.currentView);
            }
            
            // Clear dirty state
            this.isDirty = false;
        },

        /**
         * Update Publish/Update button visibility/text
         */
        updatePublishButton() {
             const $publishBtn = $('#lh-publish-btn');
             // If tree is published, we might want to hide 'Publish' or change to 'Update'
             // Original logic was: hide if published (assuming auto-save or 'Save Changes' handles updates)
             if (this.tree && this.tree.status === 'publish') {
                 $publishBtn.hide();
             } else {
                 $publishBtn.show();
             }
        },

        /**
         * Switch between views (profile, social, links, appearance, import-export)
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
         * Render items recursively
         */
        renderItems(items, container) {
            items.forEach((item, index) => {
                if (item.type === 'collection') {
                    container.append(this.createCollectionHtml(item));
                    
                    // Render children
                    const childrenContainer = container.find(`[data-id="${item.id}"] .lh-collection-children`);
                    if (item.children && item.children.length) {
                        this.renderItems(item.children, childrenContainer);
                    }
                } else if (!item.type || item.type === 'link') {
                    container.append(this.createLinkHtml(item));
                }
            });
        },

        /**
         * Create HTML for a Link Card
         */
        createLinkHtml(item) {
            const thumbHtml = item.image_url
                ? `<img src="${this.escapeAttr(item.image_url)}" alt="">`
                : '<span class="dashicons dashicons-admin-links"></span>';

            return `
                <div class="lh-link-card" data-type="link" data-link-id="${item.link_id}">
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
         * Create HTML for a Collection
         */
        createCollectionHtml(item) {
             const settings = item.settings || {};
             const borderStyle = settings.borderEnabled ? `border: ${settings.borderWidth || '2px'} solid ${settings.borderColor || '#000'};` : '';
             const headerStyle = settings.headerBgColor ? `background-color: ${settings.headerBgColor};` : '';

             return `
                <div class="lh-collection-card" data-type="collection" data-id="${item.id}">
                    <div class="lh-collection-header" style="${borderStyle} ${headerStyle}">
                        <span class="lh-drag-handle dashicons dashicons-menu"></span>
                        <div class="lh-collection-info">
                            <input type="text" class="lh-collection-title-input" value="${this.escapeAttr(item.title)}" placeholder="Collection Title">
                            <span class="lh-collection-count">${(item.children || []).length} links</span>
                        </div>
                        <div class="lh-collection-actions">
                            <button type="button" class="lh-collection-settings-btn" title="Collection Settings">
                                <span class="dashicons dashicons-art"></span>
                            </button>
                            <button type="button" class="lh-remove-collection-btn" title="Remove Collection">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                            <button type="button" class="lh-toggle-collection-btn">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                    </div>
                    <div class="lh-collection-content ${item.isExpanded ? '' : 'collapsed'}">
                        <div class="lh-collection-children-wrapper">
                            <div class="lh-collection-children"></div>
                        </div>
                    </div>
                </div>
            `;
        },
    
        // Deprecated alias for compatibility if needed internally
        renderCards() { this.renderTree(); },

        /**
         * Populate settings panel with tree data
         */
        populateSettings() {
            const s = this.tree.settings;

            // Title
            $('#lh-profile-title').val(this.tree.title || '');

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
            $('#lh-save-btn').prop('disabled', false);
        },

        /**
         * Show saved status
         */
        showSaved() {
            this.isDirty = false;
            $('#lh-save-btn').prop('disabled', true);
        },

        /**
         * Show error status
         */
        showError() {
            // Error is shown via alert in the calling code
        },

        /**
         * Save tree settings
         */
        async saveSettings() {
            if (!this.treeId) return;

            const settings = this.collectSettings();
            const title = $('#lh-profile-title').val().trim();
            const slug = $('#lh-tree-slug').val().trim();

            try {
                // Update local model
                this.tree.title = title;
                this.els.treeTitle.text(title || '(Untitled)');

                await this.api(`/trees/${this.treeId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ settings, title, slug })
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
        /**
         * Save links order (Recursive)
         */
        async saveLinksOrder() {
            if (!this.treeId) return;

            this.markDirty();

            // Helper to recursively serialize DOM to JSON
            const serialize = (container) => {
                const items = [];
                container.children().each((i, el) => {
                    const $el = $(el);
                    const type = $el.data('type');

                    if (type === 'collection') {
                        const id = $el.data('id');
                        const original = this.findItemById(this.tree.items, id) || {};
                        
                        items.push({
                            type: 'collection',
                            id: id,
                            title: $el.find('.lh-collection-title-input').val(),
                            settings: original.settings || {},
                            children: serialize($el.find('.lh-collection-children').first()),
                            isExpanded: !$el.find('.lh-collection-content').hasClass('collapsed')
                        });
                    } else if (type === 'link') {
                         const linkId = parseInt($el.data('link-id'));
                         const original = this.findItemById(this.tree.items, linkId);
                         if (original) {
                             items.push(original);
                         } else {
                             // Fallback if not found (shouldn't happen)
                             items.push({ type: 'link', link_id: linkId });
                         }
                    }
                });
                return items;
            };

            const items = serialize(this.els.linksContainer);
            this.tree.items = items;

            try {
                await this.api(`/trees/${this.treeId}/links`, {
                    method: 'PUT',
                    body: JSON.stringify({ items })
                });
                this.showSaved();
                this.refreshPreview();
            } catch (error) {
                console.error('Failed to save links order:', error);
                this.showError();
            }
        },

        /**
         * Find item by ID in nested structure
         */
        findItemById(items, id) {
             if (!items) return null;
             for (const item of items) {
                 if (item.type === 'collection') {
                     if (item.id === id) return item;
                     const found = this.findItemById(item.children, id);
                     if (found) return found;
                 } else {
                     if (item.link_id === id) return item;
                 }
             }
             return null;
        },

        /**
         * Handle title change
         */
        onTitleChange() {
            const newTitle = this.els.treeTitle.text().trim();
            if (newTitle !== this.tree.title) {
                this.tree.title = newTitle;
                this.markDirty();
            }
        },

        /**
         * Publish the tree (no unpublish)
         */
        async togglePublish() {
            if (!this.treeId || this.tree.status === 'publish') return;

            const $publishBtn = $('#lh-publish-btn');

            try {
                await this.api(`/trees/${this.treeId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ status: 'publish' })
                });

                this.tree.status = 'publish';
                this.els.treeStatus.text('publish').attr('class', 'lh-status-badge publish');

                // Hide the publish button - once published, stays published
                $publishBtn.hide();

                this.showSaved();
            } catch (error) {
                console.error('Failed to publish:', error);
                alert('Failed to publish: ' + error.message);
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
         * Add a heading (converted to Collection)
         */
        addHeading() {
            const text = $('#lh-new-heading-text').val().trim();
            // Size ignored for collections for now
            
            if (!text) {
                alert(lhTreeBuilder.strings.enterHeading);
                return;
            }

            this.tree.items.push({
                type: 'collection',
                id: 'col_' + Date.now(),
                title: text,
                settings: { 
                    borderEnabled: true, 
                    borderColor: '#000000', 
                    borderWidth: '2px', 
                    headerBgColor: 'transparent'
                },
                children: [],
                isExpanded: true
            });

            this.renderTree(); // Renders fully
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
         * Export tree as JSON file
         */
        async exportTree() {
            if (!this.treeId) return;

            try {
                const data = await this.api(`/trees/${this.treeId}/export`);
                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `linkhub-tree-${this.tree.title || 'export'}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Export failed:', error);
                alert('Export failed: ' + error.message);
            }
        },

        /**
         * Import tree from JSON file
         */
        async importTree() {
            const fileInput = $('#lh-import-file')[0];
            if (!fileInput.files.length) return;

            const $btn = $('#lh-import-btn');
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Importing...');

            const file = fileInput.files[0];
            const reader = new FileReader();

            reader.onload = async (e) => {
                try {
                    const data = JSON.parse(e.target.result);
                    await this.api(`/trees/${this.treeId}/import`, {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    alert('Import successful! Reloading...');
                    
                    // Reset UI
                    fileInput.value = '';
                    $btn.text(originalText); // Remain disabled via change handler logic effectively, but let's be explicit
                    // The change handler enables it only when files > 0. Since we cleared it:
                    $btn.prop('disabled', true);

                    this.loadTree(this.treeId);
                } catch (error) {
                    console.error('Import failed:', error);
                    alert('Import failed: ' + error.message);
                    $btn.prop('disabled', false).text(originalText);
                }
            };
            
            reader.onerror = () => {
                alert('Failed to read file');
                $btn.prop('disabled', false).text(originalText);
            };

            reader.readAsText(file);
        },

        /**
         * Import from Clickwhale CSV
         */
        async importFromClickwhale() {
            const fileInput = $('#lh-import-clickwhale-file')[0];
            if (!fileInput.files.length) {
                alert('Please select a CSV file first.');
                return;
            }

            if (!confirm('This will import links from the Clickwhale CSV file. Continue?')) {
                return;
            }

            const $btn = $('#lh-import-clickwhale-btn');
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Processing...');

            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('csv_file', file);
            formData.append('tree_id', this.treeId);

            try {
                const response = await fetch(lhTreeBuilder.apiBase + '/import/clickwhale-csv', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': lhTreeBuilder.nonce,
                    },
                    body: formData
                });

                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    throw new Error(error.message || `Import failed: ${response.status}`);
                }

                const result = await response.json();
                let message = `Imported ${result.count} links from Clickwhale CSV!`;
                if (result.errors && result.errors.length) {
                    message += `\n\nWarnings:\n${result.errors.join('\n')}`;
                }
                alert(message);
                
                fileInput.value = '';
                $btn.text(originalText).prop('disabled', true); // Reset and disable since file is gone
                
                this.loadTree(this.treeId);
                this.loadAllLinks();
            } catch (error) {
                console.error('Clickwhale CSV import failed:', error);
                alert('Import failed: ' + error.message);
                $btn.text(originalText).prop('disabled', false);
            }
        },

        /**
         * Reset all data (delete all links and reset tree)
         */
        async resetAllData() {
            if (!confirm('⚠️ WARNING: This will permanently delete ALL links and reset your LinkHub.\n\nThis action CANNOT be undone.\n\nAre you absolutely sure?')) {
                return;
            }

            if (!confirm('FINAL WARNING: All your links, settings, and data will be lost forever.\n\nClick OK to proceed with deletion.')) {
                return;
            }

            try {
                await this.api(`/trees/${this.treeId}/reset`, {
                    method: 'DELETE'
                });
                
                alert('All data has been deleted. Your LinkHub has been reset.');
                $('#lh-reset-confirm').val('');
                $('#lh-reset-all-btn').prop('disabled', true);
                this.loadTree(this.treeId);
                this.loadAllLinks();
            } catch (error) {
                console.error('Reset failed:', error);
                alert('Reset failed: ' + error.message);
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
