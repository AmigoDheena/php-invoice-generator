# Invoice Generator

A simple web-based invoice generator built with PHP and Tailwind CSS.

## Features

- Create and manage invoices with client details
- Add multiple items with quantity and price
- Option to include or exclude tax (18%)
- Support for multiple company profiles
- JSON file storage (no database required)
- Export invoices as PDF
- Edit invoices at any time
- Responsive design using Tailwind CSS

## Requirements

- PHP 7.4 or higher
- Composer (for PDF generation dependency)
- Web server (Apache/Nginx)

## Installation

1. Clone or download this repository to your web server directory

2. Install dependencies using Composer:

```
cd invoice-generator
composer install
```

3. Make sure the `data` directory is writable:

```
chmod 755 data
```

4. Access the application through your web browser (e.g., http://localhost/ham/lab/invoice-generator)

## Usage

### Creating Invoices

1. Click on "Create New Invoice" from the main dashboard
2. Fill in the client information (company name, email, address)
3. Set the invoice date and due date
4. Select your company profile from the dropdown
5. Add items with descriptions, quantities, and prices
6. Toggle tax on/off as needed
7. Add any notes or terms
8. Click "Create Invoice" to save

### Managing Invoices

- View all invoices from the main dashboard
- Click the view icon to see invoice details
- Click the edit icon to modify an invoice
- Click the PDF icon to download as PDF
- Click the delete icon to remove an invoice

### Managing Companies

1. Click on "Manage Companies" from the main dashboard
2. View existing company profiles
3. Add new companies with name, email, address, and phone
4. Edit existing companies by clicking the edit icon
5. Delete companies using the delete icon

## File Structure

- `index.php` - Main dashboard showing all invoices
- `create_invoice.php` - Form to create new invoices
- `edit_invoice.php` - Form to edit existing invoices
- `view_invoice.php` - Display invoice details
- `download_pdf.php` - Generate and download PDF invoice
- `manage_companies.php` - Company profile management
- `delete_invoice.php` - Delete invoice handler
- `includes/functions.php` - Core functions for app functionality
- `data/` - Directory for JSON data storage
- `vendor/` - Composer dependencies (after installation)
- `assets/` - CSS, JS, and other static assets

## License

This project is open-source and available for personal or commercial use.
