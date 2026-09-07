<?php
// session_start();
// // If user is NOT logged in, redirect to login
// if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
//     header("Location: ../login.php");
//     exit;
// }



session_start();

// Session timeout: 5 minutes (300 seconds) of inactivity
$timeout_duration = 300;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Last request was more than timeout_duration ago
    session_unset();
    session_destroy();
    // Show modal warning about session expiration and redirect on OK
    ?>
    
        <style>
            body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:0;background:#f4f4f4}
            .modal{position:fixed;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5)}
            .modal-content{background:#fff;padding:20px;border-radius:6px;max-width:400px;width:90%;box-shadow:0 2px 8px rgba(0,0,0,0.2);text-align:center}
            .btn{margin-top:15px;padding:8px 16px;border:none;background:#007bff;color:#fff;border-radius:4px;cursor:pointer}
        </style>
    
    
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-content">
                <h2>Session Expired</h2>
                <p>Your session has expired due to inactivity. Please log in again.</p>
                <button class="btn" id="okBtn">Login</button>
            </div>
        </div>
        <script>
            document.getElementById('okBtn').addEventListener('click', function(){
                window.location.href = '../login.php';
            });
            // Also allow Enter/Escape to close
            document.addEventListener('keydown', function(e){ if(e.key === 'Enter' || e.key === 'Escape'){ window.location.href = '../login.php'; } });
        </script>
    
    <?php
    exit;
}

// Update last activity time stamp
$_SESSION['last_activity'] = time();

// User must at least be logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// But if the user is logged in and waiting to choose a role, do NOT redirect
// (This prevents redirect loop when pending_roles exist)
if (isset($_SESSION['pending_roles']) && !isset($_SESSION['active_role'])) {
    // Stay on current page — no redirect
    return;
}





