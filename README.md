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

## Requirements

- PHP 7.4 or higher
- Composer (for PDF export)
- Web server (Apache, Nginx, XAMPP, etc.)

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
   Ensure the `data/` directory is writable by the web server:
   ```
   chmod 755 data
   ```
4. **Access the app:**
   Open your browser and go to `http://localhost/path-to/php-invoice-generator`

## Usage

- **Create Invoice:** Click "Create New Invoice", fill in details, add items, and save.
- **Reuse Client Data:** Select existing clients from the dropdown to quickly fill client details.
- **Edit Invoice:** Click the edit icon next to any invoice in the dashboard.
- **Export PDF:** Click the PDF icon to download a PDF version of any invoice.
- **Manage Companies:** Add or edit your own company profiles under "Manage Companies". You can now add banking details for payment info.
- **Navigate Pages:** Use pagination controls to browse through invoices when you have many.
- **No Database:** All data is stored in `/data` as JSON files. You can back up or move your data easily.

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

Notes:
- Pagination is implemented server-side by slicing the JSON invoice array. For very large datasets you may want to switch to a database-backed approach for better performance.
- Client data reuse is based on unique email addresses across all invoices.


## File Structure

- `/index.php` — Dashboard (list invoices)
- `/create_invoice.php` — Create new invoice
- `/edit_invoice.php` — Edit existing invoice
- `/view_invoice.php` — View invoice details
- `/download_pdf.php` — Export invoice as PDF (with banking details and Rs. currency)
- `/manage_companies.php` — Manage company profiles and banking details
- `/delete_invoice.php` — Delete invoice
- `/includes/functions.php` — Core PHP logic
- `/data/` — JSON data storage (invoices, companies)
- `/vendor/` — Composer dependencies (dompdf, etc.)
- `/assets/` — CSS, JS, and static files

## Development

- All business logic is in `includes/functions.php`.
- UI is styled with [Tailwind CSS](https://tailwindcss.com/).
- PDF export uses [dompdf](https://github.com/dompdf/dompdf).
- No database required—just PHP and file permissions.
- Currency is consistently shown as `Rs.` for Indian Rupees in all views and PDFs.

## .gitignore

- `/data/` and `/vendor/` are ignored by default (see `.gitignore`).

## Contributors

- **Lead Developer:** [AmigoDheena](https://github.com/AmigoDheena)

## License

MIT License. Free for personal and commercial use.

## Credits

This project was developed by [AmigoDheena](https://github.com/AmigoDheena). If you find this project useful, please consider starring the repository on GitHub or contributing to its development.