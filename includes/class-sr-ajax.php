<?php
if (!defined('ABSPATH')) exit;

class SR_Ajax {
    public function __construct() {
        // PIN step removed; save_step4 handler registration removed

        add_action('wp_ajax_sr_submit_form', [$this, 'submit_form']);
        add_action('wp_ajax_nopriv_sr_submit_form', [$this, 'submit_form']);

        add_action('wp_ajax_sr_track_request', [$this, 'track_request']);
        add_action('wp_ajax_nopriv_sr_track_request', [$this, 'track_request']);
    }

    // save_step4 removed - PIN is generated server-side during final submit

    public function submit_form() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'], 'sr_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!session_id()) {
            session_start();
        }

        // Get saved form data and merge with final data
        $saved_data = $_SESSION['sr_form_data'] ?? [];
        $final_data = $this->sanitize_form_data($_POST['formData']);
        
        // Merge the data
        $complete_data = array_merge($saved_data, $final_data);
        
        // If PIN was not provided by the frontend (step removed), generate a 4-6 digit PIN here
        $generated_pin = '';
        if (empty($complete_data['pin_hash'])) {
            // generate 4-6 digit PIN (1000-999999)
            $generated_pin = strval(random_int(1000, 999999));
            // ensure it's between 4 and 6 digits
            $complete_data['pin_hash'] = password_hash($generated_pin, PASSWORD_DEFAULT);
        } else {
            // If pin was stored in session (older flows), try to preserve plaintext
            $generated_pin = $saved_data['pin'] ?? '';
        }

        // Validate required fields
        $validation_result = $this->validate_complete_data($complete_data);
        if (!$validation_result['valid']) {
            wp_send_json_error($validation_result['message']);
            return;
        }

        // Prepare data for database
        $db_data = $this->prepare_db_data($complete_data);
        
    // Insert into database
    $request_id = SR_DB::insert_request($db_data);
        
        if (!$request_id) {
            wp_send_json_error('Failed to save request to database');
            return;
        }

        // If request was submitted anonymously, notify admin only.
        // For non-anonymous submissions, send confirmation to the user (which also notifies admin inside send_confirmation()).
        if (!empty($db_data['anonymous'])) {
            // Anonymous submission - notify admin only
            SR_Email::send_admin_notification($request_id, $db_data['email_contact'] ?? '', $generated_pin);
        } else {
            // Non-anonymous - send confirmation to user (and admin notification is handled inside send_confirmation)
            if (!empty($db_data['email_contact'])) {
                SR_Email::send_confirmation($request_id, $db_data['email_contact'], $generated_pin);
            }
        }
        
        // Clear session data
        unset($_SESSION['sr_form_data']);

        wp_send_json_success([
            'message' => 'Your service request has been submitted successfully!',
            'confirmation' => $request_id,
            'pin' => $generated_pin
        ]);
    }

    private function sanitize_form_data($data) {
        $sanitized = [];
        
        if (!is_array($data)) {
            return $sanitized;
        }

        foreach ($data as $key => $value) {
            $key = sanitize_key($key);
            
            switch ($key) {
                case 'description':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
                    
                case 'email_contact':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                    
                case 'anonymous':
                    $sanitized[$key] = (int) $value;
                    break;
                    
                case 'pin':
                case 'pin_confirm':
                    $sanitized[$key] = preg_replace('/[^0-9]/', '', $value);
                    break;
                    
                case 'phone':
                    $sanitized[$key] = preg_replace('/[^0-9\-\(\)\s\+\.]/', '', $value);
                    break;
                    
                case 'zipcode':
                case 'zip_contact':
                    $sanitized[$key] = preg_replace('/[^0-9\-]/', '', $value);
                    break;
                    
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        return $sanitized;
    }

    private function validate_complete_data($data) {
        $errors = [];
        
        // Required fields validation
        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        }
        
        if (empty($data['location_general'])) {
            $errors[] = 'General location is required';
        }
        
        if (empty($data['pin_hash'])) {
            $errors[] = 'PIN is required';
        }
        
        // Email validation
        if (!empty($data['email_contact']) && !is_email($data['email_contact'])) {
            $errors[] = 'Invalid contact email format';
        }
        
        // 'review_email' removed; we validate only the contact email when present
        
        // Anonymous validation - if not anonymous, name should be provided
        if (empty($data['anonymous']) && empty($data['name'])) {
            $errors[] = 'Name is required for non-anonymous requests';
        }
        
        // ZIP code validation
        if (!empty($data['zipcode']) && !preg_match('/^\d{5}(-\d{4})?$/', $data['zipcode'])) {
            $errors[] = 'Invalid ZIP code format';
        }
        
        if (!empty($data['zip_contact']) && !preg_match('/^\d{5}(-\d{4})?$/', $data['zip_contact'])) {
            $errors[] = 'Invalid contact ZIP code format';
        }
        
        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid' : implode(', ', $errors)
        ];
    }

    private function prepare_db_data($data) {
        // Map form fields to database columns
        $db_data = [
            'description' => $data['description'] ?? '',
            'location_general' => $data['location_general'] ?? '',
            'location_intersection' => $data['location_intersection'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'zipcode' => $data['zipcode'] ?? '',
            'anonymous' => $data['anonymous'] ?? 0,
            'name' => $data['name'] ?? '',
            'street' => $data['street'] ?? '',
            'city_contact' => $data['city_contact'] ?? '',
            'state_contact' => $data['state_contact'] ?? '',
            'zip_contact' => $data['zip_contact'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email_contact' => $data['email_contact'] ?? '',
            'pin_hash' => $data['pin_hash'] ?? '',
            'status' => 'Pending'
        ];
        
        return $db_data;
    }

    public function track_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'], 'sr_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Get and validate input
        $confirmation_number = intval($_POST['confirmation_number'] ?? 0);
        $pin = preg_replace('/[^0-9]/', '', $_POST['tracking_pin'] ?? '');

        if (empty($confirmation_number) || empty($pin)) {
            wp_send_json_error('Please provide both confirmation number and PIN.');
            return;
        }

        // Validate PIN format
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            wp_send_json_error('PIN must be 4-6 digits.');
            return;
        }

        // Get request from database
        $request = SR_DB::get_request($confirmation_number);
        
        if (!$request) {
            wp_send_json_error('No service request found with this confirmation number.');
            return;
        }

        // Verify PIN
        if (!password_verify($pin, $request->pin_hash)) {
            wp_send_json_error('Invalid PIN. Please check your PIN and try again.');
            return;
        }

        // Prepare response data (exclude sensitive information)
        $response_data = [
            'id' => $request->id,
            'description' => $request->description,
            'location_general' => $request->location_general,
            'location_intersection' => $request->location_intersection,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zipcode' => $request->zipcode,
            'status' => $request->status,
            'created_at' => $request->created_at,
            'anonymous' => $request->anonymous
        ];

        // Add contact info only if not anonymous
        if (!$request->anonymous) {
            $response_data['name'] = $request->name;
            $response_data['phone'] = $request->phone;
            $response_data['email_contact'] = $request->email_contact;
        }

        wp_send_json_success([
            'message' => 'Service request found successfully.',
            'request' => $response_data
        ]);
    }
}
