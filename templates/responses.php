<?php
session_start();
include('../backend/db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$formId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$formId) {
    die('Form ID is required');
}

// Get form details
$formSql = "SELECT f.*, u.username as creator FROM forms f 
            JOIN users u ON f.owner_id = u.id 
            WHERE f.id = '$formId' AND f.owner_id = '{$_SESSION['user_id']}'";
$formResult = mysqli_query($conn, $formSql);
$form = mysqli_fetch_assoc($formResult);

if (!$form) {
    die('Form not found or access denied');
}

// Get form elements
$elementsSql = "SELECT * FROM form_elements WHERE form_id = '$formId' ORDER BY position";
$elementsResult = mysqli_query($conn, $elementsSql);
$elements = mysqli_fetch_all($elementsResult, MYSQLI_ASSOC);

// Get responses
$responsesSql = "SELECT * FROM form_responses WHERE form_id = '$formId' ORDER BY submitted_at DESC";
$responsesResult = mysqli_query($conn, $responsesSql);
$responses = mysqli_fetch_all($responsesResult, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Responses - <?php echo htmlspecialchars($form['title']); ?></title>
    <?php include '../backend/link.php'; ?>
    <link rel="stylesheet" href="../public/css/workspace.css">
    <link rel="stylesheet" href="../public/css/responses.css">
</head>
<body>
    <section class="workspace">
        <div class="workspace__container">
            <div class="workspace__header">
                <div style="display: flex; align-items: center;">
                    <a href="workspace.php?id=<?php echo $form['workspace_id']; ?>" class="workspace__header--logo">
                        <svg width="23" height="26" viewBox="0 0 23 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect y="0.927734" width="22.4854" height="24.145" rx="5.37801" fill="#2941DC" />
                            <path d="M17.1074 16.519C17.1074 18.1455 16.5365 19.5296 15.3949 20.6712C14.2532 21.8129 12.8692 22.3837 11.2427 22.3837C9.61621 22.3837 8.23214 21.8129 7.09048 20.6712C5.94882 19.5296 5.37799 18.1455 5.37799 16.519V7.83928C5.37799 6.66634 5.78852 5.66934 6.60958 4.84828C7.43063 4.02723 8.42763 3.6167 9.60057 3.6167C10.7735 3.6167 11.7705 4.02723 12.5916 4.84828C13.4126 5.66934 13.8231 6.66634 13.8231 7.83928V16.0498C13.8231 16.7692 13.5729 17.3792 13.0725 17.8796C12.572 18.3801 11.9621 18.6303 11.2427 18.6303C10.5233 18.6303 9.91335 18.3801 9.4129 17.8796C8.91244 17.3792 8.66222 16.7692 8.66222 16.0498V7.3701H10.5389V16.0498C10.5389 16.2532 10.6054 16.4213 10.7383 16.5542C10.8713 16.6871 11.0394 16.7536 11.2427 16.7536C11.446 16.7536 11.6141 16.6871 11.747 16.5542C11.88 16.4213 11.9464 16.2532 11.9464 16.0498V7.83928C11.9308 7.18243 11.7001 6.62724 11.2544 6.1737C10.8087 5.72017 10.2574 5.4934 9.60057 5.4934C8.94372 5.4934 8.38853 5.72017 7.935 6.1737C7.48146 6.62724 7.25469 7.18243 7.25469 7.83928V16.519C7.23905 17.6294 7.62221 18.5717 8.40417 19.3458C9.18613 20.1199 10.1323 20.507 11.2427 20.507C12.3374 20.507 13.268 20.1199 14.0343 19.3458C14.8006 18.5717 15.1994 17.6294 15.2307 16.519V7.3701H17.1074V16.519Z" fill="white" />
                        </svg>
                        <span class="workspace__header--title text-body-medium bold"><?php echo htmlspecialchars($form['title']); ?> - Responses</span>
                    </a>
                    <div class="form-state-toggle">
                        <span>Form Status:</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="formStateToggle" <?php echo $form['status'] === 'published' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span id="stateText"><?php echo ucfirst($form['status'] ?? 'draft'); ?></span>
                    </div>
                </div>
                <div class="workspace__header--links">
                    <button class="btn btn--secondary" onclick="exportToCSV()">
                        <i class="fas fa-download"></i>
                        Export to CSV
                    </button>
                    <a href="createForm.php?id=<?php echo $formId; ?>" class="btn btn--primary">
                        <i class="fas fa-edit"></i>
                        Edit Form
                    </a>
                </div>
            </div>

            <?php if (empty($responses)): ?>
                <div class="workspace__empty">
                    <div class="workspace__empty-content">
                        <i class="fas fa-inbox fa-3x"></i>
                        <p class="text-body-regular text-neutral-50">No responses yet</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="responses-table-container">
                    <div class="responses-table-wrapper">
                        <table class="responses-table">
                            <thead>
                                <tr>
                                    <th>Submission Date</th>
                                    <?php foreach ($elements as $element): ?>
                                        <th><?php echo htmlspecialchars($element['label']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responses as $response): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($response['submitted_at'])); ?></td>
                                        <?php 
                                        $responseData = json_decode($response['response_data'], true);
                                        foreach ($elements as $element): 
                                            $elementId = "element_" . $element['id'];
                                            $value = isset($responseData[$elementId]) ? $responseData[$elementId] : '';
                                            if (is_array($value)) {
                                                $value = implode(', ', $value);
                                            }
                                        ?>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        function exportToCSV() {
            window.location.href = `../backend/export_responses.php?id=<?php echo $formId; ?>`;
        }

        document.getElementById('formStateToggle').addEventListener('change', async function(e) {
            const formId = new URLSearchParams(window.location.search).get('id');
            if (!formId) return;
            
            const newState = this.checked ? 'published' : 'draft';
            try {
                const response = await fetch('../backend/api/update_form_state.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        formId: formId,
                        state: newState
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to update form state');
                }

                const data = await response.json();
                if (data.success) {
                    document.getElementById('stateText').textContent = newState.charAt(0).toUpperCase() + newState.slice(1);
                } else {
                    throw new Error(data.error || 'Failed to update form state');
                }
            } catch (error) {
                console.error('Error updating form state:', error);
                this.checked = !this.checked;
                alert('Failed to update form state. Please try again.');
            }
        });
    </script>
</body>
</html>