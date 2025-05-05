# 📦 Formidable Archive Optimizer

A lightweight and powerful WordPress plugin to **archive old Formidable Forms entries** into separate tables for performance optimization — while still allowing access by Entry ID and restoring them as needed.

---

## ✨ Features

- 📁 Archives Formidable Forms entries to custom database tables.
- 🔄 Restore archived entries with one click.
- 🔍 Supports access to archived entries by ID.
- ⚡ Helps improve performance for large datasets.
- 🧩 Includes a shortcode with filters, actions, and pagination.

---

## 🧰 Shortcode

Use the following shortcode on any page or post:

```
[frm_entry_archived_list]
```

It will render:

- Filterable form (Form, Order #, and general search)
- Results table with checkbox selection
- Bulk restore action
- Paginated display

---

## 🚀 Getting Started

### 1. Install the Plugin

- Upload to your `/wp-content/plugins/` directory.
- Activate the plugin from your WordPress dashboard.

### 2. Create Archive Tables

Go to Formidable settings


## 📂 Plugin Structure

```
formidable-optimizer-wp/
├── assets/
├── classes/
│   ├── frm-optimizer-settings.php
│   ├── frm-entry-helper.php
│   ├── frm-entry-replacer.php
│   ├── frm-optimizer-archive.php
│   └── frm-optimizer-admin.php
├── shortcodes/
│   └── frm-entry-archived-list.php
├── formidable-optimizer.php
└── README.md
```

---

## 🪪 License

This plugin is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
