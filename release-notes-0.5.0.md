# LinkHub v0.5.0: The Analytics Update

We are excited to announce LinkHub 0.5.0, a major release that brings powerful insights to your link trees. This update introduces a full-featured Analytics Dashboard, allowing you to track performance, visualize trends, and understand what your visitors are engaging with.

## 🚀 Key Features

### 📊 Analytics Dashboard
Stop guessing what works. LinkHub now tracks clicks on every link in your tree.
- **Visual Charts**: See your click performance over time (Last 7 Days, 30 Days, or custom ranges).
- **Top Links**: A leaderboard showing your most popular content.
- **Deep Comparison**: Select specific links (even those nested deep inside Collections) and compare their performance side-by-side on the chart.

### 📂 Collection Refinements
We've polished the Collections system introduced in 0.4.0 to make it smoother and more intuitive.
- **Direct "Add to Collection"**: You can now add a new link directly into a specific collection right from the modal.
- **Better Sorting**: Drag-and-drop actions are more reliable.
- **Smart Placement**: New links added to the top level now appear at the top of your list (right below the Featured section) so you don't lose track of them.

### 📦 Robust Import/Export
The Export engine has been completely rewritten.
- **Nested Support**: It now handles complex, deeply nested Collection structures perfectly.
- **Image Portability**: When importing a tree, LinkHub will now attempt to download and sideload images referenced in the JSON, making migrations seamless.

### 🏛️ Archive Support
Added support for a dedicated `/links` archive page, giving you a secondary root view for your trees.

## 🛠️ Fixes & Polish
- **"Saving..." Indicators**: Added visual feedback so you know exactly when your changes are safe.
- **Modal Fixes**: Resolved z-index issues where modals could be blocked by overlays.
- **Settings Persistence**: Fixed an issue where Featured Section titles weren't saving correctly.

---

*This release is compatible with WordPress 6.0+.*
