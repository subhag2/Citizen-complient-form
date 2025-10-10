# Service Request Tracking System

## Overview
The Service Request Tracking System allows users to track their submitted service requests using a Confirmation Number and PIN. This secure system provides comprehensive details about request status, location, and progress timeline.

## Features
- 🔒 **Secure Authentication**: Uses hashed PINs for security
- 📋 **Comprehensive Details**: Shows all request information (respecting privacy settings)
- 📈 **Status Timeline**: Visual timeline showing request progress
- 📱 **Responsive Design**: Works perfectly on mobile and desktop
- ✅ **Real-time Validation**: Client-side form validation with helpful error messages
- 🔌 **Easy Integration**: Simple shortcode implementation

## Installation
The tracking functionality is automatically included with the Service Requests Manager plugin. No additional setup required.

## Usage

### For WordPress Administrators
Add the tracking shortcode to any page or post where you want users to be able to track their requests:

```
[service_request_tracking]
```

### For End Users
1. **Get Your Tracking Information**: After submitting a service request, users receive:
   - **Confirmation Number**: The unique ID of their request
   - **PIN**: The 4-6 digit security code they created during submission

2. **Track Your Request**: Visit the tracking page and enter:
   - Confirmation Number
   - PIN
   - Click "Track Request"

3. **View Results**: The system displays:
   - Request details and description
   - Location information
   - Contact information (if not anonymous)
   - Current status
   - Progress timeline

## Available Shortcodes

### Service Request Form
```
[service_request_form]
```
Creates a multi-step form for users to submit new service requests.

### Service Request Tracking
```
[service_request_tracking]
```
Creates a tracking interface for users to check their request status.

## Request Statuses
- **Pending**: Request has been submitted and is waiting to be reviewed
- **In Progress**: Request is currently being worked on
- **Completed**: Request has been resolved
- **Cancelled**: Request has been cancelled

## Security Features
- PIN verification using secure password hashing
- Input validation and sanitization
- WordPress nonce verification for AJAX requests
- No sensitive information exposed in error messages

## Responsive Design
The tracking interface is fully responsive and includes:
- Mobile-first design approach
- Touch-friendly form controls
- Optimized layouts for all screen sizes
- Accessible color schemes and typography

## Technical Implementation

### File Structure
```
wp-content/plugins/service-requests/
├── includes/
│   ├── class-sr-frontend.php    # Shortcode rendering
│   ├── class-sr-ajax.php        # AJAX handlers
│   └── class-sr-db.php          # Database operations
├── assets/
│   ├── js/sr-form.js            # JavaScript functionality
│   └── css/sr-form.css          # Styling
└── tracking-demo.html           # Demo page
```

### Database Schema
The tracking system uses the existing `service_requests` table with these key fields:
- `id`: Confirmation number (auto-increment primary key)
- `pin_hash`: Hashed PIN for security
- `status`: Current request status
- `created_at`: Submission timestamp
- Additional fields for request details

### AJAX Endpoints
- `sr_track_request`: Handles tracking form submissions

## Customization

### Custom Styling
Add custom CSS to override default styles:

```css
.sr-tracking {
    /* Your custom styles */
}

.status-badge {
    /* Custom status badge styles */
}
```

### Custom Status Values
Update the status handling in `class-sr-ajax.php` to support additional status types.

## Troubleshooting

### Common Issues

**"Security check failed"**
- Ensure WordPress nonces are properly configured
- Check that AJAX requests include the security token

**"No service request found"**
- Verify the confirmation number is correct
- Check that the request exists in the database

**"Invalid PIN"**
- Ensure the PIN matches the one created during submission
- PINs are case-sensitive and must be 4-6 digits

### Error Logging
Enable WordPress debugging to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Development

### Adding New Features
The tracking system is modular and can be extended:

1. **Custom Fields**: Add new fields to the tracking display in `class-sr-frontend.php`
2. **Status Types**: Extend status handling in `class-sr-ajax.php`
3. **Validation**: Add custom validation rules in the JavaScript file
4. **Styling**: Extend the CSS for custom appearance

### Testing
Use the included `tracking-demo.html` file to test the interface design before implementing in WordPress.

## Support
For issues or feature requests, please check the plugin documentation or contact the development team.

## Version History
- **v1.1**: Added service request tracking functionality
- **v1.0**: Initial release with form submission