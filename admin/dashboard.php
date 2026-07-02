<?php
include("../includes/auth.php");
include("../includes/db.php");

session_start();

/*
────────────────────────────
SECURITY: ADMIN CHECK
────────────────────────────
*/
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/*
────────────────────────────
SECURE COUNTS (prepared statements)
────────────────────────────
*/

// Notifications
$stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM notifications
    WHERE is_read = 0 AND user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifCount = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

// Cart
$stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM cart
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cartCount = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

// Chat messages
$stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM messages
    WHERE receiver_id = ? AND is_read = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$chatCount = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
?>

<?php include("../includes/sidebar.php"); ?>

<div style="margin-left:240px; padding:20px;">

<!-- TOP BAR -->
<div style="
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#1e1e2f;
    color:white;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
">

    <h2 style="margin:0;">Admin Dashboard</h2>

    <div style="display:flex; gap:25px; align-items:center;">

        <!-- CHAT -->
        <div style="position:relative; cursor:pointer;">
            💬
            <span id="chatCount" style="
                background:blue;
                color:white;
                border-radius:50%;
                padding:3px 7px;
                font-size:12px;
                position:absolute;
                top:-8px;
                right:-10px;">
                <?= (int)$chatCount ?>
            </span>
        </div>

        <!-- NOTIFICATIONS -->
        <div style="position:relative; cursor:pointer;" onclick="toggleNotif()">
            🔔
            <span id="notifCount" style="
                background:red;
                color:white;
                border-radius:50%;
                padding:3px 7px;
                font-size:12px;
                position:absolute;
                top:-8px;
                right:-10px;">
                <?= (int)$notifCount ?>
            </span>

            <div id="notifBox" style="
                display:none;
                position:absolute;
                right:0;
                top:40px;
                width:300px;
                background:white;
                color:black;
                border-radius:6px;
                box-shadow:0 4px 10px rgba(0,0,0,0.2);
                z-index:999;
            ">
                <div style="padding:10px; background:#1e1e2f; color:white;">
                    Notifications
                </div>
                <div id="notifList"></div>
            </div>
        </div>

        <!-- CART -->
        <div style="position:relative; cursor:pointer;">
            🛒
            <span id="cartCount" style="
                background:green;
                color:white;
                border-radius:50%;
                padding:3px 7px;
                font-size:12px;
                position:absolute;
                top:-8px;
                right:-10px;">
                <?= (int)$cartCount ?>
            </span>
        </div>

    </div>
</div>

<!-- CONTENT -->
<div class="card">
    <h3>Welcome, Admin</h3>
    <p>System overview dashboard active.</p>

    <ul>
        <li>User Management</li>
        <li>Orders Monitoring</li>
        <li>Withdrawals Control</li>
        <li>Fraud Detection</li>
        <li>Audit Logs</li>
    </ul>
</div>

</div>

<!-- SOCKET.IO -->
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>

<script>
const userId = <?= (int)$user_id ?>;
const socket = io("http://localhost:3000");

socket.emit("join", userId);

/* NOTIFICATIONS */
socket.on("new_notification", function(data) {

    let count = document.getElementById("notifCount");
    count.innerText = parseInt(count.innerText) + 1;

    let list = document.getElementById("notifList");

    let item = document.createElement("div");
    item.style.padding = "10px";
    item.style.borderBottom = "1px solid #eee";
    item.innerHTML = "<b>" + data.type + "</b><br>" + data.message;

    list.prepend(item);

    document.getElementById("notifBox").style.display = "block";
});

/* CHAT */
socket.on("new_message", function() {
    let c = document.getElementById("chatCount");
    c.innerText = parseInt(c.innerText) + 1;
});

/* CART */
socket.on("cart_changed", function(data) {
    document.getElementById("cartCount").innerText = data.count;
});

/* TOGGLE */
function toggleNotif() {
    let box = document.getElementById("notifBox");
    box.style.display = (box.style.display === "block") ? "none" : "block";
}
</script>