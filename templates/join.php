<?php
session_start();
include('../backend/db.php');

$token = isset($_GET['token']) ? $_GET['token'] : null;
$error = null;
$workspace = null;
$inviter = null;

if (!$token) {
    $error = 'Invalid invitation link';
} else {
    try {
        // Check if invite exists and is valid
        $query = "SELECT wi.*, w.name as workspace_name, w.description, 
                        u.username as inviter_name, u.email as inviter_email
                 FROM workspace_invites wi
                 JOIN workspaces w ON wi.workspace_id = w.id
                 JOIN users u ON wi.created_by = u.id
                 WHERE wi.token = ? 
                 AND (wi.expires_at IS NULL OR wi.expires_at > NOW())
                 AND wi.used_at IS NULL";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $invite = mysqli_fetch_assoc($result);

        if (!$invite) {
            $error = 'This invitation link is invalid or has expired';
        } else {
            $workspace = [
                'name' => $invite['workspace_name'],
                'description' => $invite['description']
            ];
            $inviter = [
                'name' => $invite['inviter_name'],
                'email' => $invite['inviter_email']
            ];

            // If user is logged in, automatically join the workspace
            if (isset($_SESSION['user_id'])) {
                // Check if user is already a member
                $checkQuery = "SELECT 1 FROM workspace_users 
                             WHERE workspace_id = ? AND user_id = ?";
                $stmt = mysqli_prepare($conn, $checkQuery);
                mysqli_stmt_bind_param($stmt, "ii", $invite['workspace_id'], $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                $checkResult = mysqli_stmt_get_result($stmt);

                if (!mysqli_fetch_assoc($checkResult)) {
                    // Add user to workspace
                    mysqli_begin_transaction($conn);
                    
                    $addUserQuery = "INSERT INTO workspace_users (workspace_id, user_id, role) 
                                   VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $addUserQuery);
                    mysqli_stmt_bind_param($stmt, "iis", 
                        $invite['workspace_id'], 
                        $_SESSION['user_id'],
                        $invite['role']
                    );
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to join workspace");
                    }

                    // Mark invite as used
                    $updateInviteQuery = "UPDATE workspace_invites 
                                        SET used_at = NOW(), used_by = ? 
                                        WHERE token = ?";
                    $stmt = mysqli_prepare($conn, $updateInviteQuery);
                    mysqli_stmt_bind_param($stmt, "is", $_SESSION['user_id'], $token);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to update invite status");
                    }

                    mysqli_commit($conn);
                    
                    // Redirect to workspace
                    header("Location: workspace.php?id=" . $invite['workspace_id']);
                    exit();
                } else {
                    $error = 'You are already a member of this workspace';
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Workspace</title>
    <?php include '../backend/link.php'; ?>
    <link rel="stylesheet" href="../public/css/signup.css">
    <style>
        .join-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: var(--color-neutrals-2);
        }

        .join-card {
            background: var(--color-neutrals-4);
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 480px;
            text-align: center;
        }

        .join-title {
            color: var(--color-neutrals-94);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .join-description {
            color: var(--color-neutrals-50);
            margin-bottom: 2rem;
        }

        .join-error {
            background: var(--color-sementic-red-10);
            color: var(--color-sementic-red);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .workspace-info {
            background: var(--color-neutrals-6);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .workspace-name {
            color: var(--color-neutrals-94);
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .workspace-description {
            color: var(--color-neutrals-50);
            font-size: 0.875rem;
        }

        .inviter-info {
            color: var(--color-neutrals-50);
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="join-container">
        <div class="join-card">
            <?php if ($error): ?>
                <div class="join-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <p>Please <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="text-primary">log in</a> or <a href="signup.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="text-primary">sign up</a> to join this workspace.</p>
                <?php endif; ?>
            <?php elseif ($workspace): ?>
                <h1 class="join-title">Join Workspace</h1>
                <div class="workspace-info">
                    <h2 class="workspace-name"><?php echo htmlspecialchars($workspace['name']); ?></h2>
                    <?php if ($workspace['description']): ?>
                        <p class="workspace-description"><?php echo htmlspecialchars($workspace['description']); ?></p>
                    <?php endif; ?>
                </div>
                <p class="inviter-info">
                    Invited by <?php echo htmlspecialchars($inviter['name']); ?> (<?php echo htmlspecialchars($inviter['email']); ?>)
                </p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="auth-actions">
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn--primary">Log in to join</a>
                        <p class="text-neutral-50 mt-4">
                            Don't have an account? 
                            <a href="signup.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="text-primary">Sign up</a>
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>