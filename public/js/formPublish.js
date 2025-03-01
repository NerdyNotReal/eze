// Function to create publish modal
function createPublishModal() {
    const modal = document.createElement('div');
    modal.className = 'publish-modal';
    const formStatus = document.getElementById('formStateToggle')?.checked ? 'published' : 'draft';
    
    modal.innerHTML = `
        <div class="publish-modal-content">
            <div class="publish-modal-header">
                <div class="status-indicator">
                    <i class="fas fa-paper-plane"></i>
                    <h2>Publish Your Form</h2>
                </div>
                <button onclick="closePublishModal()" class="close-btn">×</button>
            </div>
            <div class="publish-modal-body">
                <div class="form-status-section">
                    <div class="status-toggle">
                        <span class="status-label">Form Status</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="modalFormStateToggle" ${formStatus === 'published' ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                        <span id="modalStateText" class="status-text ${formStatus === 'published' ? 'published' : 'draft'}">${formStatus === 'published' ? 'Published' : 'Draft'}</span>
                    </div>
                    <p class="status-description">
                        ${formStatus === 'published' 
                            ? 'Your form is live and accepting responses.' 
                            : 'Toggle to publish and start accepting responses.'}
                    </p>
                </div>
                <div class="sharing-section">
                    <h3>Share Your Form</h3>
                    <p class="sharing-text">Share this URL with people who need to fill out the form:</p>
                    <div class="url-container">
                        <input type="text" id="formUrl" readonly class="form-url-input" />
                        <button onclick="copyFormUrl()" class="copy-url-btn">
                            <i class="fas fa-copy"></i>
                            Copy URL
                        </button>
                    </div>
                    <div id="copyMessage" class="copy-message"></div>
                </div>
            </div>
        </div>
    `;
    return modal;
}

// Function to show publish modal with URL
function showPublishModal(url) {
    let modal = document.querySelector('.publish-modal');
    if (!modal) {
        modal = createPublishModal();
        document.body.appendChild(modal);
    }
    
    const urlInput = modal.querySelector('#formUrl');
    urlInput.value = url;
    
    modal.style.display = 'block';
    initializeModalToggle();
}

// Function to close publish modal
function closePublishModal() {
    const modal = document.querySelector('.publish-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Function to copy form URL
function copyFormUrl() {
    const urlInput = document.getElementById('formUrl');
    urlInput.select();
    document.execCommand('copy');
    
    const copyMessage = document.getElementById('copyMessage');
    copyMessage.textContent = 'URL copied to clipboard!';
    copyMessage.style.opacity = '1';
    
    setTimeout(() => {
        copyMessage.style.opacity = '0';
    }, 2000);
}

// Function to gather form elements data
function gatherFormElements() {
    const formElements = [];
    const elements = document.querySelectorAll('.canvas__item');
    
    elements.forEach(element => {
        const questionDiv = element.querySelector('div[class*="text-body-medium"]');
        if (!questionDiv) return;

        const label = questionDiv.querySelector('label')?.textContent || '';
        const input = questionDiv.querySelector('input, select, textarea');
        if (!input) return;

        const elementData = {
            type: input.type || input.tagName.toLowerCase(),
            label: label,
            required: input.hasAttribute('required'),
            properties: {
                placeholder: input.placeholder || '',
                description: element.querySelector('.field-description')?.textContent || '',
                options: []
            }
        };

        // Handle special cases like radio, checkbox, select
        if (input.type === 'radio' || input.type === 'checkbox') {
            const options = element.querySelectorAll('input[type="radio"], input[type="checkbox"]');
            options.forEach(option => {
                elementData.properties.options.push({
                    label: option.nextElementSibling?.textContent || '',
                    value: option.value || ''
                });
            });
        } else if (input.tagName === 'SELECT') {
            const options = input.querySelectorAll('option');
            options.forEach(option => {
                elementData.properties.options.push({
                    label: option.textContent,
                    value: option.value
                });
            });
        }

        formElements.push(elementData);
    });

    return formElements;
}

// Function to publish form
async function publishForm() {
    try {
        const formId = new URLSearchParams(window.location.search).get('id');
        const formElements = gatherFormElements();

        const response = await fetch('/Form/backend/publish_form.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                formId: formId,
                formElements: formElements
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showPublishModal(data.publicUrl);
        } else {
            alert('Failed to publish form: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Failed to publish form: ' + error.message);
    }
}

// Add event listener for modal form state toggle
function initializeModalToggle() {
    const modalToggle = document.getElementById('modalFormStateToggle');
    const headerToggle = document.getElementById('formStateToggle');
    
    if (modalToggle) {
        modalToggle.addEventListener('change', async function(e) {
            const newState = this.checked ? 'published' : 'draft';
            const formId = new URLSearchParams(window.location.search).get('id');
            
            try {
                const response = await fetch('/Form/backend/api/update_form_state.php', {
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
                    // Update both toggles and status texts
                    headerToggle.checked = this.checked;
                    document.getElementById('stateText').textContent = newState.charAt(0).toUpperCase() + newState.slice(1);
                    document.getElementById('modalStateText').textContent = newState.charAt(0).toUpperCase() + newState.slice(1);
                    document.getElementById('modalStateText').className = 'status-text ' + newState;
                    
                    // Update status description
                    const statusDescription = document.querySelector('.status-description');
                    statusDescription.textContent = newState === 'published' 
                        ? 'Your form is live and accepting responses.'
                        : 'Toggle to publish and start accepting responses.';
                } else {
                    throw new Error(data.error || 'Failed to update form state');
                }
            } catch (error) {
                console.error('Error updating form state:', error);
                this.checked = !this.checked;
                alert('Failed to update form state. Please try again.');
            }
        });
    }
}

// Add styles for publish modal
const publishStyles = `
.publish-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

.publish-modal-content {
    position: relative;
    background-color: #fff;
    margin: 10% auto;
    padding: 28px;
    width: 90%;
    max-width: 520px;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
    animation: slideIn 0.3s ease;
}

.publish-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-indicator i {
    color: #4A90E2;
    font-size: 24px;
}

.publish-modal-header h2 {
    margin: 0;
    color: #333;
    font-size: 22px;
    font-weight: 600;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 4px;
    line-height: 1;
}

.close-btn:hover {
    color: #000;
}

.form-status-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.status-toggle {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 12px;
}

.status-label {
    font-weight: 500;
    color: #333;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #4CAF50;
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.status-text {
    font-weight: 500;
}

.status-text.published {
    color: #4CAF50;
}

.status-text.draft {
    color: #666;
}

.status-description {
    color: #666;
    font-size: 14px;
    margin: 0;
}

.sharing-section {
    padding-top: 4px;
}

.sharing-section h3 {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0 0 12px;
}

.sharing-text {
    color: #666;
    font-size: 14px;
    margin: 0 0 16px;
}

.url-container {
    display: flex;
    gap: 12px;
    margin: 12px 0;
}

.form-url-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    color: #333;
    background: #f8f9fa;
}

.copy-url-btn {
    padding: 12px 20px;
    background-color: #4A90E2;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.copy-url-btn:hover {
    background-color: #357ABD;
    transform: translateY(-1px);
}

.copy-url-btn:active {
    transform: translateY(0);
}

.copy-message {
    color: #4CAF50;
    font-size: 14px;
    margin-top: 12px;
    text-align: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
`;

// Add styles to document
const publishStyleSheet = document.createElement('style');
publishStyleSheet.textContent = publishStyles;
document.head.appendChild(publishStyleSheet);