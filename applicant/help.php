<?php
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('applicant');

get_header('Help & Support');
get_sidebar('applicant');
?>

<div class="content">
    <div class="container-fluid px-4 py-4" style="margin-top: 60px;">
        <h1 class="h3 mb-4">Help & Support</h1>
        
        <div class="row">
            <div class="col-md-8">
                <!-- FAQs -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Frequently Asked Questions</h5>
                        
                        <div class="accordion" id="faqAccordion">
                            <!-- Application Process -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        What are the steps in the application process?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Online Registration</li>
                                            <li>Document Submission</li>
                                            <li>Entrance Examination (Parts 1 & 2)</li>
                                            <li>Interview</li>
                                            <li>Results & Enrollment</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Exam -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        What should I prepare for the entrance exam?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>The entrance exam covers:</p>
                                        <ul>
                                            <li>Mathematics (Algebra, Basic Calculus)</li>
                                            <li>Logic and Problem Solving</li>
                                            <li>Basic Programming Concepts</li>
                                            <li>English Proficiency</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Technical Issues -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        What should I do if I encounter technical issues?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>If you encounter technical issues:</p>
                                        <ol>
                                            <li>Take a screenshot of the error</li>
                                            <li>Note down what you were trying to do</li>
                                            <li>Contact technical support via email or phone</li>
                                            <li>Include your application number in all communications</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Troubleshooting -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Common Issues & Solutions</h5>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Issue</th>
                                        <th>Solution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Can't log in</td>
                                        <td>
                                            <ul class="mb-0">
                                                <li>Check if caps lock is on</li>
                                                <li>Clear browser cache</li>
                                                <li>Reset password if needed</li>
                                            </ul>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Exam not loading</td>
                                        <td>
                                            <ul class="mb-0">
                                                <li>Check internet connection</li>
                                                <li>Use Chrome or Firefox browser</li>
                                                <li>Disable browser extensions</li>
                                            </ul>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>File upload issues</td>
                                        <td>
                                            <ul class="mb-0">
                                                <li>Ensure file is under 2MB</li>
                                                <li>Use PDF format</li>
                                                <li>Check file name (no special characters)</li>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Contact Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Contact Us</h5>
                        
                        <div class="mb-3">
                            <h6 class="mb-2">CCS Office</h6>
                            <p class="mb-1"><i class="bi bi-telephone me-2"></i>(02) 8123-4567</p>
                            <p class="mb-1"><i class="bi bi-envelope me-2"></i>ccs@earist.edu.ph</p>
                            <p class="mb-0"><i class="bi bi-clock me-2"></i>Mon-Fri, 8:00 AM - 5:00 PM</p>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="mb-2">Technical Support</h6>
                            <p class="mb-1"><i class="bi bi-telephone me-2"></i>(02) 8123-4568</p>
                            <p class="mb-0"><i class="bi bi-envelope me-2"></i>support@earist.edu.ph</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Links</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-file-pdf me-2"></i>
                                <a href="#" class="text-decoration-none">Application Guide</a>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-journal-text me-2"></i>
                                <a href="#" class="text-decoration-none">Program Requirements</a>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-calendar-event me-2"></i>
                                <a href="#" class="text-decoration-none">Academic Calendar</a>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-building me-2"></i>
                                <a href="#" class="text-decoration-none">Campus Map</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
