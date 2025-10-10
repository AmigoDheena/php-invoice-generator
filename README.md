<p align="center">
  <img src="assets/img/logo.png" alt="PHP Invoice Generator Logo" width="280">
</p>

# PHP Invoice Generator (No Database)

A simple, modern, and self-hosted invoice generator web app built with PHP and Tailwind CSS. No database required—data is stored in JSON files for easy setup and portability.

![UI Tailwind CSS](https://img.shields.io/badge/UI-Tailwind%20CSS-38bdf8)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![PDF Export](https://img.shields.io/badge/PDF-dompdf-4b4b4b)
![Status](https://img.shields.io/badge/Status-Production%20Ready-success)
![No Database](https://img.shields.io/badge/No%20Database-JSON%20Storage-44cc11)

## Features

- Create, edit, and delete invoices
- Add multiple items per invoice (description, quantity, price)
- Option to include or exclude tax (18%)
- Manage multiple company profiles ("Invoice From")
- Product & Service Catalog with autocomplete selection when creating invoices
- Advanced Search & Filtering with custom saved filters
- Column sorting for invoice management
- Export filtered invoice lists to CSV
- Add and display company banking details on invoices and PDFs
- Client data reuse for quick invoice creation with returning clients
- All data stored in JSON files (no SQL or DB setup needed)
- Export invoices as PDF (using dompdf)
- Responsive UI with Tailwind CSS
- Edit invoices at any time
- List and manage all invoices from the dashboard
- Pagination for better performance with many invoices
- Currency displayed as `Rs.` throughout the app and PDF (for Indian Rupees)
- No database required—just PHP and file permissions
- Comprehensive data management with backups, import/export, and MySQL migration
- Cloud backup integration with Dropbox and Google Drive
- Scheduled automatic backups with configurable retention policies
- Built-in troubleshooting tools for logo and GD extension issues
- AJAX-powered product search and autocomplete
- Utility tools for testing and diagnostics

## Requirements

- PHP 7.4 or higher
- Composer (for PDF export)
- Web server (Apache, Nginx, XAMPP, etc.)
- PHP GD Extension (optional but recommended for logo display in PDFs)

## Installation

1. **Clone the repository:**
   ```
   git clone https://github.com/amigodheena/php-invoice-generator.git
   cd php-invoice-generator
   ```
2. **Install dependencies:**
   ```
   composer install
   ```
3. **Set permissions:**
   Ensure the `data/` and `uploads/` directories are writable by the web server:
   ```
   chmod 755 data
   mkdir -p uploads/logos
   chmod 755 uploads uploads/logos
   ```
4. **Access the app:**
   Open your browser and go to `http://localhost/path-to/php-invoice-generator`

## Usage

- **Create Invoice:** Click "Create New Invoice", fill in details, add items, and save.
- **Reuse Client Data:** Select existing clients from the dropdown to quickly fill client details.
- **Manage Products:** Click "Products" in the navigation to add, edit, and organize your product catalog.
- **Select Products:** When creating invoices, search for products using the autocomplete field to quickly add them.
- **Edit Invoice:** Click the edit icon next to any invoice in the dashboard.
- **Export PDF:** Click the PDF icon to download a PDF version of any invoice.
- **Search & Filter:** Click "Search & Filter" to open the advanced search panel with multiple filtering options.
- **Sort Invoices:** Click on column headers in the invoice list to sort by that column.
- **Save Filters:** Apply filters and click "Save Filter" to save your filter combinations for future use.
- **Export CSV:** Apply filters and click "Export CSV" to download filtered invoice data as a CSV file.
- **Manage Saved Filters:** Click "Saved Filters" to view, apply, or delete your saved filter combinations.
- **Manage Companies:** Add or edit your own company profiles under "Manage Companies". You can now add banking details for payment info.
- **Navigate Pages:** Use pagination controls to browse through invoices when you have many.
- **No Database:** All data is stored in `/data` as JSON files. You can back up or move your data easily.
- **Manage Data:** Click "Data Management" to access backup, import/export, and MySQL migration features.
- **Create Backups:** Click "Create Backup" to manually create a backup of all your data.
- **Configure Cloud Backups:** Connect to Dropbox or Google Drive for automated cloud backups.
- **Import/Export Data:** Use the import/export tools for data portability and migration.
- **MySQL Migration:** Generate MySQL schema and migrate your data to a MySQL database when needed.
- **Troubleshoot Logo Issues:** Use `logo_test.php` to diagnose logo display problems in PDF invoices.
- **Enable GD Extension:** Use `enable_gd.php` for step-by-step instructions to enable PHP GD extension for logo support.

## Advanced Features

### Pagination
- The dashboard (`index.php`) now supports pagination for the invoices list. By default the dashboard shows **10 invoices per page** to keep the list fast and easy to navigate.
- Use the `page` query parameter to navigate pages.
- The pagination control shows previous/next, first/last buttons and a range of page numbers (up to 5 visible at once). The current page is highlighted.
- If you want to change the number of invoices per page, edit the `$perPage` variable in `index.php` (search for `$perPage = 10;`). You can also update the `getInvoices()` call signature in `includes/functions.php` if you need more advanced control.

### Client Data Reuse
- When creating or editing invoices, you can now select from previously used clients to quickly fill in client details.
- The system automatically extracts unique clients from your existing invoices based on email address.
- Select a client from the dropdown at the top of the client information section to auto-populate the client name, email, and address.
- You can still manually enter client details for new clients or to modify existing information.

### Product & Service Catalog
- Maintain a catalog of products and services that you frequently include in invoices
- Organize products by categories to find them easily
- Set default prices for each product to ensure consistent pricing
- When creating or editing invoices, use the autocomplete search to quickly find and add products
- Product details (description and price) are auto-filled when selected
- Add SKUs/product codes for better inventory tracking

### Advanced Search & Filtering
- **Comprehensive Filters**: Filter invoices by client name/email, invoice number, status, document type, date range, and amount range
- **Column Sorting**: Sort your invoice list by any column (date, client name, amount, status, etc.) in ascending or descending order
- **Filter Combinations**: Apply multiple filters simultaneously for precise invoice searching
- **Saved Filters**: Save your commonly used filter combinations with custom names for quick access
- **Filter Management**: View, apply, and delete your saved filters from the dedicated Saved Filters page
- **CSV Export**: Export filtered invoice lists to CSV format with all relevant invoice data
- **Visual Indicators**: Active filters and sort options are clearly displayed with visual indicators
- **Responsive Design**: All filtering options work seamlessly on mobile and desktop devices

### Data Management
- **Automated Backups**: Schedule regular backups to keep your data safe
- **Cloud Integration**: Connect to Dropbox or Google Drive for cloud storage backups
- **Backup History**: View and manage your backup history with detailed information
- **Data Export**: Export all your data in a ZIP archive for portability
- **Data Import**: Import data from a ZIP archive for easy migration or restoration
- **MySQL Migration**: Generate MySQL schema and migrate your data to a MySQL database
- **Storage Statistics**: Get insights into your data storage usage and growth

Notes:
- Pagination is implemented server-side by slicing the JSON invoice array. For very large datasets you may want to switch to a database-backed approach for better performance.
- Client data reuse is based on unique email addresses across all invoices.
- Product catalog supports unlimited number of products and categories.
- Saved filters are stored in the `data/saved_filters.json` file.
- Cloud backup settings are stored in the `data/cloud_backup_settings.json` file.
- Automatic backups require setting up a cron job to call `auto_backup.php` at your desired frequency.


## File Structure

### Main Application Files
- `/index.php` — Dashboard (list invoices)
- `/create_invoice.php` — Create new invoice
- `/edit_invoice.php` — Edit existing invoice
- `/view_invoice.php` — View invoice details
- `/download_pdf.php` — Export invoice as PDF (with banking details and Rs. currency)
- `/delete_invoice.php` — Delete invoice

### Product & Filter Management
- `/manage_products.php` — Manage products/services catalog
- `/saved_filters.php` — Manage saved filter combinations
- `/export_csv.php` — Export invoice list to CSV based on filters

### Company Management
- `/manage_companies.php` — Manage company profiles and banking details

### Data Management
- `/manage_data.php` — Data management features (backup, import/export, MySQL migration)
- `/download_backup.php` — Download backup files securely
- `/import_data.php` — Import data from uploaded ZIP archives
- `/auto_backup.php` — Automated backup endpoint (for cron jobs)
- `/purge_old_backups.php` — Clean up old backup files (for cron jobs)

### Cloud & Database Features
- `/save_cloud_settings.php` — Save cloud backup configuration
- `/migrate_to_mysql.php` — Generate schema and migrate data to MySQL

### Helper & Diagnostic Tools
- `/logo_test.php` — Logo troubleshooting tool for PDF invoice issues
- `/enable_gd.php` — PHP GD Extension helper and installation guide
- `/test_data_functions.php` — Data functions testing utility

### Core Directories
- `/includes/functions.php` — Core PHP logic
- `/includes/cloud/` — Cloud storage provider integrations
- `/data/` — JSON data storage (invoices, companies, products, saved filters)
- `/data/backups/` — Stored backup archives
- `/data/exports/` — Stored export archives
- `/data/schemas/` — Generated MySQL schemas
- `/uploads/logos/` — Company logo storage
- `/vendor/` — Composer dependencies (dompdf, etc.)
- `/assets/` — CSS, JS, and static files
  - `/assets/css/` — Custom stylesheets
  - `/assets/js/` — JavaScript files (product selection, main logic)
  - `/assets/img/` — Images and logo
- `/ajax/` — AJAX endpoints for dynamic data loading

## Setting Up Automated Backups

To set up automated backups:

1. **Edit Security Token:**
   Open `auto_backup.php` and replace `YOUR_SECURE_TOKEN_HERE` with a secure random string of your choice.

2. **Set Up Cron Job (Linux/Unix):**
   ```
   # Daily backup at 3:00 AM
   0 3 * * * curl https://your-domain.com/path-to/auto_backup.php?token=YOUR_SECURE_TOKEN_HERE > /dev/null 2>&1
   ```

3. **Set Up Task Scheduler (Windows):**
   Create a scheduled task that runs a command like:
   ```
   curl http://localhost/path-to/auto_backup.php?token=YOUR_SECURE_TOKEN_HERE
   ```

4. **Configure Cloud Backups:**
   - Go to "Data Management" in the application
   - Click on the "Backup" tab
   - Enter your cloud service API key and settings
   - Enable automatic backups
   - Select your desired backup frequency

5. **Set Up Automatic Backup Cleanup (Optional):**
   To prevent excessive storage usage, set up a cron job to purge old backups:
   ```
   # Weekly cleanup on Sunday at 2:00 AM
   0 2 * * 0 curl http://localhost/path-to/purge_old_backups.php?token=YOUR_SECURE_TOKEN_HERE > /dev/null 2>&1
   ```
   Note: Edit `purge_old_backups.php` to set your secure token and configure retention settings.

## Troubleshooting

### Logo Issues in PDF Invoices

If company logos are not appearing in your PDF invoices, you can use the built-in troubleshooting tools:

1. **Logo Test Tool** (`logo_test.php`):
   - Navigate to `http://localhost/path-to/php-invoice-generator/logo_test.php`
   - This tool diagnoses logo display issues and checks:
     - GD extension status
     - Logo directory permissions
     - Company logo file paths and accessibility
   - Follow the on-screen troubleshooting steps

2. **GD Extension Helper** (`enable_gd.php`):
   - Navigate to `http://localhost/path-to/php-invoice-generator/enable_gd.php`
   - Provides step-by-step instructions for enabling PHP GD extension
   - Platform-specific instructions for:
     - XAMPP on Windows
     - Linux/Unix systems
     - General Windows installations

### Common Issues

**GD Extension Not Installed:**
- **XAMPP (Windows):** Edit `C:\xampp\php\php.ini`, find `;extension=gd`, remove the semicolon, and restart Apache
- **Linux/Ubuntu:** Run `sudo apt-get install php-gd` and restart your web server
- **CentOS/RHEL:** Run `sudo yum install php-gd` and restart your web server

**Logo Upload Issues:**
- Ensure the `uploads/logos/` directory exists and has proper permissions (755)
- Verify uploaded logo files have read permissions (644)
- Supported formats: JPG, PNG, GIF

**PDF Generation Issues:**
- Make sure Composer dependencies are installed: `composer install`
- Check if dompdf library is available in `/vendor/` directory

**Data File Permissions:**
- The `data/` directory must be writable by the web server (755)
- All JSON files in `data/` should be readable/writable (644)

## Development

- All business logic is in `includes/functions.php`.
- UI is styled with [Tailwind CSS](https://tailwindcss.com/).
- PDF export uses [dompdf](https://github.com/dompdf/dompdf).
- No database required—just PHP and file permissions.
- Currency is consistently shown as `Rs.` for Indian Rupees in all views and PDFs.
- Cloud storage integration uses custom API libraries (implement in `includes/cloud/` for your chosen provider).

## .gitignore

- `/data/` and `/vendor/` are ignored by default (see `.gitignore`).

## Contributors

- **Lead Developer:** [AmigoDheena](https://github.com/AmigoDheena)

## License

MIT License. Free for personal and commercial use.

## Credits

This project was developed by [AmigoDheena](https://github.com/AmigoDheena). If you find this project useful, please consider starring the repository on GitHub or contributing to its development.