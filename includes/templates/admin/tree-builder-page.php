<?php
/**
 * Tree Builder Admin Page Template
 *
 * @package LinkHub
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap lh-tree-builder-wrap">
    <div class="lh-tree-builder" id="lh-tree-builder" data-tree-id="<?php echo esc_attr($tree_id); ?>">

        <!-- Left Sidebar: Settings Navigation -->
        <aside class="lh-builder-sidebar">
            <div class="lh-sidebar-section lh-tree-settings-nav" id="lh-tree-settings-nav">
                <h3><?php esc_html_e('Tree Settings', 'linkhub'); ?></h3>
                <ul class="lh-settings-nav-list">
                    <li class="active">
                        <button type="button" data-view="profile">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php esc_html_e('Profile', 'linkhub'); ?>
                        </button>
                    </li>
                    <li>
                        <button type="button" data-view="social">
                            <span class="dashicons dashicons-share"></span>
                            <?php esc_html_e('Social Links', 'linkhub'); ?>
                        </button>
                    </li>
                    <li>
                        <button type="button" data-view="links">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php esc_html_e('Links', 'linkhub'); ?>
                        </button>
                    </li>
                    <li>
                        <button type="button" data-view="appearance">
                            <span class="dashicons dashicons-art"></span>
                            <?php esc_html_e('Appearance', 'linkhub'); ?>
                        </button>
                    </li>
                    <li>
                        <button type="button" data-view="analytics">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php esc_html_e('Analytics', 'linkhub'); ?>
                        </button>
                    </li>
                    <li>
                        <button type="button" data-view="import-export">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Import / Export', 'linkhub'); ?>
                        </button>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Content: Views -->
        <main class="lh-builder-main">
            <div class="lh-builder-empty" id="lh-builder-empty">
                <div class="lh-empty-state">
                    <span class="dashicons dashicons-networking"></span>
                    <h2><?php esc_html_e('Loading...', 'linkhub'); ?></h2>
                    <p><?php esc_html_e('Setting up your link tree.', 'linkhub'); ?></p>
                </div>
            </div>

            <div class="lh-builder-content" id="lh-builder-content" style="display: none;">
                <!-- Header (shown on all views) -->
                <header class="lh-builder-header">
                    <div class="lh-header-title">
                        <h1 id="lh-tree-title"></h1>
                        <span class="lh-status-badge" id="lh-tree-status"></span>
                    </div>
                    <div class="lh-header-actions">
                        <button type="button" id="lh-save-btn" class="lh-btn lh-btn-primary" disabled>
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Changes', 'linkhub'); ?>
                        </button>
                        <button type="button" id="lh-publish-btn" class="lh-btn lh-btn-primary">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Publish', 'linkhub'); ?>
                        </button>
                        <a href="#" id="lh-view-tree-btn" class="lh-btn lh-btn-secondary" target="_blank">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e('View', 'linkhub'); ?>
                        </a>
                    </div>
                </header>

                <!-- Links View (default) -->
                <div class="lh-view lh-view-links" id="lh-view-links">
                    <div class="lh-view-header">
                        <h2><?php esc_html_e('Links', 'linkhub'); ?></h2>
                    </div>
                    <div class="lh-add-buttons">
                        <button type="button" class="lh-btn lh-btn-primary" id="lh-add-link-btn">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php esc_html_e('Add Link', 'linkhub'); ?>
                        </button>
                        <button type="button" class="lh-btn lh-btn-secondary" id="lh-add-heading-btn">
                            <span class="dashicons dashicons-editor-textcolor"></span>
                            <?php esc_html_e('Add Heading', 'linkhub'); ?>
                        </button>
                        <button type="button" class="lh-btn lh-btn-secondary" id="lh-add-existing-btn">
                            <span class="dashicons dashicons-plus"></span>
                            <?php esc_html_e('Add Existing Link', 'linkhub'); ?>
                        </button>
                    </div>
                    <div class="lh-links-container" id="lh-links-container">
                        <!-- Link cards will be rendered here via JS -->
                    </div>
                </div>

                <!-- Profile View -->
                <div class="lh-view lh-view-profile" id="lh-view-profile" style="display: none;">
                    <div class="lh-view-header">
                        <h2><?php esc_html_e('Profile', 'linkhub'); ?></h2>
                        <p class="lh-view-description"><?php esc_html_e('Customize your profile header that appears at the top of your link tree.', 'linkhub'); ?></p>
                    </div>
                    <div class="lh-settings-form">
                        <div class="lh-form-group">
                            <label for="lh-profile-title"><?php esc_html_e('Display Name / Title', 'linkhub'); ?></label>
                            <input type="text" id="lh-profile-title" placeholder="<?php esc_attr_e('e.g. My Link Tree', 'linkhub'); ?>">
                        </div>

                        <div class="lh-form-group">
                            <label><?php esc_html_e('Profile Image', 'linkhub'); ?></label>
                            <div class="lh-image-upload" id="lh-header-image-upload">
                                <div class="lh-image-preview" id="lh-header-image-preview"></div>
                                <button type="button" class="lh-btn lh-btn-small" id="lh-header-image-btn">
                                    <?php esc_html_e('Select Image', 'linkhub'); ?>
                                </button>
                                <button type="button" class="lh-btn lh-btn-small lh-btn-danger" id="lh-header-image-remove" style="display:none;">
                                    <?php esc_html_e('Remove', 'linkhub'); ?>
                                </button>
                                <input type="hidden" id="lh-header-image-id" value="">
                            </div>
                        </div>
                        <div class="lh-form-group">
                            <label for="lh-about-text"><?php esc_html_e('Bio / About Text', 'linkhub'); ?></label>
                            <textarea id="lh-about-text" rows="3" placeholder="<?php esc_attr_e('Tell visitors about yourself...', 'linkhub'); ?>"></textarea>
                        </div>
                        <div class="lh-form-group">
                            <label for="lh-tree-slug"><?php esc_html_e('Page URL (slug)', 'linkhub'); ?></label>
                            <input type="text" id="lh-tree-slug" placeholder="<?php esc_attr_e('my-links', 'linkhub'); ?>">
                            <p class="lh-form-description"><?php esc_html_e('This will be the URL path: /links/your-slug', 'linkhub'); ?></p>
                        </div>
                        <div class="lh-form-group">
                            <label><?php esc_html_e('Hero Image Shape', 'linkhub'); ?></label>
                            <select id="lh-hero-shape">
                                <option value="round"><?php esc_html_e('Circle', 'linkhub'); ?></option>
                                <option value="rounded"><?php esc_html_e('Rounded', 'linkhub'); ?></option>
                                <option value="square"><?php esc_html_e('Square', 'linkhub'); ?></option>
                            </select>
                        </div>
                        <div class="lh-form-group lh-form-group-inline">
                            <label>
                                <input type="checkbox" id="lh-hero-fade">
                                <?php esc_html_e('Enable fade effect on hero image', 'linkhub'); ?>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Social Links View -->
                <div class="lh-view lh-view-social" id="lh-view-social" style="display: none;">
                    <div class="lh-view-header">
                        <h2><?php esc_html_e('Social Links', 'linkhub'); ?></h2>
                        <p class="lh-view-description"><?php esc_html_e('Add social media links that appear as icons on your tree.', 'linkhub'); ?></p>
                    </div>
                    <div class="lh-settings-form">
                        <div class="lh-social-links-list" id="lh-social-links-list">
                            <!-- Social links will be rendered here -->
                        </div>
                        <button type="button" class="lh-btn lh-btn-secondary" id="lh-add-social-btn">
                            <span class="dashicons dashicons-plus"></span>
                            <?php esc_html_e('Add Social Link', 'linkhub'); ?>
                        </button>
                        <div class="lh-form-group" style="margin-top: 24px;">
                            <label><?php esc_html_e('Icon Style', 'linkhub'); ?></label>
                            <select id="lh-social-style">
                                <option value="circle"><?php esc_html_e('Circle', 'linkhub'); ?></option>
                                <option value="rounded"><?php esc_html_e('Rounded', 'linkhub'); ?></option>
                                <option value="square"><?php esc_html_e('Square', 'linkhub'); ?></option>
                                <option value="minimal"><?php esc_html_e('Minimal', 'linkhub'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Appearance View -->
                <div class="lh-view lh-view-appearance" id="lh-view-appearance" style="display: none;">
                    <div class="lh-view-header">
                        <h2><?php esc_html_e('Appearance', 'linkhub'); ?></h2>
                        <p class="lh-view-description"><?php esc_html_e('Customize colors and fonts for your link tree.', 'linkhub'); ?></p>
                    </div>
                    <div class="lh-settings-form">
                        <h3 class="lh-settings-section-title"><?php esc_html_e('Colors', 'linkhub'); ?></h3>
                        <div class="lh-form-row">
                            <div class="lh-form-group">
                                <label for="lh-background-color"><?php esc_html_e('Page Background', 'linkhub'); ?></label>
                                <input type="text" id="lh-background-color" class="lh-color-picker" value="#8b8178">
                            </div>
                            <div class="lh-form-group">
                                <label for="lh-tree-background-color"><?php esc_html_e('Content Background', 'linkhub'); ?></label>
                                <input type="text" id="lh-tree-background-color" class="lh-color-picker" value="#f5f5f5">
                            </div>
                        </div>
                        <div class="lh-form-row">
                            <div class="lh-form-group">
                                <label for="lh-title-color"><?php esc_html_e('Title Color', 'linkhub'); ?></label>
                                <input type="text" id="lh-title-color" class="lh-color-picker" value="#1a1a1a">
                            </div>
                            <div class="lh-form-group">
                                <label for="lh-bio-color"><?php esc_html_e('Bio Text Color', 'linkhub'); ?></label>
                                <input type="text" id="lh-bio-color" class="lh-color-picker" value="#555555">
                            </div>
                        </div>
                        <div class="lh-form-row">
                            <div class="lh-form-group">
                                <label for="lh-link-background-color"><?php esc_html_e('Link Button Background', 'linkhub'); ?></label>
                                <input type="text" id="lh-link-background-color" class="lh-color-picker" value="#eeeeee">
                            </div>
                            <div class="lh-form-group">
                                <label for="lh-link-text-color"><?php esc_html_e('Link Button Text', 'linkhub'); ?></label>
                                <input type="text" id="lh-link-text-color" class="lh-color-picker" value="#000000">
                            </div>
                        </div>
                        <div class="lh-form-group">
                            <label for="lh-social-color"><?php esc_html_e('Social Icons Color', 'linkhub'); ?></label>
                            <input type="text" id="lh-social-color" class="lh-color-picker" value="#333333">
                        </div>

                        <h3 class="lh-settings-section-title" style="margin-top: 32px;"><?php esc_html_e('Typography', 'linkhub'); ?></h3>
                        <div class="lh-form-row">
                            <div class="lh-form-group">
                                <label for="lh-title-font"><?php esc_html_e('Title Font', 'linkhub'); ?></label>
                                <select id="lh-title-font">
                                    <!-- Options populated via JS -->
                                </select>
                            </div>
                            <div class="lh-form-group">
                                <label for="lh-body-font"><?php esc_html_e('Body Font', 'linkhub'); ?></label>
                                <select id="lh-body-font">
                                    <!-- Options populated via JS -->
                                </select>
                            </div>
                        </div>
                        <div class="lh-form-group">
                            <label for="lh-heading-size"><?php esc_html_e('Heading/Divider Size', 'linkhub'); ?></label>
                            <select id="lh-heading-size">
                                <option value="small"><?php esc_html_e('Small', 'linkhub'); ?></option>
                                <option value="medium"><?php esc_html_e('Medium', 'linkhub'); ?></option>
                                <option value="large"><?php esc_html_e('Large', 'linkhub'); ?></option>
                            </select>
                        </div>

                        <h3 class="lh-settings-section-title" style="margin-top: 32px;"><?php esc_html_e('Background', 'linkhub'); ?></h3>
                        <div class="lh-form-group">
                            <label><?php esc_html_e('Background Image', 'linkhub'); ?></label>
                            <div class="lh-image-upload" id="lh-bg-image-upload">
                                <div class="lh-image-preview" id="lh-bg-image-preview"></div>
                                <button type="button" class="lh-btn lh-btn-small" id="lh-bg-image-btn">
                                    <?php esc_html_e('Select Image', 'linkhub'); ?>
                                </button>
                                <button type="button" class="lh-btn lh-btn-small lh-btn-danger" id="lh-bg-image-remove" style="display:none;">
                                    <?php esc_html_e('Remove', 'linkhub'); ?>
                                </button>
                                <input type="hidden" id="lh-bg-image-id" value="">
                            </div>
                        </div>

                        <h3 class="lh-settings-section-title" style="margin-top: 32px;"><?php esc_html_e('Display', 'linkhub'); ?></h3>
                        <div class="lh-form-group lh-form-group-inline">
                            <label>
                                <input type="checkbox" id="lh-hide-header-footer">
                                <?php esc_html_e('Hide site header and footer (clean display)', 'linkhub'); ?>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Import/Export View -->
                <div class="lh-view lh-view-import-export" id="lh-view-import-export" style="display: none;">
                    <div class="lh-view-header">
                        <h2><?php esc_html_e('Import / Export', 'linkhub'); ?></h2>
                        <p class="lh-view-description"><?php esc_html_e('Export your tree data or import from another source.', 'linkhub'); ?></p>
                    </div>
                    <div class="lh-settings-form">
                        <h3 class="lh-settings-section-title"><?php esc_html_e('Export', 'linkhub'); ?></h3>
                        <p class="lh-form-description"><?php esc_html_e('Download your tree settings and links as a JSON file.', 'linkhub'); ?></p>
                        <button type="button" class="lh-btn lh-btn-secondary" id="lh-export-btn">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Export Tree', 'linkhub'); ?>
                        </button>

                        <h3 class="lh-settings-section-title" style="margin-top: 32px;"><?php esc_html_e('Import', 'linkhub'); ?></h3>
                        <p class="lh-form-description"><?php esc_html_e('Import tree data from a JSON file or migrate from Clickwhale.', 'linkhub'); ?></p>
                        <div class="lh-form-group">
                            <label for="lh-import-file"><?php esc_html_e('Select File', 'linkhub'); ?></label>
                            <input type="file" id="lh-import-file" accept=".json">
                        </div>
                        <button type="button" class="lh-btn lh-btn-secondary" id="lh-import-btn" disabled>
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Import', 'linkhub'); ?>
                        </button>

                        <h3 class="lh-settings-section-title" style="margin-top: 32px;"><?php esc_html_e('Clickwhale Migration', 'linkhub'); ?></h3>
                        <p class="lh-form-description"><?php esc_html_e('Import links from a Clickwhale CSV export file.', 'linkhub'); ?></p>
                        <div class="lh-form-group">
                            <label for="lh-import-clickwhale-file"><?php esc_html_e('Clickwhale CSV File', 'linkhub'); ?></label>
                            <input type="file" id="lh-import-clickwhale-file" accept=".csv">
                        </div>
                        <button type="button" class="lh-btn lh-btn-secondary" id="lh-import-clickwhale-btn" disabled>
                            <span class="dashicons dashicons-migrate"></span>
                            <?php esc_html_e('Import from CSV', 'linkhub'); ?>
                        </button>
                        <p class="lh-form-description" style="margin-top: 12px;">
                            <?php esc_html_e('Expected columns: title/name, url/destination, clicks (optional), icon (optional)', 'linkhub'); ?>
                        </p>

                        <h3 class="lh-settings-section-title lh-danger-zone-title" style="margin-top: 48px;">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e('Danger Zone', 'linkhub'); ?>
                        </h3>
                        <div class="lh-danger-zone">
                            <p class="lh-form-description">
                                <?php esc_html_e('Permanently delete all links and reset your LinkHub to start fresh. This action cannot be undone.', 'linkhub'); ?>
                            </p>
                            <div class="lh-form-group">
                                <label for="lh-reset-confirm"><?php esc_html_e('Type DELETE to confirm', 'linkhub'); ?></label>
                                <input type="text" id="lh-reset-confirm" placeholder="DELETE" autocomplete="off">
                            </div>
                            <button type="button" class="lh-btn lh-btn-danger" id="lh-reset-all-btn" disabled>
                                <span class="dashicons dashicons-trash"></span>
                                <?php esc_html_e('Delete All Data', 'linkhub'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Analytics View -->
                <div class="lh-view lh-view-analytics" id="lh-view-analytics" style="display: none;">
                    <div class="lh-view-header">
                        <h2><?php esc_html_e('Analytics', 'linkhub'); ?></h2>
                        <p class="lh-view-description"><?php esc_html_e('Track your link tree performance.', 'linkhub'); ?></p>
                    </div>
                    <div class="lh-analytics-dashboard">
                         <div class="lh-analytics-overview-cards" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                             <div class="lh-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; text-align: center;">
                                 <h3 style="margin-top: 0; color: #666; font-size: 14px; text-transform: uppercase;"><?php esc_html_e('Total Clicks', 'linkhub'); ?></h3>
                                 <div class="lh-stat-number" id="lh-stat-total" style="font-size: 36px; font-weight: bold; color: #333;">0</div>
                             </div>
                             <div class="lh-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; text-align: center;">
                                 <h3 style="margin-top: 0; color: #666; font-size: 14px; text-transform: uppercase;"><?php esc_html_e('Last 30 Days', 'linkhub'); ?></h3>
                                 <div class="lh-stat-number" id="lh-stat-month" style="font-size: 36px; font-weight: bold; color: #333;">0</div>
                             </div>
                         </div>
                         
                         <div class="lh-analytics-chart-container" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px;">
                            <div class="lh-analytics-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3 style="margin: 0;"><?php esc_html_e('Performance Over Time', 'linkhub'); ?></h3>
                                <div class="lh-analytics-controls" style="display: flex; gap: 10px; align-items: center;">
                                    <span id="lh-analytics-filter-label" style="font-size: 13px; color: #666; font-weight: 500;">
                                        <?php esc_html_e('All Links', 'linkhub'); ?>
                                    </span>
                                    <button type="button" class="lh-btn lh-btn-secondary" id="lh-analytics-compare-btn">
                                        <span class="dashicons dashicons-filter"></span>
                                        <?php esc_html_e('Compare Links', 'linkhub'); ?>
                                    </button>
                                    <button type="button" class="lh-btn lh-btn-small" id="lh-refresh-analytics" title="<?php esc_attr_e('Refresh Data', 'linkhub'); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                    </button>
                                </div>
                            </div>
                            <div style="height: 300px;">
                                <canvas id="lh-analytics-chart"></canvas>
                            </div>
                         </div>
                         
                         <div class="lh-analytics-top-links">
                             <h3><?php esc_html_e('Top Performing Links', 'linkhub'); ?></h3>
                             <table class="widefat striped" id="lh-top-links-table">
                                 <thead>
                                     <tr>
                                         <th><?php esc_html_e('Link', 'linkhub'); ?></th>
                                         <th><?php esc_html_e('Clicks', 'linkhub'); ?></th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                 </tbody>
                             </table>
                         </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Right Panel: Live Preview -->
        <aside class="lh-builder-preview">
            <div class="lh-preview-header">
                <h3><?php esc_html_e('Preview', 'linkhub'); ?></h3>
                <button type="button" class="lh-btn lh-btn-small" id="lh-refresh-preview" title="<?php esc_attr_e('Refresh Preview', 'linkhub'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
            <div class="lh-preview-device">
                <div class="lh-device-frame">
                    <iframe id="lh-preview-frame" src="about:blank"></iframe>
                </div>
            </div>
        </aside>

    </div>
</div>

<!-- Modals -->

<!-- Create Link Modal -->
<div class="lh-modal" id="lh-create-link-modal">
    <div class="lh-modal-overlay"></div>
    <div class="lh-modal-content">
        <div class="lh-modal-header">
            <h3><?php esc_html_e('Create New Link', 'linkhub'); ?></h3>
            <button type="button" class="lh-modal-close">&times;</button>
        </div>
        <div class="lh-modal-body">
            <div class="lh-form-group">
                <label for="lh-new-link-title"><?php esc_html_e('Title', 'linkhub'); ?></label>
                <input type="text" id="lh-new-link-title" placeholder="<?php esc_attr_e('My Website', 'linkhub'); ?>">
            </div>
            <div class="lh-form-group">
                <label for="lh-new-link-url"><?php esc_html_e('URL', 'linkhub'); ?></label>
                <input type="url" id="lh-new-link-url" placeholder="https://example.com">
            </div>
            <div class="lh-form-group">
                <label for="lh-new-link-style"><?php esc_html_e('Display Style', 'linkhub'); ?></label>
                <select id="lh-new-link-style">
                    <option value="bar"><?php esc_html_e('Bar (Button)', 'linkhub'); ?></option>
                    <option value="card"><?php esc_html_e('Card (With Image)', 'linkhub'); ?></option>
                </select>
            </div>
            <div class="lh-form-group">
                <label><?php esc_html_e('Image (Optional)', 'linkhub'); ?></label>
                <div class="lh-image-upload" id="lh-new-link-image-upload">
                    <div class="lh-image-preview" id="lh-new-link-image-preview"></div>
                    <button type="button" class="lh-btn lh-btn-small" id="lh-new-link-image-btn">
                        <?php esc_html_e('Select Image', 'linkhub'); ?>
                    </button>
                    <input type="hidden" id="lh-new-link-image-id" value="">
                </div>
            </div>
        </div>
        <div class="lh-modal-footer">
            <button type="button" class="lh-btn lh-btn-secondary lh-modal-cancel"><?php esc_html_e('Cancel', 'linkhub'); ?></button>
            <button type="button" class="lh-btn lh-btn-primary" id="lh-create-link-submit"><?php esc_html_e('Create Link', 'linkhub'); ?></button>
        </div>
    </div>
</div>

<!-- Edit Link Modal -->
<div class="lh-modal" id="lh-edit-link-modal">
    <div class="lh-modal-overlay"></div>
    <div class="lh-modal-content">
        <div class="lh-modal-header">
            <h3><?php esc_html_e('Edit Link', 'linkhub'); ?></h3>
            <button type="button" class="lh-modal-close">&times;</button>
        </div>
        <div class="lh-modal-body">
            <input type="hidden" id="lh-edit-link-id" value="">
            <div class="lh-form-group">
                <label for="lh-edit-link-title"><?php esc_html_e('Title', 'linkhub'); ?></label>
                <input type="text" id="lh-edit-link-title" placeholder="<?php esc_attr_e('My Website', 'linkhub'); ?>">
            </div>
            <div class="lh-form-group">
                <label for="lh-edit-link-url"><?php esc_html_e('URL', 'linkhub'); ?></label>
                <input type="url" id="lh-edit-link-url" placeholder="https://example.com">
            </div>
            <div class="lh-form-group">
                <label for="lh-edit-link-style"><?php esc_html_e('Display Style', 'linkhub'); ?></label>
                <select id="lh-edit-link-style">
                    <option value="bar"><?php esc_html_e('Bar (Button)', 'linkhub'); ?></option>
                    <option value="card"><?php esc_html_e('Card (With Image)', 'linkhub'); ?></option>
                </select>
            </div>
            <div class="lh-form-group">
                <label><?php esc_html_e('Image (Optional)', 'linkhub'); ?></label>
                <div class="lh-image-upload" id="lh-edit-link-image-upload">
                    <div class="lh-image-preview" id="lh-edit-link-image-preview"></div>
                    <button type="button" class="lh-btn lh-btn-small" id="lh-edit-link-image-btn">
                        <?php esc_html_e('Select Image', 'linkhub'); ?>
                    </button>
                    <button type="button" class="lh-btn lh-btn-small lh-btn-danger" id="lh-edit-link-image-remove" style="display:none;">
                        <?php esc_html_e('Remove', 'linkhub'); ?>
                    </button>
                    <input type="hidden" id="lh-edit-link-image-id" value="">
                </div>
            </div>
        </div>
        <div class="lh-modal-footer">
            <button type="button" class="lh-btn lh-btn-secondary lh-modal-cancel"><?php esc_html_e('Cancel', 'linkhub'); ?></button>
            <button type="button" class="lh-btn lh-btn-primary" id="lh-edit-link-submit"><?php esc_html_e('Save Changes', 'linkhub'); ?></button>
        </div>
    </div>
</div>

<!-- Edit Heading Modal -->
<div class="lh-modal" id="lh-edit-heading-modal">
    <div class="lh-modal-overlay"></div>
    <div class="lh-modal-content">
        <div class="lh-modal-header">
            <h3><?php esc_html_e('Edit Heading', 'linkhub'); ?></h3>
            <button type="button" class="lh-modal-close">&times;</button>
        </div>
        <div class="lh-modal-body">
            <input type="hidden" id="lh-edit-heading-index" value="">
            <div class="lh-form-group">
                <label for="lh-edit-heading-text"><?php esc_html_e('Heading Text', 'linkhub'); ?></label>
                <input type="text" id="lh-edit-heading-text" placeholder="<?php esc_attr_e('Section Title', 'linkhub'); ?>">
            </div>
            <div class="lh-form-group">
                <label for="lh-edit-heading-size"><?php esc_html_e('Size', 'linkhub'); ?></label>
                <select id="lh-edit-heading-size">
                    <option value="small"><?php esc_html_e('Small', 'linkhub'); ?></option>
                    <option value="medium"><?php esc_html_e('Medium', 'linkhub'); ?></option>
                    <option value="large"><?php esc_html_e('Large', 'linkhub'); ?></option>
                </select>
            </div>
        </div>
        <div class="lh-modal-footer">
            <button type="button" class="lh-btn lh-btn-secondary lh-modal-cancel"><?php esc_html_e('Cancel', 'linkhub'); ?></button>
            <button type="button" class="lh-btn lh-btn-primary" id="lh-edit-heading-submit"><?php esc_html_e('Save Changes', 'linkhub'); ?></button>
        </div>
    </div>
</div>

<!-- Add Heading Modal -->
<div class="lh-modal" id="lh-add-heading-modal">
    <div class="lh-modal-overlay"></div>
    <div class="lh-modal-content">
        <div class="lh-modal-header">
            <h3><?php esc_html_e('Add Heading', 'linkhub'); ?></h3>
            <button type="button" class="lh-modal-close">&times;</button>
        </div>
        <div class="lh-modal-body">
            <div class="lh-form-group">
                <label for="lh-new-heading-text"><?php esc_html_e('Heading Text', 'linkhub'); ?></label>
                <input type="text" id="lh-new-heading-text" placeholder="<?php esc_attr_e('Section Title', 'linkhub'); ?>">
            </div>
            <div class="lh-form-group">
                <label for="lh-new-heading-size"><?php esc_html_e('Size', 'linkhub'); ?></label>
                <select id="lh-new-heading-size">
                    <option value="small"><?php esc_html_e('Small', 'linkhub'); ?></option>
                    <option value="medium" selected><?php esc_html_e('Medium', 'linkhub'); ?></option>
                    <option value="large"><?php esc_html_e('Large', 'linkhub'); ?></option>
                </select>
            </div>
        </div>
        <div class="lh-modal-footer">
            <button type="button" class="lh-btn lh-btn-secondary lh-modal-cancel"><?php esc_html_e('Cancel', 'linkhub'); ?></button>
            <button type="button" class="lh-btn lh-btn-primary" id="lh-add-heading-submit"><?php esc_html_e('Add Heading', 'linkhub'); ?></button>
        </div>
    </div>
</div>

<!-- Add Existing Link Modal -->
<div class="lh-modal" id="lh-add-existing-modal">
    <div class="lh-modal-overlay"></div>
    <div class="lh-modal-content">
        <div class="lh-modal-header">
            <h3><?php esc_html_e('Add Existing Link', 'linkhub'); ?></h3>
            <button type="button" class="lh-modal-close">&times;</button>
        </div>
        <div class="lh-modal-body">
            <div class="lh-form-group">
                <label for="lh-existing-link-select"><?php esc_html_e('Select Link', 'linkhub'); ?></label>
                <select id="lh-existing-link-select">
                    <option value=""><?php esc_html_e('-- Select a link --', 'linkhub'); ?></option>
                </select>
            </div>
        </div>
        <div class="lh-modal-footer">
            <button type="button" class="lh-btn lh-btn-secondary lh-modal-cancel"><?php esc_html_e('Cancel', 'linkhub'); ?></button>
            <button type="button" class="lh-btn lh-btn-primary" id="lh-add-existing-submit"><?php esc_html_e('Add to Tree', 'linkhub'); ?></button>
        </div>
    </div>
</div>

<!-- Analytics Link Selector Modal -->
<div id="lh-analytics-modal" class="lh-modal-overlay" style="display: none; z-index: 99999;">
    <div class="lh-modal-content lh-modal-large" style="width: 800px; max-width: 90%; max-height: 80vh; display: flex; flex-direction: column;">
        <div class="lh-modal-header">
            <h3><?php esc_html_e('Select Links to Compare', 'linkhub'); ?></h3>
            <button type="button" class="lh-modal-close" id="lh-analytics-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="lh-modal-body" style="flex: 1; display: flex; flex-direction: column; overflow: hidden; padding: 0;">
            <div class="lh-modal-toolbar" style="padding: 15px 20px; border-bottom: 1px solid #eee; background: #fff; display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                <input type="text" id="lh-modal-search" placeholder="<?php esc_attr_e('Search links...', 'linkhub'); ?>" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
                <div style="flex-grow: 1;"></div>
                <button type="button" class="lh-btn lh-btn-small" id="lh-modal-select-none"><?php esc_html_e('Clear Selection', 'linkhub'); ?></button>
                <span class="lh-modal-count" id="lh-modal-selected-count" style="min-width: 100px; text-align: right; color: #666; font-weight: 600;">0/10 Selected</span>
            </div>
            <div class="lh-modal-scroll-area" style="flex: 1; overflow-y: auto; padding: 20px;">
                <div id="lh-analytics-link-grid" class="lh-link-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px;">
                    <!-- Populated via JS -->
                </div>
            </div>
        </div>
        <div class="lh-modal-footer">
            <button type="button" class="lh-btn lh-btn-secondary" id="lh-analytics-modal-cancel"><?php esc_html_e('Cancel', 'linkhub'); ?></button>
            <button type="button" class="lh-btn lh-btn-primary" id="lh-analytics-modal-apply"><?php esc_html_e('Apply Comparison', 'linkhub'); ?></button>
        </div>
    </div>
</div>

