<?php
session_start();
include '../backend/db.php';

$formId = isset($_GET['id']) ? $_GET['id'] : null;
$formStatus = 'draft';

if ($formId) {
    $sql = "SELECT status FROM forms WHERE id = ? AND owner_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $formId, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $formStatus = $row['status'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Form</title>
    <?php include '../backend/link.php'; ?>
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <link rel="stylesheet" href="../public/css/styling-sidebar.css">
    <link rel="stylesheet" href="../public/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-state-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 8px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 36px;
            height: 20px;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--color-neutrals-50);
            transition: .2s ease-in-out;
            border-radius: 20px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .2s ease-in-out;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--color-primary-52);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(16px);
        }

        #stateText {
            color: var(--color-neutral-94);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    <div class="form-state-toggle">
        <span>Form Status:</span>
        <label class="toggle-switch">
            <input type="checkbox" id="formStateToggle" <?php echo $formStatus === 'published' ? 'checked' : ''; ?>>
            <span class="toggle-slider"></span>
        </label>
        <span id="stateText"><?php echo ucfirst($formStatus); ?></span>
    </div>
    <?php include '../components/sidebar.php'; ?>
    <?php include '../components/canvas.php'; ?>

    <script src="../public/js/sidebar.js" defer></script>
    <script src="../public/js/canvasRenderer2.js" defer></script>
    <script>
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

        // Add functionality for choice inputs in the form builder
        function initializeChoiceInputs() {
            // Handle radio button groups
            document.querySelectorAll('.radio-group').forEach(group => {
                const options = group.querySelectorAll('input[type="radio"]');
                options.forEach(radio => {
                    radio.addEventListener('change', function() {
                        // Update the form data
                        const elementId = this.closest('.canvas__item').dataset.elementId;
                        updateElementData(elementId, {
                            value: this.value,
                            checked: this.checked
                        });
                    });
                });
            });

            // Handle checkbox groups
            document.querySelectorAll('.checkbox-group').forEach(group => {
                const options = group.querySelectorAll('input[type="checkbox"]');
                options.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        // Get all checked values in the group
                        const checkedValues = Array.from(options)
                            .filter(cb => cb.checked)
                            .map(cb => cb.value);

                        // Update the form data
                        const elementId = this.closest('.canvas__item').dataset.elementId;
                        updateElementData(elementId, {
                            values: checkedValues
                        });
                    });
                });
            });

            // Handle select/dropdown elements
            document.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', function() {
                    const elementId = this.closest('.canvas__item').dataset.elementId;
                    const values = Array.from(this.selectedOptions).map(option => option.value);
                    
                    updateElementData(elementId, {
                        values: this.multiple ? values : values[0]
                    });
                });
            });
        }

        // Function to update element data
        function updateElementData(elementId, data) {
            const element = document.querySelector(`[data-element-id="${elementId}"]`);
            if (!element) return;

            const currentData = JSON.parse(element.dataset.elementData || '{}');
            const updatedData = { ...currentData, ...data };
            element.dataset.elementData = JSON.stringify(updatedData);

            // Trigger save if auto-save is enabled
            if (typeof saveFormElement === 'function') {
                saveFormElement(elementId, updatedData);
            }
        }

        // Initialize choice inputs when the page loads
        document.addEventListener('DOMContentLoaded', initializeChoiceInputs);

        // Re-initialize choice inputs when new elements are added to the form
        document.addEventListener('elementAdded', function(e) {
            initializeChoiceInputs();
        });
    </script>
</body>
</html>