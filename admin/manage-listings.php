<?php
include 'db.php';
session_start();

/* =========================
   SECURITY CHECK (basic)
========================= */
if (!isset($_SESSION['admin'])) {
    header("Location: admin-login.php");
    exit();
}

/* =========================
   ACTION HANDLER
========================= */
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === "approve") {
        $sql = "UPDATE listings SET status='approved' WHERE id=$id";
        mysqli_query($conn, $sql);
    }

    if ($action === "reject") {
        $sql = "UPDATE listings SET status='rejected' WHERE id=$id";
        mysqli_query($conn, $sql);
    }

    if ($action === "delete") {
        $sql = "DELETE FROM listings WHERE id=$id";
        mysqli_query($conn, $sql);
    }

    header("Location: admin-listings.php");
    exit();
}

/* =========================
   FILTER LOGIC
========================= */
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

if ($status == "all") {
    $query = "SELECT * FROM listings ORDER BY id DESC";
} else {
    $query = "SELECT * FROM listings WHERE status='$status' ORDER BY id DESC";
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Listings</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<!-- =========================
     SIDEBAR
========================= -->
<div class="sidebar">
    <a href="admin-dashboard.php">Dashboard</a>
    <a href="admin-listings.php?status=pending">Pending Listings</a>
    <a href="admin-listings.php?status=approved">Approved Listings</a>
    <a href="admin-listings.php?status=rejected">Rejected Listings</a>
    <a href="admin-listings.php?status=all">All Listings</a>
</div>

<!-- =========================
     MAIN CONTENT
========================= -->
<div class="main">

    <h2>Listings Management</h2>

    <div class="filter-info">
        <p>Viewing: <strong><?php echo ucfirst($status); ?></strong></p>
    </div>

    <table border="1" width="100%">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Owner</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['title']; ?></td>
            <td><?php echo $row['owner']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td>

                <?php if ($row['status'] == 'pending') { ?>
                    <a href="?action=approve&id=<?php echo $row['id']; ?>">Approve</a> |
                    <a href="?action=reject&id=<?php echo $row['id']; ?>">Reject</a> |
                <?php } ?>

                <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                   onclick="return confirm('Delete this listing?')">
                   Delete
                </a>

            </td>
        </tr>
        <?php } ?>

    </table>

</div>

</body>
</html>