// Function to create preview modal
function createPreviewModal() {
    const modal = document.createElement('div');
    modal.className = 'preview-modal';
    modal.innerHTML = `
        <div class="preview-modal-content">
            <div class="preview-modal-header">
                <h2>Form Preview</h2>
                <button onclick="closePreviewModal()" class="close-btn">×</button>
            </div>
            <div class="preview-modal-body">
                <form id="preview-form" class="preview-form"></form>
            </div>
        </div>
    `;
    return modal;
}

// Function to render preview form
function renderPreviewForm() {
    const formCanvas = document.getElementById('form-canvas');
    const previewForm = document.getElementById('preview-form');
    
    // Clear previous preview
    previewForm.innerHTML = '';
    
    // Add form title if exists
    const formTitle = document.querySelector('.form-title');
    if (formTitle) {
        const titleElement = document.createElement('h1');
        titleElement.textContent = formTitle.textContent;
        titleElement.className = 'preview-form-title';
        previewForm.appendChild(titleElement);
    }
    
    // Clone all form elements from canvas
    const formElements = formCanvas.querySelectorAll('.canvas__item');
    console.log('Found form elements:', formElements.length);

    if (formElements.length === 0) {
        // Show a message if no form elements exist
        const emptyMessage = document.createElement('p');
        emptyMessage.textContent = 'No form elements added yet. Add some elements to preview the form.';
        emptyMessage.className = 'preview-empty-message';
        previewForm.appendChild(emptyMessage);
        return;
    }

    formElements.forEach((element, index) => {
        const formGroup = document.createElement('div');
        formGroup.className = 'preview-form-group';
        
        // Get element data
        const elementData = element.dataset;
        const elementType = elementData.type || 'text';
        const isRequired = elementData.required === 'true';
        
        // Create label
        const label = document.createElement('label');
        label.className = 'preview-label' + (isRequired ? ' required' : '');
        label.textContent = elementData.label || `Question ${index + 1}`;
        formGroup.appendChild(label);

        // Add description if exists
        if (elementData.description) {
            const description = document.createElement('p');
            description.className = 'preview-description';
            description.textContent = elementData.description;
            formGroup.appendChild(description);
        }

        // Create input element based on type
        let input;
        const options = elementData.options ? JSON.parse(elementData.options) : [];

        switch(elementType) {
            case 'radio':
            case 'radio-button':
                const radioGroup = document.createElement('div');
                radioGroup.className = 'preview-radio-group';
                
                options.forEach((option, optionIndex) => {
                    const label = document.createElement('label');
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = `element_${index}`;
                    radio.value = option.value;
                    radio.required = isRequired;
                    radio.id = `element_${index}_${optionIndex}`;

                    const span = document.createElement('span');
                    span.textContent = option.label;

                    if (option.description) {
                        const desc = document.createElement('span');
                        desc.className = 'option-description';
                        desc.textContent = option.description;
                        span.appendChild(desc);
                    }

                    label.appendChild(radio);
                    label.appendChild(span);
                    radioGroup.appendChild(label);
                });
                
                formGroup.appendChild(radioGroup);
                break;

            case 'checkbox':
            case 'checkbox-group':
                const checkboxGroup = document.createElement('div');
                checkboxGroup.className = 'preview-checkbox-group';
                
                options.forEach((option, optionIndex) => {
                    const label = document.createElement('label');
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = `element_${index}[]`;
                    checkbox.value = option.value;
                    checkbox.required = isRequired;
                    checkbox.id = `element_${index}_${optionIndex}`;

                    const span = document.createElement('span');
                    span.textContent = option.label;

                    if (option.description) {
                        const desc = document.createElement('span');
                        desc.className = 'option-description';
                        desc.textContent = option.description;
                        span.appendChild(desc);
                    }

                    label.appendChild(checkbox);
                    label.appendChild(span);
                    checkboxGroup.appendChild(label);
                });
                
                formGroup.appendChild(checkboxGroup);

                // Add validation for required checkbox groups
                if (isRequired) {
                    const checkboxes = checkboxGroup.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            const group = checkboxGroup.querySelectorAll('input[type="checkbox"]');
                            const checked = Array.from(group).some(cb => cb.checked);
                            group.forEach(cb => {
                                if (!checked) {
                                    cb.setCustomValidity('Please select at least one option');
                                } else {
                                    cb.setCustomValidity('');
                                }
                            });
                        });
                    });
                }
                break;

            case 'select':
            case 'dropdown':
            case 'multi-select':
                const isMulti = elementType === 'multi-select';
                const select = document.createElement('select');
                select.className = 'preview-input';
                select.name = `element_${index}${isMulti ? '[]' : ''}`;
                select.required = isRequired;
                select.id = `element_${index}`;
                
                if (isMulti) {
                    select.multiple = true;
                } else {
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Select an option';
                    select.appendChild(defaultOption);
                }

                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.label;
                    select.appendChild(optionElement);
                });

                formGroup.appendChild(select);

                if (isMulti) {
                    const helpText = document.createElement('p');
                    helpText.className = 'preview-description';
                    helpText.textContent = 'Hold Ctrl/Cmd to select multiple options';
                    formGroup.appendChild(helpText);
                }
                break;

            default:
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'preview-input';
                input.name = `element_${index}`;
                input.required = isRequired;
                input.placeholder = elementData.placeholder || '';
                formGroup.appendChild(input);
                break;
        }

        previewForm.appendChild(formGroup);
    });

    // Add submit button
    const submitButton = document.createElement('button');
    submitButton.type = 'submit';
    submitButton.className = 'preview-submit-button';
    submitButton.textContent = 'Submit';
    previewForm.appendChild(submitButton);

    // Add form submit handler
    previewForm.addEventListener('submit', async function(e) {
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

            alert('Form submitted successfully! (Preview mode)');
            this.reset();
        } catch (error) {
            alert('Failed to submit form: ' + error.message);
        }
    });
}

