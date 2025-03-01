<?php
session_start();
require_once('../backend/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Form/login.php');
    exit;
}

// Get form ID from URL
$formId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$formId) {
    die('Form ID is required');
}

// Get form details and check workspace access
$formSql = "SELECT f.*, w.id as workspace_id, w.name as workspace_name 
            FROM forms f 
            JOIN workspaces w ON f.workspace_id = w.id 
            WHERE f.id = ?";
$stmt = mysqli_prepare($conn, $formSql);
mysqli_stmt_bind_param($stmt, "i", $formId);
mysqli_stmt_execute($stmt);
$formResult = mysqli_stmt_get_result($stmt);
$form = mysqli_fetch_assoc($formResult);

if (!$form) {
    die('Form not found');
}

// Check if user has access to the workspace
$workspaceAccessSql = "SELECT * FROM workspace_users 
                       WHERE workspace_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $workspaceAccessSql);
mysqli_stmt_bind_param($stmt, "ii", $form['workspace_id'], $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$accessResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($accessResult) === 0) {
    die('Access denied');
}

// Get form elements
$elementsSql = "SELECT * FROM form_elements WHERE form_id = ? ORDER BY position";
$stmt = mysqli_prepare($conn, $elementsSql);
mysqli_stmt_bind_param($stmt, "i", $formId);
mysqli_stmt_execute($stmt);
$elementsResult = mysqli_stmt_get_result($stmt);
$elements = mysqli_fetch_all($elementsResult, MYSQLI_ASSOC);

// Get form responses
$responsesSql = "SELECT r.*, u.username 
                 FROM form_responses r
                 LEFT JOIN users u ON r.user_id = u.id
                 WHERE r.form_id = ?
                 ORDER BY r.created_at DESC";
$stmt = mysqli_prepare($conn, $responsesSql);
mysqli_stmt_bind_param($stmt, "i", $formId);
mysqli_stmt_execute($stmt);
$responsesResult = mysqli_stmt_get_result($stmt);
$responses = [];
while ($row = mysqli_fetch_assoc($responsesResult)) {
    $responseId = $row['id'];
    if (!isset($responses[$responseId])) {
        $responses[$responseId] = [
            'id' => $responseId,
            'created_at' => $row['created_at'],
            'username' => $row['username'] ?: 'Anonymous',
            'values' => []
        ];
    }
    $responses[$responseId]['values'][$row['element_id']] = $row['response_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responses - <?php echo htmlspecialchars($form['title']); ?></title>
    <link rel="stylesheet" href="../public/css/theme.css">
    <link rel="stylesheet" href="../public/css/utilities.css">
    <style>
        body {
            background-color: var(--color-neutrals-94);
            margin: 0;
            padding: 20px;
            font-family: 'Inter', sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header {
            background-color: var(--color-primary-94);
            border: 1px solid var(--color-neutrals-16);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {
            flex: 1;
        }

        .header-right {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .search-container {
            position: relative;
            margin-bottom: 24px;
        }

        .search-input {
            width: 300px;
            padding: 8px 12px;
            padding-left: 36px;
            font-size: 14px;
            border: 1px solid var(--color-neutrals-16);
            border-radius: 4px;
            background-color: #fff;
            color: var(--color-neutrals-2);
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--color-primary-52);
            box-shadow: 0 0 0 2px var(--color-primary-94);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--color-neutrals-50);
        }

        .export-button {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: var(--color-primary-52);
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .export-button:hover {
            background-color: var(--color-primary-86);
            transform: translateY(-1px);
        }

        .export-button:active {
            transform: translateY(0);
        }

        .title {
            font-size: 24px;
            color: var(--color-primary-52);
            margin: 0;
            font-weight: 500;
        }

        .workspace-info {
            color: var(--color-neutrals-50);
            font-size: 14px;
            margin-top: 8px;
        }

        .responses-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .responses-table th,
        .responses-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--color-neutrals-16);
            font-size: 14px;
        }

        .responses-table th {
            background-color: var(--color-neutrals-94);
            color: var(--color-neutrals-2);
            font-weight: 500;
        }

        .responses-table tr:last-child td {
            border-bottom: none;
        }

        .responses-table tr:hover td {
            background-color: var(--color-neutrals-94);
        }

        .timestamp {
            color: var(--color-neutrals-50);
            white-space: nowrap;
        }

        .no-responses {
            text-align: center;
            padding: 48px;
            color: var(--color-neutrals-50);
            font-size: 16px;
        }

        .response-value {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .response-value.file {
            color: var(--color-primary-52);
            text-decoration: none;
        }

        .response-value.file:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="title"><?php echo htmlspecialchars($form['title']); ?> - Responses</h1>
                    <div class="workspace-info">
                        Workspace: <?php echo htmlspecialchars($form['workspace_name']); ?>
                    </div>
                </div>
                <div class="header-right">
                    <button class="export-button" disabled>
                        Export CSV
                    </button>
                </div>
            </div>
            <div class="search-container">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" class="search-input" placeholder="Search responses..." disabled>
            </div>
        </div>

        <?php if (empty($responses)): ?>
            <div class="no-responses">No responses yet</div>
        <?php else: ?>
            <table class="responses-table">
                <thead>
                    <tr>
                        <th>Submitted By</th>
                        <th>Timestamp</th>
                        <?php foreach ($elements as $element): 
                            $elementData = json_decode($element['element_data'], true);
                        ?>
                            <th><?php echo htmlspecialchars($elementData['label'] ?? ''); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $response): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($response['username']); ?></td>
                            <td class="timestamp"><?php echo date('M j, Y g:i A', strtotime($response['created_at'])); ?></td>
                            <?php foreach ($elements as $element):
                                $value = $response['values'][$element['id']] ?? '';
                                $elementType = $element['element_type'];
                            ?>
                                <td>
                                    <?php if (in_array($elementType, ['file-upload', 'image-upload', 'document-upload']) && $value): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($value); ?>" 
                                           class="response-value file" 
                                           target="_blank">
                                            <?php echo htmlspecialchars($value); ?>
                                        </a>
                                    <?php elseif (in_array($elementType, ['checkbox', 'multi-select']) && $value): ?>
                                        <?php 
                                            $values = json_decode($value, true);
                                            echo htmlspecialchars(implode(', ', $values));
                                        ?>
                                    <?php else: ?>
                                        <div class="response-value">
                                            <?php echo htmlspecialchars($value); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html> 