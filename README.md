# Wasanthaye-Mahagedara
Web-based management system for Wasanthaye Mahagedara, covering customer management, wedding calendar, billing and invoices, inventory, and reports across multiple showrooms. Built with PHP MVC.

The wedding calendar stores wedding records in the `weddings` table. New installations
should use the table definition in `database/schema.sql`; existing installations can
apply `database/migrations/001_create_weddings.sql` after the `branches` table exists.
