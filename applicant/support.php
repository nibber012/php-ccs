<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../includes/layout.php';
require_once '../includes/utilities.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();;

// Initialize variables
$faqs = [];
$contact_info = [];
$success_message = '';
$error_message = '';

try {
    // Get FAQs
    $stmt = $db->query(
        "SELECT * FROM faqs WHERE status = 'active' AND (target_role = 'applicant' OR target_role = 'all') ORDER BY priority DESC",
        []
    );
    $faqs = $stmt->fetchAll();

    // Get contact information
    $stmt = $db->query(
        "SELECT * FROM system_settings WHERE setting_key IN ('support_email', 'support_phone', 'office_address')",
        []
    );
    while ($row = $stmt->fetch()) {
        $contact_info[$row['setting_key']] = $row['setting_value'];
    }

    // Handle support ticket submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if (empty($subject) || empty($message) || empty($category)) {
            $error_message = "Please fill in all required fields.";
        } else {
            // Insert support ticket
            $db->query(
                "INSERT INTO support_tickets (user_id, subject, message, category, status, created_at) 
                 VALUES (?, ?, ?, ?, 'open', NOW())",
                [$user_id, $subject, $message, $category]
            );
            $success_message = "Your support ticket has been submitted successfully. We will get back to you soon.";
        }
    }

} catch (Exception $e) {
    error_log("Support Page Error: " . $e->getMessage());
    $error_message = "An error occurred. Please try again later.";
}

get_header('Help & Support');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php get_sidebar('applicant'); ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Help & Support</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo safe_string($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo safe_string($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Contact Information -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Contact Information</h5>
                            <div class="contact-info">
                                <?php if (!empty($contact_info['support_email'])): ?>
                                    <div class="mb-3">
                                        <i class="bx bx-envelope"></i>
                                        <strong>Email:</strong><br>
                                        <a href="mailto:<?php echo safe_string($contact_info['support_email']); ?>">
                                            <?php echo safe_string($contact_info['support_email']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($contact_info['support_phone'])): ?>
                                    <div class="mb-3">
                                        <i class="bx bx-phone"></i>
                                        <strong>Phone:</strong><br>
                                        <a href="tel:<?php echo safe_string($contact_info['support_phone']); ?>">
                                            <?php echo safe_string($contact_info['support_phone']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($contact_info['office_address'])): ?>
                                    <div class="mb-3">
                                        <i class="bx bx-map"></i>
                                        <strong>Address:</strong><br>
                                        <?php echo nl2br(safe_string($contact_info['office_address'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="office-hours mt-4">
                                <h6>Office Hours</h6>
                                <p class="mb-1">Monday - Friday: 8:00 AM - 5:00 PM</p>
                                <p>Saturday - Sunday: Closed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Ticket Form -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Submit a Support Ticket</h5>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select a category</option>
                                        <option value="technical">Technical Issue</option>
                                        <option value="exam">Exam Related</option>
                                        <option value="account">Account Related</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                </div>

                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                </div>

                                <button type="submit" name="submit_ticket" class="btn btn-primary">Submit Ticket</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQs -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Frequently Asked Questions</h5>
                            <div class="accordion" id="faqAccordion">
                                <?php foreach ($faqs as $index => $faq): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq-heading-<?php echo $index; ?>">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#faq-collapse-<?php echo $index; ?>" 
                                                    aria-expanded="false" 
                                                    aria-controls="faq-collapse-<?php echo $index; ?>">
                                                <?php echo safe_string($faq['question']); ?>
                                            </button>
                                        </h2>
                                        <div id="faq-collapse-<?php echo $index; ?>" 
                                             class="accordion-collapse collapse" 
                                             aria-labelledby="faq-heading-<?php echo $index; ?>" 
                                             data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                <?php echo nl2br(safe_string($faq['answer'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (empty($faqs)): ?>
                                    <p class="text-muted">No FAQs available at the moment.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.contact-info i {
    font-size: 1.25rem;
    margin-right: 0.5rem;
    color: #3498db;
}

.contact-info a {
    color: #2c3e50;
    text-decoration: none;
}

.contact-info a:hover {
    color: #3498db;
}

.accordion-button:not(.collapsed) {
    background-color: #edf2f7;
    color: #2c3e50;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}

.office-hours {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.office-hours h6 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.office-hours p {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0;
}
</style>

<?php get_footer(); ?>
