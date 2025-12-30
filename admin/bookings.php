<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

// Fetch ONLY Bookings
$stmt = $pdo->query("SELECT * FROM inquiries WHERE type = 'booking' ORDER BY created_at DESC");
$bookings = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Tour Bookings Management</h1>
        <a href="inquiries.php" class="btn btn-outline-secondary btn-sm">View All Inquiries</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">Recent Tour Bookings</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th>Ref #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Package Interest</th> <!-- Parsed from message -->
                            <th>Arrival</th> <!-- Parsed -->
                            <th>Status Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No bookings found yet.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($bookings as $b):
                            // PARSE THE MESSAGE for Details
                            // We look for patterns like "Package: ...", "Arrival: ..."
                            $msg = $b['message'];
                            $package = preg_match('/Package: (.*?)(\n|$)/', $msg, $m) ? trim($m[1]) : '-';
                            $arrival = preg_match('/Arrival: (.*?)(\n|$)/', $msg, $m) ? trim($m[1]) : '-';
                            $adults = preg_match('/Adults: (.*?)(\n|$)/', $msg, $m) ? trim($m[1]) : '0';
                            $kids = preg_match('/Children: (.*?)(\n|$)/', $msg, $m) ? trim($m[1]) : '0';
                            $country = preg_match('/Country: (.*?)(\n|$)/', $msg, $m) ? trim($m[1]) : '-';
                            ?>
                            <tr>
                                <td>#<?php echo $b['id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($b['created_at'])); ?> <small
                                        class="text-muted"><?php echo date('H:i', strtotime($b['created_at'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($b['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($b['email']); ?></small><br>
                                    <?php if ($country !== '-'): ?><span
                                            class="badge bg-info text-dark"><?php echo htmlspecialchars($country); ?></span><?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary"><?php echo htmlspecialchars($package); ?></span>
                                </td>
                                <td>
                                    <?php if ($arrival !== '-'): ?>
                                        <i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars($arrival); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <i class="bi bi-people"></i> <strong><?php echo $adults; ?></strong> Adults,
                                        <strong><?php echo $kids; ?></strong> Kids
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary"
                                        onclick="showBooking(<?php echo htmlspecialchars(json_encode($b)); ?>)">
                                        <i class="bi bi-eye"></i> View Full
                                    </button>
                                    <a href="delete_inquiry.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Refuse/Delete this booking?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Booking Details <span id="modalRef" class="badge bg-secondary ms-2"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h6 class="text-uppercase text-muted small">Customer Info</h6>
                        <p class="mb-1"><strong>Name:</strong> <span id="modalName"></span></p>
                        <p class="mb-1"><strong>Email:</strong> <span id="modalEmail"></span></p>
                        <p class="mb-1"><strong>Received:</strong> <span id="modalDate"></span></p>
                    </div>
                    <div class="col-md-6 ps-4">
                        <h6 class="text-uppercase text-muted small">Trip Details</h6>
                        <div id="parsedDetails"></div>
                    </div>
                </div>
                <hr>
                <h6 class="text-uppercase text-muted small">Full Message / Requests</h6>
                <div id="modalMessage" class="bg-light p-3 rounded"
                    style="white-space: pre-wrap; font-family: monospace;"></div>
            </div>
            <div class="modal-footer">
                <a href="#" id="replyBtn" class="btn btn-success"><i class="bi bi-reply"></i> Reply via Email</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showBooking(data) {
        document.getElementById('modalRef').innerText = '#' + data.id;
        document.getElementById('modalName').innerText = data.name;
        document.getElementById('modalEmail').innerText = data.email;
        document.getElementById('modalDate').innerText = data.created_at;
        document.getElementById('modalMessage').innerText = data.message;
        document.getElementById('replyBtn').href = "mailto:" + data.email + "?subject=Re: Booking Inquiry #" + data.id;

        // Smart Parse for the Modal Right Side
        let msg = data.message;
        let html = '';

        // Simple regex parser for JS side as well
        const patterns = {
            'Package': /Package: (.*?)(\n|$)/,
            'Country': /Country: (.*?)(\n|$)/,
            'Phone': /Phone: (.*?)(\n|$)/,
            'Arrival': /Arrival: (.*?)(\n|$)/,
            'Departure': /Departure: (.*?)(\n|$)/,
            'Adults': /Adults: (.*?)(\n|$)/,
            'Children': /Children: (.*?)(\n|$)/
        };

        for (let key in patterns) {
            let match = msg.match(patterns[key]);
            if (match) {
                html += `<p class="mb-1"><strong>${key}:</strong> ${match[1]}</p>`;
            }
        }
        document.getElementById('parsedDetails').innerHTML = html;

        new bootstrap.Modal(document.getElementById('bookingModal')).show();
    }
</script>

<?php include 'includes/footer.php'; ?>