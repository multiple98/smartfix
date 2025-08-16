# SmartFix Admin Panel

## Overview
The SmartFix Admin Panel provides a comprehensive interface for managing all aspects of the SmartFix platform, including users, technicians, service requests, and more.

## First-Time Setup
When accessing the admin panel for the first time, you may need to run the database update scripts to ensure all required database columns are present:

1. For user management: Navigate to: `/admin/add_status_column.php`
2. For service requests: Navigate to: `/admin/add_service_request_columns.php`

These scripts will add any missing columns to the database tables and ensure the admin panel functions correctly.

## Features

### Dashboard
- View key statistics and metrics
- Quick access to common admin tasks
- Recent service requests overview
- Analytics and charts

### User Management
- View and manage all users
- Filter users by type, status, and search terms
- Activate/deactivate user accounts
- Edit user information and roles

### Service Requests
- View all service requests
- Filter by status, date, and search terms
- Assign technicians to service requests
- Update request status
- Add notes to service requests

### Technician Management
- View and manage technicians
- Monitor technician performance
- Assign service requests to technicians

### Reports & Analytics
- Generate reports on platform usage
- View service request trends
- Monitor technician performance
- Export data for further analysis

## Troubleshooting
If you encounter any issues with the admin panel:

1. Check that your database connection is working properly
2. Ensure all required database tables and columns exist
3. Run the database update scripts:
   - For user management issues: `/admin/add_status_column.php`
   - For service request issues: `/admin/add_service_request_columns.php`
4. Check the PHP error logs for any specific error messages
5. If you see "Column not found" errors, run the appropriate update script above

## Security
- Always log out when you're done using the admin panel
- Regularly update your admin password
- Do not share your admin credentials with others

## Contact
For technical support or questions about the admin panel, please contact the system administrator.