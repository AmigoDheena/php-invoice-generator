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
- All data stored in JSON files (no SQL or DB setup needed)
- Export invoices as PDF (using dompdf)
- Responsive UI with Tailwind CSS
- Edit invoices at any time
- List and manage all invoices from the dashboard

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
- **Edit Invoice:** Click the edit icon next to any invoice in the dashboard.
- **Export PDF:** Click the PDF icon to download a PDF version of any invoice.
- **Manage Companies:** Add or edit your own company profiles under "Manage Companies".
- **No Database:** All data is stored in `/data` as JSON files. You can back up or move your data easily.

## File Structure

- `/index.php` — Dashboard (list invoices)
- `/create_invoice.php` — Create new invoice
- `/edit_invoice.php` — Edit existing invoice
- `/view_invoice.php` — View invoice details
- `/download_pdf.php` — Export invoice as PDF
- `/manage_companies.php` — Manage company profiles
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

## .gitignore

- `/data/` and `/vendor/` are ignored by default (see `.gitignore`).

## License

MIT License. Free for personal and commercial use.