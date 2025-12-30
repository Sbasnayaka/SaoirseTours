<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$stmt = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC");
$inquiries = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Inquiries & Bookings</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inq): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($inq['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($inq['name']); ?></td>
                                <td><a
                                        href="mailto:<?php echo htmlspecialchars($inq['email']); ?>"><?php echo htmlspecialchars($inq['email']); ?></a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $inq['type'] == 'booking' ? 'success' : 'primary'; ?>">
                                        <?php echo ucfirst($inq['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars(substr($inq['message'], 0, 100))); ?>...</td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm text-white"
                                        onclick="showInquiry(<?php echo htmlspecialchars(json_encode($inq)); ?>)">
                                        View
                                    </button>
                                    <a href="delete_inquiry.php?id=<?php echo $inq['id']; ?>" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this inquiry?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Inquiry Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Date:</strong> <span id="viewDate"></span></p>
                <p><strong>Name:</strong> <span id="viewName"></span></p>
                <p><strong>Email:</strong> <span id="viewEmail"></span></p>
                <p><strong>Type:</strong> <span id="viewType" class="badge bg-primary"></span></p>
                <hr>
                <h6>Message / Booking Details:</h6>
                <div id="viewMessage" class="bg-light p-3 rounded" style="white-space: pre-wrap;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showInquiry(data) {
        document.getElementById('viewDate').innerText = data.created_at;
        document.getElementById('viewName').innerText = data.name;
        document.getElementById('viewEmail').innerText = data.email;
        document.getElementById('viewType').innerText = data.type;
        document.getElementById('viewMessage').innerText = data.message;

        var myModal = new bootstrap.Modal(document.getElementById('viewModal'));
        myModal.show();
    }
</script>

<?php include 'includes/footer.php'; ?>