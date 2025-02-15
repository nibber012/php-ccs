<?php
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('applicant');

$user = $auth->getCurrentUser();

// Get applicant's submitted requirements
$database = Database::getInstance();;
$conn = $database->getConnection();

$query = "SELECT r.*, ar.status, ar.submitted_at, ar.notes 
          FROM requirements r
          LEFT JOIN applicant_requirements ar ON r.id = ar.requirement_id 
          AND ar.applicant_id = (SELECT id FROM applicants WHERE user_id = ?)
          WHERE r.active = 1
          ORDER BY r.order_num";
$stmt = $conn->prepare($query);
$stmt->execute([$user['id']]);
$requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

get_header('Requirements');
get_sidebar('applicant');
?>

<div class="content">
    <div class="container-fluid px-4 py-4" style="margin-top: 60px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Requirements Checklist</h1>
                <p class="text-muted">Track and submit your application requirements</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Requirements List -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 40%">Requirement</th>
                                        <th style="width: 20%">Status</th>
                                        <th style="width: 25%">Submitted</th>
                                        <th style="width: 15%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requirements as $req): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($req['name']); ?></strong>
                                                        <?php if ($req['description']): ?>
                                                            <small class="d-block text-muted">
                                                                <?php echo htmlspecialchars($req['description']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!$req['status']): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php elseif ($req['status'] === 'submitted'): ?>
                                                    <span class="badge bg-info">Under Review</span>
                                                <?php elseif ($req['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($req['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($req['submitted_at']): ?>
                                                    <?php echo date('M j, Y', strtotime($req['submitted_at'])); ?>
                                                    <?php if ($req['notes']): ?>
                                                        <i class="bi bi-info-circle ms-1" 
                                                           data-bs-toggle="tooltip" 
                                                           title="<?php echo htmlspecialchars($req['notes']); ?>"></i>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$req['status'] || $req['status'] === 'rejected'): ?>
                                                    <button class="btn btn-sm btn-primary" 
                                                            onclick="uploadRequirement(<?php echo $req['id']; ?>)">
                                                        <i class="bi bi-upload"></i>
                                                        Upload
                                                    </button>
                                                <?php elseif ($req['status'] === 'submitted' || $req['status'] === 'approved'): ?>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewRequirement(<?php echo $req['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                        View
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Guidelines -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Submission Guidelines</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-check-circle me-2 text-success"></i>
                                File format: PDF only
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle me-2 text-success"></i>
                                Maximum file size: 2MB
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle me-2 text-success"></i>
                                Clear and readable scans
                            </li>
                            <li>
                                <i class="bi bi-check-circle me-2 text-success"></i>
                                Complete all pages
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Need Help -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Need Help?</h5>
                        <p>Having trouble with your requirements? Contact us:</p>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-telephone me-2"></i>
                                (02) 8123-4567
                            </li>
                            <li>
                                <i class="bi bi-envelope me-2"></i>
                                ccs@earist.edu.ph
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Requirement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm">
                    <input type="hidden" id="requirementId" name="requirement_id">
                    <div class="mb-3">
                        <label for="file" class="form-label">Select File (PDF only, max 2MB)</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".pdf" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitRequirement()">Upload</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function uploadRequirement(id) {
    document.getElementById('requirementId').value = id;
    new bootstrap.Modal(document.getElementById('uploadModal')).show();
}

function submitRequirement() {
    // TODO: Implement file upload functionality
    alert('File upload functionality will be implemented here');
}

function viewRequirement(id) {
    // TODO: Implement view functionality
    alert('View functionality will be implemented here');
}
</script>

<?php get_footer(); ?>