// Function to show preview modal
function previewForm() {
    // Create modal if it doesn't exist
    let modal = document.querySelector('.preview-modal');
    if (!modal) {
        modal = createPreviewModal();
        document.body.appendChild(modal);
    }
    
    // Show modal
    modal.style.display = 'block';
    
    // Render preview form
    renderPreviewForm();
}

// Function to close preview modal
function closePreviewModal() {
    const modal = document.querySelector('.preview-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Add styles for preview modal
const styles = `
.preview-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.preview-modal-content {
    position: relative;
    background-color: var(--color-neutrals-94);
    margin: 2% auto;
    padding: 0;
    width: 90%;
    max-width: 640px;
    border-radius: 8px;
    max-height: 90vh;
    overflow-y: auto;
}

.preview-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid var(--color-neutrals-16);
    background-color: var(--color-primary-94);
    border-radius: 8px 8px 0 0;
}

.preview-modal-header h2 {
    color: var(--color-primary-52);
}

.preview-form-title {
    font-size: 32px;
    color: var(--color-neutrals-2);
    margin: 0 0 8px 0;
    padding: 0;
    font-weight: 400;
}

.preview-empty-message {
    text-align: center;
    color: var(--color-neutrals-50);
    padding: 40px 20px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--color-neutrals-50);
}

.close-btn:hover {
    color: var(--color-neutrals-16);
}

.preview-modal-body {
    padding: 24px;
    background: var(--color-neutrals-94);
}

.preview-form {
    max-width: 100%;
    margin: 0 auto;
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
}

.preview-radio-group label,
.preview-checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--color-neutrals-2);
    font-size: 14px;
    cursor: pointer;
}

.preview-form select {
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
    border: 1px solid var(--color-neutrals-16);
    border-radius: 4px;
    background-color: #fff;
    color: var(--color-neutrals-2);
}

.preview-form textarea {
    width: 100%;
    min-height: 120px;
    padding: 12px;
    font-size: 14px;
    border: 1px solid var(--color-neutrals-16);
    border-radius: 4px;
    resize: vertical;
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

/* Required field indicator */
.preview-label.required:after {
    content: "*";
    color: var(--color-sementic-red);
    margin-left: 4px;
}
`;

// Add styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet); 