<?php
if (!defined('ABSPATH')) exit;

class SR_Frontend {
    public function __construct() {
        add_shortcode('service_request_form', [$this, 'render_form']);
        add_shortcode('service_request_tracking', [$this, 'render_tracking']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('sr-form-css', SR_PLUGIN_URL . 'assets/css/sr-form.css');
        wp_enqueue_script('sr-form-js', SR_PLUGIN_URL . 'assets/js/sr-form.js', ['jquery'], null, true);
        wp_localize_script('sr-form-js', 'SR_Ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sr_nonce')
        ]);
    }

    public function render_form() {
        ob_start(); ?>
        
        <div id="sr-stepper-form" class="sr-stepper">
            <!-- Progress Steps -->
            <div class="sr-steps">
                <div class="sr-step active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-title">Description</span>
                </div>
                <div class="sr-step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-title">Location</span>
                </div>
                <div class="sr-step" data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-title">Contact</span>
                </div>
                <div class="sr-step" data-step="4">
                    <span class="step-number">4</span>
                    <span class="step-title">Security</span>
                </div>
                <div class="sr-step" data-step="5">
                    <span class="step-number">5</span>
                    <span class="step-title">Review</span>
                </div>
            </div>

            <form id="serviceRequestForm">
                <!-- Step 1: Description -->
                <div class="sr-step-content active" data-step="1">
                    <h3>Step 1: Service Request Description</h3>
                    <div class="sr-field">
                        <label for="description">Describe your service request <span class="required">*</span></label>
                        <textarea id="description" name="description" rows="5" placeholder="Please provide a detailed description of your service request..." required></textarea>
                        <div class="sr-error" id="description-error"></div>
                    </div>
                    <div class="sr-buttons">
                        <div></div>
                        <button type="button" class="btn-next">Next Step</button>
                    </div>
                </div>

                <!-- Step 2: Location -->
                <div class="sr-step-content" data-step="2">
                    <h3>Step 2: Location Information</h3>
                    <div class="sr-field">
                        <label for="location_general">General Location <span class="required">*</span></label>
                        <input type="text" id="location_general" name="location_general" placeholder="e.g., Near Main Street Park" required>
                        <div class="sr-error" id="location_general-error"></div>
                    </div>
                    <div class="sr-field">
                        <label for="location_intersection">Closest Intersection</label>
                        <input type="text" id="location_intersection" name="location_intersection" placeholder="e.g., Main St & Oak Ave">
                    </div>
                    <div class="sr-field">
                        <label for="address">Street Address</label>
                        <input type="text" id="address" name="address" placeholder="123 Main Street">
                    </div>
                    <div class="sr-field-row">
                        <div class="sr-field">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" placeholder="City">
                        </div>
                        <div class="sr-field">
                            <label for="state">State</label>
                            <select id="state" name="state">
                                <option value="">Select State</option>
                                <option value="AL">Alabama</option>
                                <option value="AK">Alaska</option>
                                <option value="AZ">Arizona</option>
                                <option value="AR">Arkansas</option>
                                <option value="CA">California</option>
                                <option value="CO">Colorado</option>
                                <option value="CT">Connecticut</option>
                                <option value="DE">Delaware</option>
                                <option value="FL">Florida</option>
                                <option value="GA">Georgia</option>
								  <option value="HI">Hawaii</option>
								<option value="ID">Idaho</option>
								<option value="IL">Illinois</option>
								<option value="IN">Indiana</option>
								<option value="IA">Iowa</option>
								<option value="KS">Kansas</option>
								<option value="KY">Kentucky</option>
								<option value="LA">Louisiana</option>
								<option value="ME">Maine</option>
								<option value="MD">Maryland</option>
								<option value="MA">Massachusetts</option>
								<option value="MI">Michigan</option>
								<option value="MN">Minnesota</option>
								<option value="MS">Mississippi</option>
								<option value="MO">Missouri</option>
								<option value="MT">Montana</option>
								<option value="NE">Nebraska</option>
								<option value="NV">Nevada</option>
								<option value="NH">New Hampshire</option>
								<option value="NJ">New Jersey</option>
								<option value="NM">New Mexico</option>
								<option value="NY">New York</option>
								<option value="NC">North Carolina</option>
								<option value="ND">North Dakota</option>
								<option value="OH">Ohio</option>
								<option value="OK">Oklahoma</option>
								<option value="OR">Oregon</option>
								<option value="PA">Pennsylvania</option>
								<option value="RI">Rhode Island</option>
								<option value="SC">South Carolina</option>
								<option value="TN">Tennessee</option>
								<option value="TX">Texas</option>
								<option value="UT">Utah</option>
								<option value="VT">Vermont</option>
								<option value="VA">Virginia</option>
								<option value="WA">Washington</option>
								<option value="WV">West Virginia</option>
								<option value="WI">Wisconsin</option>
								<option value="WY">Wyoming</option>
								
                                <!-- Add more states as needed -->
                            </select>
                        </div>
                        <div class="sr-field">
                            <label for="zipcode">ZIP Code</label>
                            <input type="text" id="zipcode" name="zipcode" placeholder="12345" pattern="[0-9]{5}(-[0-9]{4})?">
                        </div>
                    </div>
                    <div class="sr-buttons">
                        <button type="button" class="btn-prev">Previous</button>
                        <button type="button" class="btn-next">Next Step</button>
                    </div>
                </div>

                <!-- Step 3: Contact -->
                <div class="sr-step-content" data-step="3">
                    <h3>Step 3: Contact Information</h3>
                    <div class="sr-field">
                        <label>
                            <input type="checkbox" id="anonymous" name="anonymous"> 
                            I prefer to remain anonymous
                        </label>
                        <div class="sr-note">Check this if you do not want to provide contact information</div>
                    </div>
                    <div id="contact-fields">
                        <div class="sr-field">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="Your full name">
                            <div class="sr-error" id="name-error"></div>
                        </div>
                        <div class="sr-field">
                            <label for="street">Street Address</label>
                            <input type="text" id="street" name="street" placeholder="Your street address">
                        </div>
                        <div class="sr-field-row">
                            <div class="sr-field">
                                <label for="city_contact">City</label>
                                <input type="text" id="city_contact" name="city_contact" placeholder="Your city">
                            </div>
                            <div class="sr-field">
                                <label for="state_contact">State</label>
                                <input type="text" id="state_contact" name="state_contact" placeholder="Your state">
                            </div>
                            <div class="sr-field">
                                <label for="zip_contact">ZIP Code</label>
                                <input type="text" id="zip_contact" name="zip_contact" placeholder="Your ZIP code">
                            </div>
                        </div>
                        <div class="sr-field-row">
                            <div class="sr-field">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="(555) 123-4567">
                            </div>
                            <div class="sr-field">
                                <label for="email_contact">Email Address</label>
                                <input type="email" id="email_contact" name="email_contact" placeholder="your.email@example.com">
                                <div class="sr-error" id="email_contact-error"></div>
                            </div>
                        </div>
                    </div>
                    <div class="sr-buttons">
                        <button type="button" class="btn-prev">Previous</button>
                        <button type="button" class="btn-next">Next Step</button>
                    </div>
                </div>

                <!-- Step 4: Security PIN -->
                <div class="sr-step-content" data-step="4">
                    <h3>Step 4: Set Security PIN</h3>
                    <div class="sr-field">
                        <label for="pin">Create a 4-6 digit PIN <span class="required">*</span></label>
                        <input type="password" id="pin" name="pin" maxlength="6" minlength="4" placeholder="Enter 4-6 digits" >
                        <div class="sr-note">You will need this PIN to check the status of your request</div>
                        <div class="sr-error" id="pin-error"></div>
                    </div>
                    <div class="sr-field">
                        <label for="pin_confirm">Confirm PIN <span class="required">*</span></label>
                        <input type="password" id="pin_confirm" name="pin_confirm" maxlength="6" minlength="4" placeholder="Re-enter your PIN" >
                        <div class="sr-error" id="pin_confirm-error"></div>
                    </div>
                    <div class="sr-buttons">
                        <button type="button" class="btn-prev">Previous</button>
                        <button type="button" id="saveData">Save & Continue</button>
                    </div>
                </div>

                <!-- Step 5: Review -->
                <div class="sr-step-content" data-step="5">
                    <h3>Step 5: Review & Submit</h3>
                    <div id="review-data" class="review-section"></div>
                    <!-- Confirmation email removed: notifications will be sent automatically to the contact email (email_contact) when admin replies or status changes -->
                    <div class="sr-buttons">
                        <button type="button" class="btn-prev">Previous</button>
                        <button type="submit" class="btn-submit">Submit Request</button>
                    </div>
                </div>
            </form>
            
            <div id="sr-confirmation" style="display: none;"></div>
        </div>

        <?php return ob_get_clean();
    }

    public function render_tracking() {
        $tracking_result = null;
        $tracking_error = null;
        
        // Process form submission
        if (isset($_POST['track_request']) && wp_verify_nonce($_POST['tracking_nonce'], 'sr_tracking_nonce')) {
            $confirmation_number = intval($_POST['confirmation_number'] ?? 0);
            $pin = preg_replace('/[^0-9]/', '', $_POST['tracking_pin'] ?? '');
            
            if (empty($confirmation_number) || empty($pin)) {
                $tracking_error = 'Please provide both confirmation number and PIN.';
            } elseif (!preg_match('/^\d{4,6}$/', $pin)) {
                $tracking_error = 'PIN must be 4-6 digits.';
            } else {
                // Get request from database
                $request = SR_DB::get_request($confirmation_number);
                
                if (!$request) {
                    $tracking_error = 'No service request found with this confirmation number.';
                } elseif (!password_verify($pin, $request->pin_hash)) {
                    $tracking_error = 'Invalid PIN. Please check your PIN and try again.';
                } else {
                    $tracking_result = $request;
                }
            }
        }
        
        ob_start(); ?>
        
        <div id="sr-tracking-form" class="sr-tracking">
            

            <?php if ($tracking_error): ?>
                <div class="sr-tracking-error">
                    <p><?php echo esc_html($tracking_error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($tracking_result): ?>
                <div class="sr-tracking-result">
                    <?php $this->display_tracking_result($tracking_result); ?>
                </div>
            <?php else: ?>
                <div class="sr-tracking-header">
                    <h3>Track Your Service Request</h3>
                    <p>Enter your confirmation number and PIN to check the status of your service request.</p>
                </div>
                <form method="post" class="sr-tracking-form">
                    <?php wp_nonce_field('sr_tracking_nonce', 'tracking_nonce'); ?>
                    <div class="sr-field-row">
                        <div class="sr-field">
                            <label for="confirmation_number">Confirmation Number <span class="required">*</span></label>
                            <input type="number" id="confirmation_number" name="confirmation_number" 
                                   value="<?php echo isset($_POST['confirmation_number']) ? esc_attr($_POST['confirmation_number']) : ''; ?>" 
                                   placeholder="Enter confirmation number" required>
                        </div>
                        <div class="sr-field">
                            <label for="tracking_pin">PIN <span class="required">*</span></label>
                            <input type="password" id="tracking_pin" name="tracking_pin" maxlength="6" placeholder="Enter your PIN" required>
                        </div>
                    </div>
                    <div class="sr-buttons">
                        <button type="submit" name="track_request" class="btn-track">Track Request</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($tracking_result): ?>
                <div class="sr-buttons" style="margin-top: 20px;">
                    <a href="<?php echo esc_url(remove_query_arg(array('confirmation_number', 'tracking_pin'))); ?>" class="btn-track">Track Another Request</a>
                </div>
            <?php endif; ?>
        </div>

        <?php return ob_get_clean();
    }

    private function display_tracking_result($request) {
        $status_class = $this->get_status_class($request->status);
        $formatted_date = $this->format_date($request->created_at);
        ?>
        <div class="tracking-result-content">
            <div class="result-header">
                <h3>Service Request #<?php echo $request->id; ?></h3>
                <div class="status-badge d-none1 <?php echo $status_class; ?>"><?php echo esc_html($request->status); ?></div>
            </div>
            
            <div class="result-section">
                <h4>Request Details</h4>
                <p><strong>Submitted:</strong> <?php echo $formatted_date; ?></p>
                <p><strong>Description:</strong> <?php echo esc_html($request->description); ?></p>
            </div>
            
            <?php if ($request->location_general || $request->address || $request->city): ?>
                <div class="result-section">
                    <h4>Location Information</h4>
                    <?php if ($request->location_general): ?>
                        <p><strong>General Location:</strong> <?php echo esc_html($request->location_general); ?></p>
                    <?php endif; ?>
                    <?php if ($request->location_intersection): ?>
                        <p><strong>Nearest Intersection:</strong> <?php echo esc_html($request->location_intersection); ?></p>
                    <?php endif; ?>
                    <?php if ($request->address): ?>
                        <p><strong>Address:</strong> <?php echo esc_html($request->address); ?></p>
                    <?php endif; ?>
                    <?php if ($request->city || $request->state || $request->zipcode): ?>
                        <p><strong>City/State/ZIP:</strong> 
                        <?php 
                        $location_parts = array_filter([$request->city, $request->state, $request->zipcode]);
                        echo esc_html(implode(', ', $location_parts)); 
                        ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$request->anonymous && ($request->name || $request->phone || $request->email_contact)): ?>
                <div class="result-section">
                    <h4>Contact Information</h4>
                    <?php if ($request->name): ?>
                        <p><strong>Name:</strong> <?php echo esc_html($request->name); ?></p>
                    <?php endif; ?>
                    <?php if ($request->phone): ?>
                        <p><strong>Phone:</strong> <?php echo esc_html($request->phone); ?></p>
                    <?php endif; ?>
                    <?php if ($request->email_contact): ?>
                        <p><strong>Email:</strong> <?php echo esc_html($request->email_contact); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($request->reply_text)): ?>
                <div class="result-section">
                    <h4>Response from Administration</h4>
                    <div class="sr-admin-reply">
                        <?php echo nl2br(esc_html($request->reply_text)); ?>
                        <?php if (!empty($request->replied_at)): ?>
                            <div class="sr-reply-meta"><small>Replied on <?php echo esc_html(date('M j, Y \a\t g:i A', strtotime($request->replied_at))); ?><?php if (!empty($request->replied_by)) echo ' by ' . esc_html($request->replied_by); ?></small></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="result-section d-none">
                <h4>Status Information</h4>
                <p>Current status: <strong class="<?php echo $status_class; ?>"><?php echo esc_html($request->status); ?></strong></p>
                <div class="status-timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Request Submitted</h5>
                            <p><?php echo $formatted_date; ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo ($request->status !== 'Pending') ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Under Review</h5>
                            <p><?php echo ($request->status !== 'Pending') ? 'In progress' : 'Waiting to be reviewed'; ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo ($request->status === 'Completed') ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Completed</h5>
                            <p><?php echo ($request->status === 'Completed') ? 'Request has been completed' : 'Pending completion'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_status_class($status) {
        switch (strtolower($status)) {
            case 'pending': return 'status-pending';
            case 'in progress': case 'in-progress': return 'status-progress';
            case 'completed': return 'status-completed';
            case 'cancelled': case 'canceled': case 'rejected': return 'status-cancelled';
            default: return 'status-pending';
        }
    }

    private function format_date($dateString) {
        $date = new DateTime($dateString);
        return $date->format('M j, Y \a\t g:i A');
    }
}
