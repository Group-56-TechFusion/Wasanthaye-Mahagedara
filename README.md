# Wasanthaye-Mahagedara
Web-based management system for Wasanthaye Mahagedara, covering customer management, wedding calendar, billing and invoices, inventory, and reports across multiple showrooms. Built with PHP MVC.

The wedding calendar stores wedding records in the `weddings` table. New installations
should use the table definition in `database/schema.sql`; existing installations can
apply `database/migrations/001_create_weddings.sql` after the `branches` table exists.

Administrators can create administrator and showroom-manager login accounts from
the User Management page. Passwords are stored as PHP password hashes in `app_db.users`;
showroom-manager accounts must be assigned to a showroom.

The dashboard is role-scoped: administrators see performance across showrooms,
while showroom managers see metrics and activity for their assigned location.

Customer Management combines existing customer details from bills with saved customer
contacts. New installations include the `customers` table in `database/schema.sql`;
existing installations should apply `database/migrations/003_create_customers.sql`.

Showroom-manager logins require an administrator-approved browser device. Administrators
can review, approve, reject, or revoke devices from Device Approvals; administrator
logins are not gated by device approval. Existing installations should apply
`database/migrations/004_create_login_devices.sql`. Device trust uses a long-lived,
HTTP-only cookie; clearing browser cookies or using a private window creates a new
approval request. On HTTPS deployments, the device and session cookies are marked Secure.
