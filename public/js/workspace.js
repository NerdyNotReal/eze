document.addEventListener('DOMContentLoaded', function() {
    const elements = {
        formGrid: document.querySelector('.workspace__forms--grid'),
        workspaceTitle: document.querySelector('.workspace__header--title'),
        createFormPopup: document.querySelector('#createFormPopup'),
        createFormBtn: document.querySelector('#createFormBtn'),
        closeFormPopupBtn: document.querySelector('#closeFormPopupBtn'),
        createFormForm: document.querySelector('#createFormForm'),
        invitePopup: document.querySelector('#invitePopup'),
        inviteBtn: document.querySelector('#inviteBtn'),
        closeInvitePopupBtn: document.querySelector('#closeInvitePopupBtn'),
        inviteForm: document.querySelector('#inviteForm'),
        collaboratorsList: document.querySelector('#collaboratorsList'),
        inviteLinkInput: document.querySelector('#inviteLink'),
        copyInviteLinkBtn: document.querySelector('#copyInviteLink'),
        generateInviteLinkBtn: document.querySelector('#generateInviteLink')
    };

    const workspaceId = new URLSearchParams(window.location.search).get('id');

    if (!workspaceId) {
        window.location.href = 'dashboard.php';
        return;
    }

    // Event handling
    elements.createFormBtn.addEventListener('click', () => {
        elements.createFormPopup.style.display = 'block';
    });

    elements.closeFormPopupBtn.addEventListener('click', () => {
        elements.createFormPopup.style.display = 'none';
    });

    // Add event delegation for delete form buttons
    elements.formGrid.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.workspace-card__action[data-action="delete"]');
        if (deleteBtn) {
            const formId = deleteBtn.dataset.id;
            deleteForm(formId);
        }
    });

    // Add event delegation for form state changes
    elements.formGrid.addEventListener('click', (e) => {
        const stateToggle = e.target.closest('.workspace-card__action[data-action="toggle-state"]');
        if (stateToggle) {
            const formId = stateToggle.dataset.id;
            const currentState = stateToggle.dataset.state;
            const newState = currentState === 'draft' ? 'published' : 'draft';
            updateFormState(formId, newState);
        }
    });

    function deleteForm(formId) {
        if (confirm('Are you sure you want to delete this form? This action cannot be undone.')) {
            fetch('../backend/api/deleteforms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: formId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    loadWorkspaceData();
                } else {
                    throw new Error(result.error || 'Failed to delete form');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Failed to delete form');
            });
        }
    }

    function updateFormState(formId, state) {
        fetch('../backend/api/update_form_state.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ formId, state })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                loadWorkspaceData();
            } else {
                throw new Error(result.error || 'Failed to update form state');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to update form state');
        });
    }

    elements.createFormForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(elements.createFormForm);
        formData.append('workspaceId', workspaceId);

        fetch('../backend/api/create_form.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                elements.createFormPopup.style.display = 'none';
                elements.createFormForm.reset();
                loadWorkspaceData();
            } else {
                throw new Error(result.error || 'Failed to create form');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to create form. Please try again.');
        });
    });

    // Template popup functionality
    const createFromTemplateBtn = document.getElementById('createFromTemplateBtn');
    const templatesPopup = document.getElementById('templatesPopup');
    const closeTemplatesPopupBtn = document.getElementById('closeTemplatesPopupBtn');
    const templateCards = document.querySelectorAll('.template-card');

    // Open templates popup
    createFromTemplateBtn.addEventListener('click', () => {
        templatesPopup.style.display = 'flex';
    });

    // Close templates popup
    closeTemplatesPopupBtn.addEventListener('click', () => {
        templatesPopup.style.display = 'none';
    });

    // Close popup when clicking outside
    templatesPopup.addEventListener('click', (e) => {
        if (e.target === templatesPopup) {
            templatesPopup.style.display = 'none';
        }
    });

    // Handle template selection
    templateCards.forEach(card => {
        card.addEventListener('click', () => {
            const templateType = card.dataset.template;
            // Here you can implement the logic to create a form based on the selected template
            createFormFromTemplate(templateType);
        });
    });

    function createFormFromTemplate(templateType) {
        const templates = {
            contact: {
                title: 'Contact Form',
                description: 'A contact form template',
                fields: [
                    { type: 'text', label: 'Name', required: true },
                    { type: 'email', label: 'Email', required: true },
                    { type: 'textarea', label: 'Message', required: true }
                ]
            },
            survey: {
                title: 'Survey Form',
                description: 'A customer feedback survey',
                fields: [
                    { type: 'text', label: 'Name', required: true },
                    { type: 'email', label: 'Email', required: true },
                    { type: 'radio', label: 'How satisfied are you?', options: ['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied'] },
                    { type: 'textarea', label: 'Additional Comments' }
                ]
            },
            registration: {
                title: 'Registration Form',
                description: 'A user registration form',
                fields: [
                    { type: 'text', label: 'Full Name', required: true },
                    { type: 'email', label: 'Email', required: true },
                    { type: 'password', label: 'Password', required: true },
                    { type: 'password', label: 'Confirm Password', required: true },
                    { type: 'date', label: 'Date of Birth', required: true }
                ]
            },
            event: {
                title: 'Event Registration',
                description: 'An event registration form',
                fields: [
                    { type: 'text', label: 'Attendee Name', required: true },
                    { type: 'email', label: 'Email', required: true },
                    { type: 'tel', label: 'Phone Number', required: true },
                    { type: 'select', label: 'Number of Tickets', options: ['1', '2', '3', '4', '5'], required: true },
                    { type: 'checkbox', label: 'Dietary Restrictions', options: ['Vegetarian', 'Vegan', 'Gluten-free', 'None'] }
                ]
            }
        };

        const template = templates[templateType];
        if (template) {
            const formData = new FormData();
            formData.append('workspaceId', workspaceId);
            formData.append('title', template.title);
            formData.append('description', template.description);
            formData.append('fields', JSON.stringify(template.fields));
            formData.append('isTemplate', true);

            fetch('../backend/api/create_form_template.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    templatesPopup.style.display = 'none';
                    loadWorkspaceData();
                    // Redirect to the form editor
                    window.location.href = `createForm.php?id=${result.formId}`;
                } else {
                    throw new Error(result.error || 'Failed to create form from template');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Failed to create form from template');
            });
        }
    }

    function createFormCard(form) {
        return `
            <div class="workspace-card" data-form-id="${form.id}">
                <div class="workspace-card__content">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <i class="fa-regular fa-file-lines"></i>
                        </div>
                        <h3 class="workspace-card__title">${form.title}</h3>
                        <span class="workspace-card__status">
                            <i class="fa-regular fa-circle-${form.status === 'published' ? 'check' : 'dot'}"></i>
                            ${form.status}
                        </span>
                    </div>
                    <p class="workspace-card__description">${form.description || 'No description'}</p>
                    <div class="workspace-card__meta">
                        <span class="workspace-card__date">
                            <i class="fa-regular fa-calendar"></i>
                            ${new Date(form.created_at).toLocaleDateString()}
                        </span>
                    </div>
                    <div class="workspace-card__actions">
                        <button class="workspace-card__action" data-action="toggle-state" data-id="${form.id}" data-state="${form.status}">
                            <i class="fa-solid fa-toggle-${form.status === 'published' ? 'on' : 'off'}"></i>
                            ${form.status === 'published' ? 'Unpublish' : 'Publish'}
                        </button>
                        ${form.status === 'published' ? `
                            <a href="responses.php?id=${form.id}" class="workspace-card__action" data-action="responses">
                                <i class="fa-solid fa-chart-bar"></i>
                                Responses
                            </a>
                        ` : ''}
                        <a href="createForm.php?id=${form.id}" class="workspace-card__action" data-action="edit">
                            <i class="fa-regular fa-pen-to-square"></i>
                            Edit
                        </a>
                        <button class="workspace-card__action workspace-card__action--danger" data-action="delete" data-id="${form.id}">
                            <i class="fa-regular fa-trash-can"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function showLoading() {
        const loadingCards = Array(6).fill(`
            <div class="workspace-card workspace-card--loading workspace-card--skeleton">
                <div class="workspace-card__content">
                    <div class="workspace-card__header">
                        <div class="skeleton-text"></div>
                        <div class="skeleton-text"></div>
                    </div>
                    <div class="workspace-card__info">
                        <div class="skeleton-text"></div>
                    </div>
                </div>
            </div>
        `).join('');

        elements.formGrid.innerHTML = loadingCards;
    }

    function loadWorkspaceData() {
        showLoading();
        
        fetch(`../backend/api/get_workspace.php?id=${workspaceId}`)
            .then(response => response.json())
            .then(data => {
                // Add small delay to show loading animation
                setTimeout(() => {
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to load workspace');
                    }
                    
                    // Update workspace title
                    elements.workspaceTitle.textContent = data.workspace.name;
                    document.title = `${data.workspace.name} - Workspace`;

                    // Handle button visibility based on role
                    const userRole = data.workspace.role;
                    const canCreateForms = ['owner', 'admin', 'member'].includes(userRole);
                    if (elements.createFormBtn) {
                        elements.createFormBtn.style.display = canCreateForms ? 'block' : 'none';
                    }
                    if (document.getElementById('createFromTemplateBtn')) {
                        document.getElementById('createFromTemplateBtn').style.display = canCreateForms ? 'block' : 'none';
                    }
                    
                    // Populate forms grid
                    if (data.forms && data.forms.length > 0) {
                        let formsHtml = '';
                        for (let i = 0; i < data.forms.length; i++) {
                            formsHtml += createFormCard(data.forms[i]);
                        }
                        elements.formGrid.innerHTML = formsHtml;
                    } else {
                        elements.formGrid.innerHTML = `
                            <div class="workspace__form-card workspace__form-card--empty">
                                <div class="workspace__form-card-content">
                                    <p>${canCreateForms ? 'No forms found. Create your first form!' : 'No forms found in this workspace.'}</p>
                                </div>
                            </div>
                        `;
                    }
                }, 800);
            })
            .catch(error => {
                console.error('Error:', error);
                elements.formGrid.innerHTML = `
                    <div class="workspace__form-card workspace__form-card--error">
                        <div class="workspace__form-card-content">
                            <p class="text-body-regular text-semantic-red">Failed to load workspace data. Please try again later.</p>
                        </div>
                    </div>
                `;
            });
    }

    // Invite popup functionality
    if (elements.inviteBtn && elements.invitePopup && elements.closeInvitePopupBtn) {
        elements.inviteBtn.addEventListener('click', () => {
            elements.invitePopup.style.display = 'block';
            loadCollaborators();
        });

        elements.closeInvitePopupBtn.addEventListener('click', () => {
            elements.invitePopup.style.display = 'none';
        });

        // Close popup when clicking outside
        elements.invitePopup.addEventListener('click', (e) => {
            if (e.target === elements.invitePopup) {
                elements.invitePopup.style.display = 'none';
            }
        });
    }

    // Invite link functionality
    if (elements.generateInviteLinkBtn) {
        elements.generateInviteLinkBtn.addEventListener('click', () => {
            const role = elements.inviteForm.querySelector('#inviteRole').value;
            generateInviteLink(role);
        });
    }

    if (elements.copyInviteLinkBtn && elements.inviteLinkInput) {
        elements.copyInviteLinkBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(elements.inviteLinkInput.value);
                const originalText = elements.copyInviteLinkBtn.innerHTML;
                elements.copyInviteLinkBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                setTimeout(() => {
                    elements.copyInviteLinkBtn.innerHTML = originalText;
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
                alert('Failed to copy link');
            }
        });
    }

    function generateInviteLink(role) {
        elements.generateInviteLinkBtn.disabled = true;
        elements.generateInviteLinkBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
        elements.inviteLinkInput.value = 'Generating link...';

        fetch('../backend/api/generate_invite_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ workspaceId, role })
        })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(`Invalid JSON response: ${text}`);
            }
        })
        .then(result => {
            if (result.success) {
                // Get the base URL dynamically
                const path = window.location.pathname;
                const basePath = path.substring(0, path.indexOf('/templates/'));
                const inviteUrl = `${window.location.protocol}//${window.location.host}${basePath}/templates/join.php?token=${result.token}`;
                elements.inviteLinkInput.value = inviteUrl;
                elements.inviteLinkInput.select();
                elements.copyInviteLinkBtn.disabled = false;
            } else {
                throw new Error(result.error || 'Failed to generate invite link');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to generate invite link');
            elements.inviteLinkInput.value = '';
            elements.copyInviteLinkBtn.disabled = true;
        })
        .finally(() => {
            elements.generateInviteLinkBtn.disabled = false;
            elements.generateInviteLinkBtn.innerHTML = '<i class="fa-solid fa-rotate"></i> Generate New Link';
        });
    }

    function loadCollaborators() {
        if (!elements.collaboratorsList) return;

        elements.collaboratorsList.innerHTML = `
            <div class="collaborator-item collaborator-item--loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                Loading collaborators...
            </div>
        `;

        fetch(`../backend/api/get_collaborators.php?workspaceId=${workspaceId}`)
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(`Invalid JSON response: ${text}`);
            }
        })
        .then(data => {
            if (data.success) {
                const html = data.collaborators.map(collaborator => `
                    <div class="collaborator-item">
                        <div class="collaborator-info">
                            <span class="collaborator-email">${collaborator.email}</span>
                            <span class="collaborator-role">${collaborator.role}</span>
                        </div>
                        ${collaborator.is_owner ? '<span class="owner-badge">Owner</span>' :
                        `<button class="btn btn--danger btn--small" onclick="removeCollaborator(${collaborator.user_id})">
                            Remove
                        </button>`}
                    </div>
                `).join('');
                elements.collaboratorsList.innerHTML = html || '<p>No collaborators yet</p>';
            } else {
                throw new Error(data.error || 'Failed to load collaborators');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            elements.collaboratorsList.innerHTML = `<p>Failed to load collaborators: ${error.message}</p>`;
        });
    }

    window.removeCollaborator = function(userId) {
        if (!confirm('Are you sure you want to remove this collaborator?')) return;

        fetch('../backend/api/remove_collaborator.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ workspaceId, userId })
        })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(`Invalid JSON response: ${text}`);
            }
        })
        .then(result => {
            if (result.success) {
                loadCollaborators();
            } else {
                throw new Error(result.error || 'Failed to remove collaborator');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to remove collaborator');
        });
    };

    // Initial load
    loadWorkspaceData();
});