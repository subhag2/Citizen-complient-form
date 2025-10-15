<?php
if (!defined('ABSPATH')) exit;

class SR_Email {
    public static function send_confirmation($id, $to, $pin = '') {
        $subject = "Service Request Confirmation #$id";
        
        $message = self::get_confirmation_template($id, $pin);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <cameron.gales@carterlake-ia.gov>',
            'Reply-To: cameron.gales@carterlake-ia.gov'
        ];
        
        // Send to user
        $user_sent = wp_mail($to, $subject, $message, $headers);
        
        // Also send notification to admin
        $admin_sent = self::send_admin_notification($id, $to, $pin);
        
        return $user_sent; // Return user email status for backwards compatibility
    }
    
    public static function send_admin_notification($id, $user_email, $pin = '') {
        // $admin_email = "subhagnkp@gmail.com";
        $admin_email = "cameron.gales@carterlake-ia.gov";
        // $admin_email = get_option('admin_email');
        $subject = "New Service Request Submitted #$id";
        
        $message = self::get_admin_notification_template($id, $user_email, $pin);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>',
            'Reply-To: cameron.gales@carterlake-ia.gov'
        ];
        
        return wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Send an email to the user when admin replies to their request.
     */
    public static function send_reply_notification($id, $to, $reply_text) {
        $subject = "Update on Service Request #$id";

        $message = self::get_reply_template($id, $reply_text);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        ];

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Send an email to the user when the status changes.
     */
    public static function send_status_change_notification($id, $to, $new_status) {
        $subject = "Service Request #$id Status Update: $new_status";

        $message = self::get_status_change_template($id, $new_status);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        ];

        return wp_mail($to, $subject, $message, $headers);
    }
    
    private static function get_confirmation_template($id, $pin) {
        $site_name = get_option('blogname');
        $site_url = get_site_url();
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Service Request Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3b82f6; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .info-box { background: white; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #3b82f6; }
                .important { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Service Request Confirmation</h1>
                    <p>Thank you for submitting your service request</p>
                </div>
                <div class='content'>
                    <p>Dear Resident,</p>
                    
                    <p>Your service request has been successfully submitted and is now being processed by our team.</p>
                    
                    <div class='info-box'>
                        <h3>Your Request Details:</h3>
                        <p><strong>Confirmation Number:</strong> #{$id}</p>" . 
                        (!empty($pin) ? "<p><strong>Your PIN:</strong> {$pin}</p>" : "<p><strong>PIN:</strong> Not set (you can track using just the confirmation number)</p>") . "
                       
                        <p><strong>Submitted:</strong> " . current_time('F j, Y \a\t g:i A') . "</p>
                    </div>
                    <p>If you have any questions or need to provide additional information, please contact us and reference your confirmation number.</p>
                    
                    <p>Thank you for helping us improve our community services.</p>
                    
                    <p>Best regards,<br>
                    {$site_name} Service Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from <a href='{$site_url}'>{$site_name}</a></p>
                    <p>Please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private static function get_reply_template($id, $reply_text) {
        $site_name = get_option('blogname');
        $site_url = get_site_url();

        return "
        <html>
        <body>
            <h2>Update for Service Request #{$id}</h2>
            <p>Dear Resident,</p>
            <p>Our team has added the following reply to your service request:</p>
            <div style='background:#f4f4f4;padding:15px;border-radius:6px;margin:10px 0;'>" . nl2br(esc_html($reply_text)) . "</div>
            <p>If you have further questions, please reply to this email or check your request online.</p>
            <p>Regards,<br>{$site_name} Service Team</p>
            <p><small><a href='{$site_url}'>Visit {$site_name}</a></small></p>
        </body>
        </html>
        ";
    }

    private static function get_status_change_template($id, $new_status) {
        $site_name = get_option('blogname');
        $site_url = get_site_url();

        return "
        <html>
        <body>
            <h2>Service Request #{$id} Status Updated</h2>
            <p>Dear Resident,</p>
            <p>The status for your service request #{$id} has been updated to: <strong>{$new_status}</strong>.</p>
            <p>If you have questions, please check the request details online or contact us.</p>
            <p>Regards,<br>{$site_name} Service Team</p>
            <p><small><a href='{$site_url}'>Visit {$site_name}</a></small></p>
        </body>
        </html>
        ";
    }
    
    private static function get_admin_notification_template($id, $user_email, $pin = '') {
        $site_name = get_option('blogname');
        $site_url = get_site_url();
        $admin_url = admin_url('admin.php?page=sr-requests');
        
        // Get the request details from database
        $request = SR_DB::get_request($id);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>New Service Request Notification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .info-box { background: white; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #dc3545; }
                .details { background: white; padding: 20px; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
                .btn { display: inline-block; background: #dc3545; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔔 New Service Request</h1>
                    <p>A new service request has been submitted</p>
                </div>
                <div class='content'>
                    <p>Hello Administrator,</p>
                    
                    <p>A new service request has been submitted and requires your attention.</p>
                    
                    <div class='info-box'>
                        <h3>Request Summary:</h3>
                        <p><strong>Confirmation Number:</strong> #{$id}</p>
                        <p><strong>User Email:</strong> {$user_email}</p>" . 
                        (!empty($pin) ? "<p><strong>PIN Protected:</strong> Yes</p>" : "<p><strong>PIN Protected:</strong> No</p>") . "
                        <p><strong>Submitted:</strong> " . current_time('F j, Y \a\t g:i A') . "</p>
                    </div>" . 
                    
                    ($request ? "
                    <div class='details'>
                        <h3>Request Details:</h3>
                        <p><strong>Description:</strong> " . esc_html($request->description) . "</p>
                        <p><strong>Location:</strong> " . esc_html($request->location_general) . "</p>" . 
                        ($request->address ? "<p><strong>Address:</strong> " . esc_html($request->address) . "</p>" : "") .
                        (!$request->anonymous && $request->name ? "<p><strong>Contact Name:</strong> " . esc_html($request->name) . "</p>" : "") .
                        (!$request->anonymous && $request->phone ? "<p><strong>Phone:</strong> " . esc_html($request->phone) . "</p>" : "") .
                        ($request->anonymous ? "<p><strong>Anonymous Request:</strong> Yes</p>" : "") . "
                    </div>" : "") . "
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$admin_url}' class='btn'>View in Admin Panel</a>
                    </div>
                    
                    
                    <p>Best regards,<br>
                    {$site_name} System</p>
                </div>
                <div class='footer'>
                    <p>This is an automated notification from <a href='{$site_url}'>{$site_name}</a></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
