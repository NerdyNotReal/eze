<?php
include('../backend/db.php');

// Get form ID from URL
$formId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$formId) {
    die('Form ID is required');
}

// Get form details
$formSql = "SELECT f.*, u.username as creator FROM forms f 
            JOIN users u ON f.owner_id = u.id 
            WHERE f.id = ? AND f.status = 'published'";
$stmt = mysqli_prepare($conn, $formSql);
mysqli_stmt_bind_param($stmt, "i", $formId);
mysqli_stmt_execute($stmt);
$formResult = mysqli_stmt_get_result($stmt);
$form = mysqli_fetch_assoc($formResult);

if (!$form) {
    die('Form not found or not published');
}

// Get form elements
$elementsSql = "SELECT * FROM form_elements WHERE form_id = ? ORDER BY position";
$stmt = mysqli_prepare($conn, $elementsSql);
mysqli_stmt_bind_param($stmt, "i", $formId);
mysqli_stmt_execute($stmt);
$elementsResult = mysqli_stmt_get_result($stmt);
$elements = mysqli_fetch_all($elementsResult, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['title']); ?></title>
    <link rel="stylesheet" href="../public/css/theme.css">
    <link rel="stylesheet" href="../public/css/utilities.css">
    <style>
        body {
            background-color: var(--color-neutrals-94);
            margin: 0;
            padding: 20px;
            font-family: 'Inter', sans-serif;
        }

        .preview-form {
            max-width: 640px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .preview-form-title {
            font-size: 32px;
            color: var(--color-neutrals-2);
            margin: 0 0 8px 0;
            padding: 0;
            font-weight: 400;
        }

        .preview-form-group {
            margin-bottom: 24px;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            border: 1px solid var(--color-neutrals-16);
            transition: box-shadow 0.2s ease;
        }

        .preview-form-group:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .preview-label {
            display: block;
            font-size: 16px;
            color: var(--color-neutrals-2);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .preview-input {
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid var(--color-neutrals-16);
            border-radius: 4px;
            background-color: #fff;
            color: var(--color-neutrals-2);
        }

        .preview-input:focus {
            outline: none;
            border-color: var(--color-primary-52);
            box-shadow: 0 0 0 2px var(--color-primary-94);
        }

        .preview-description {
            margin-top: 8px;
            font-size: 12px;
            color: var(--color-neutrals-50);
        }

        .preview-radio-group,
        .preview-checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 12px;
            width: 100%;
            border: 1px solid var(--color-neutrals-16);
            border-radius: 4px;
            padding: 12px;
            background-color: #fff;
        }

        .preview-radio-group label,
        .preview-checkbox-group label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: var(--color-neutrals-2);
            font-size: 14px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
            margin: 0;
        }

        .preview-radio-group label:hover,
        .preview-checkbox-group label:hover {
            background-color: var(--color-neutrals-94);
        }

        .preview-radio-group input[type="radio"],
        .preview-checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
            border: 2px solid var(--color-neutrals-16);
            background-color: #fff;
            transition: all 0.2s ease;
            flex-shrink: 0;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            position: relative;
            top: 2px;
        }

        .preview-radio-group input[type="radio"] {
            border-radius: 50%;
        }

        .preview-checkbox-group input[type="checkbox"] {
            border-radius: 4px;
        }

        .preview-radio-group input[type="radio"]:checked,
        .preview-checkbox-group input[type="checkbox"]:checked {
            background-color: var(--color-primary-52);
            border-color: var(--color-primary-52);
        }

        .preview-radio-group input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background-color: white;
            border-radius: 50%;
        }

        .preview-checkbox-group input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
        }

        .preview-radio-group input[type="radio"]:hover,
        .preview-checkbox-group input[type="checkbox"]:hover {
            border-color: var(--color-primary-52);
        }

        select.preview-input {
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid var(--color-neutrals-16);
            border-radius: 4px;
            background-color: #fff;
            color: var(--color-neutrals-2);
            padding-right: 32px;
            background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L5 5L9 1' stroke='%23666' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            cursor: pointer;
        }

        select.preview-input[multiple] {
            padding-right: 12px;
            background-image: none;
            min-height: 120px;
        }

        .option-description {
            font-size: 12px;
            color: var(--color-neutrals-50);
            margin-top: 4px;
            line-height: 1.4;
            padding-left: 32px;
        }

        .preview-radio-group label > span,
        .preview-checkbox-group label > span {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .preview-submit-button {
            display: inline-block;
            padding: 10px 24px;
            background-color: var(--color-primary-52);
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .preview-submit-button:hover {
            background-color: var(--color-primary-86);
        }

        .preview-label.required:after {
            content: "*";
            color: var(--color-sementic-red);
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <form id="preview-form" class="preview-form" method="POST" action="/Form/backend/submit_response.php">
        <input type="hidden" name="form_id" value="<?php echo $formId; ?>">
        
        <h1 class="preview-form-title"><?php echo htmlspecialchars($form['title']); ?></h1>
        <?php if ($form['description']): ?>
            <p class="preview-description"><?php echo htmlspecialchars($form['description']); ?></p>
        <?php endif; ?>

        <?php foreach ($elements as $element): ?>
            <?php 
                $elementData = json_decode($element['element_data'], true);
                $elementData = array_merge([
                    'label' => '',
                    'description' => '',
                    'required' => false,
                    'placeholder' => '',
                    'options' => [],
                    'properties' => []
                ], $elementData ?: []);
            ?>
            <div class="preview-form-group">
                <label class="preview-label <?php echo ($elementData['required'] ?? false) ? 'required' : ''; ?>">
                    <?php echo htmlspecialchars($elementData['label']); ?>
                </label>

                <?php if (isset($elementData['description']) && $elementData['description']): ?>
                    <p class="preview-description"><?php echo htmlspecialchars($elementData['description']); ?></p>
                <?php endif; ?>

                <?php
                switch($element['element_type']):
                    case 'text':
                    case 'text-field':
                    case 'textfield':
                        ?>
                        <input type="text" 
                               name="element_<?php echo $element['id']; ?>" 
                               class="preview-input"
                               <?php echo ($elementData['required'] ?? false) ? 'required' : ''; ?>
                               placeholder="<?php echo htmlspecialchars($elementData['placeholder'] ?? ''); ?>">
                        <?php
                        break;

                    case 'textarea':
                    case 'text-area':
                        ?>
                        <textarea name="element_<?php echo $element['id']; ?>" 
                                  class="preview-input"
                                  <?php echo ($elementData['required'] ?? false) ? 'required' : ''; ?>
                                  placeholder="<?php echo htmlspecialchars($elementData['placeholder'] ?? ''); ?>"></textarea>
                        <?php
                        break;

                    case 'radio':
                    case 'radio-button':
                        ?>
                        <div class="preview-radio-group">
                            <?php foreach ($elementData['options'] as $option): ?>
                                <label>
                                    <input type="radio" 
                                           id="element_<?php echo $element['id']; ?>_<?php echo htmlspecialchars($option['value']); ?>"
                                           name="element_<?php echo $element['id']; ?>" 
                                           value="<?php echo htmlspecialchars($option['value']); ?>"
                                           <?php echo ($elementData['required'] ?? false) ? 'required' : ''; ?>>
                                    <span>
                                        <?php echo htmlspecialchars($option['label']); ?>
                                        <?php if (isset($option['description']) && $option['description']): ?>
                                            <span class="option-description"><?php echo htmlspecialchars($option['description']); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        break;

                    case 'checkbox':
                    case 'checkbox-group':
                        ?>
                        <div class="preview-checkbox-group">
                            <?php foreach ($elementData['options'] as $option): ?>
                                <label>
                                    <input type="checkbox" 
                                           id="element_<?php echo $element['id']; ?>_<?php echo htmlspecialchars($option['value']); ?>"
                                           name="element_<?php echo $element['id']; ?>[]" 
                                           value="<?php echo htmlspecialchars($option['value']); ?>"
                                           <?php echo ($elementData['required'] ?? false) ? 'required' : ''; ?>>
                                    <span>
                                        <?php echo htmlspecialchars($option['label']); ?>
                                        <?php if (isset($option['description']) && $option['description']): ?>
                                            <span class="option-description"><?php echo htmlspecialchars($option['description']); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        break;

                    case 'select':
                    case 'dropdown':
                    case 'multi-select':
                        $isMulti = $element['element_type'] === 'multi-select';
                        ?>
                        <select name="element_<?php echo $element['id']; ?><?php echo $isMulti ? '[]' : ''; ?>" 
                                id="element_<?php echo $element['id']; ?>"
                                class="preview-input"
                                <?php echo ($elementData['required'] ?? false) ? 'required' : ''; ?>
                                <?php echo $isMulti ? 'multiple' : ''; ?>>
                            <?php if (!$isMulti): ?>
                                <option value="">Select an option</option>
                            <?php endif; ?>
                            <?php foreach ($elementData['options'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option['value']); ?>">
                                    <?php echo htmlspecialchars($option['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($isMulti): ?>
                            <p class="preview-description">Hold Ctrl/Cmd to select multiple options</p>
                        <?php endif; ?>
                        <?php
                        break;

                    default:
                        ?>
                        <input type="text" 
                               name="element_<?php echo $element['id']; ?>" 
                               class="preview-input"
                               <?php echo ($elementData['required'] ?? false) ? 'required' : ''; ?>
                               placeholder="<?php echo htmlspecialchars($elementData['placeholder'] ?? ''); ?>">
                        <?php
                        break;
                endswitch;
                ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="preview-submit-button">Submit</button>
    </form>

    <script>
        document.getElementById('preview-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                
                // Handle checkbox groups
                const checkboxGroups = document.querySelectorAll('.preview-checkbox-group');
                checkboxGroups.forEach(group => {
                    const name = group.querySelector('input[type="checkbox"]').name.replace('[]', '');
                    const checkedBoxes = group.querySelectorAll('input[type="checkbox"]:checked');
                    if (checkedBoxes.length === 0) {
                        formData.append(name, '');
                    }
                });

                // Handle required validation for checkbox groups
                const requiredGroups = document.querySelectorAll('.preview-checkbox-group input[required]');
                let isValid = true;
                requiredGroups.forEach(input => {
                    const groupName = input.name.replace('[]', '');
                    const group = document.querySelectorAll(`input[name="${input.name}"]`);
                    const checked = Array.from(group).some(checkbox => checkbox.checked);
                    if (!checked) {
                        isValid = false;
                        input.setCustomValidity('Please select at least one option');
                    } else {
                        input.setCustomValidity('');
                    }
                });

                if (!isValid) {
                    return;
                }

                const response = await fetch('/Form/backend/submit_response.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Form submitted successfully!');
                    this.reset();
                } else {
                    alert(result.error || 'Failed to submit form');
                }
            } catch (error) {
                alert('Failed to submit form: ' + error.message);
            }
        });

        // Add event listeners for checkbox group validation
        document.querySelectorAll('.preview-checkbox-group input[required]').forEach(input => {
            input.addEventListener('change', function() {
                const groupName = this.name;
                const group = document.querySelectorAll(`input[name="${groupName}"]`);
                const checked = Array.from(group).some(checkbox => checkbox.checked);
                group.forEach(checkbox => {
                    if (!checked) {
                        checkbox.setCustomValidity('Please select at least one option');
                    } else {
                        checkbox.setCustomValidity('');
                    }
                });
            });
        });
    </script>
</body>
</html> 