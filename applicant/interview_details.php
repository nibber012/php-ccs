<?php
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('applicant');

get_header('Interview Details');
get_sidebar('applicant');
?>

<div class="content">
    <div class="container-fluid px-4 py-4" style="margin-top: 60px;">
        <h1 class="h3 mb-4">Interview Guidelines</h1>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">What to Expect</h5>
                        <p>The interview is a crucial part of your application to the College of Computer Studies. Here's what you need to know:</p>
                        
                        <h6 class="mt-4">Interview Format</h6>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-clock me-2"></i> Duration: 15-20 minutes</li>
                            <li><i class="bi bi-people me-2"></i> Panel: 2-3 CCS faculty members</li>
                            <li><i class="bi bi-translate me-2"></i> Language: English and Filipino</li>
                        </ul>
                        
                        <h6 class="mt-4">Common Topics</h6>
                        <ul>
                            <li>Academic background and achievements</li>
                            <li>Interest in computer science/IT</li>
                            <li>Programming experience (if any)</li>
                            <li>Career goals and aspirations</li>
                            <li>Problem-solving abilities</li>
                            <li>Extracurricular activities</li>
                        </ul>
                        
                        <h6 class="mt-4">Tips for Success</h6>
                        <ol>
                            <li>Arrive 15 minutes early</li>
                            <li>Dress professionally (school uniform or business casual)</li>
                            <li>Bring a valid ID and your application documents</li>
                            <li>Prepare questions about the program</li>
                            <li>Be honest and authentic in your responses</li>
                        </ol>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Required Documents</h5>
                        <p>Please bring the following documents to your interview:</p>
                        
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Valid School ID or Government ID
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Original High School Report Card
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Certificate of Good Moral Character
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Printed Application Form
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Location</h5>
                        <p><i class="bi bi-geo-alt me-2"></i>College of Computer Studies</p>
                        <p><i class="bi bi-building me-2"></i>3rd Floor, CCS Building</p>
                        <p><i class="bi bi-signpost me-2"></i>EARIST Main Campus</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Need Help?</h5>
                        <p>If you have questions or need to reschedule:</p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-telephone me-2"></i>(02) 8123-4567</li>
                            <li><i class="bi bi-envelope me-2"></i>ccs@earist.edu.ph</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
