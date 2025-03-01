<?php
session_start();
include('../backend/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$workspaceId = isset($_GET['id']) ? $_GET['id'] : null;
if (!$workspaceId) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading Workspace...</title>
    <?php include '../backend/link.php'; ?>
    <link rel="stylesheet" href="../public/css/workspace.css">
</head>

<body>
    <section class="workspace">
        <div class="workspace__container">
            <div class="workspace__header">
                <a href="/dashboard.php" class="workspace__header--logo">
                    <svg width="23" height="26" viewBox="0 0 23 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect y="0.927734" width="22.4854" height="24.145" rx="5.37801" fill="#2941DC" />
                        <path
                            d="M17.1074 16.519C17.1074 18.1455 16.5365 19.5296 15.3949 20.6712C14.2532 21.8129 12.8692 22.3837 11.2427 22.3837C9.61621 22.3837 8.23214 21.8129 7.09048 20.6712C5.94882 19.5296 5.37799 18.1455 5.37799 16.519V7.83928C5.37799 6.66634 5.78852 5.66934 6.60958 4.84828C7.43063 4.02723 8.42763 3.6167 9.60057 3.6167C10.7735 3.6167 11.7705 4.02723 12.5916 4.84828C13.4126 5.66934 13.8231 6.66634 13.8231 7.83928V16.0498C13.8231 16.7692 13.5729 17.3792 13.0725 17.8796C12.572 18.3801 11.9621 18.6303 11.2427 18.6303C10.5233 18.6303 9.91335 18.3801 9.4129 17.8796C8.91244 17.3792 8.66222 16.7692 8.66222 16.0498V7.3701H10.5389V16.0498C10.5389 16.2532 10.6054 16.4213 10.7383 16.5542C10.8713 16.6871 11.0394 16.7536 11.2427 16.7536C11.446 16.7536 11.6141 16.6871 11.747 16.5542C11.88 16.4213 11.9464 16.2532 11.9464 16.0498V7.83928C11.9308 7.18243 11.7001 6.62724 11.2544 6.1737C10.8087 5.72017 10.2574 5.4934 9.60057 5.4934C8.94372 5.4934 8.38853 5.72017 7.935 6.1737C7.48146 6.62724 7.25469 7.18243 7.25469 7.83928V16.519C7.23905 17.6294 7.62221 18.5717 8.40417 19.3458C9.18613 20.1199 10.1323 20.507 11.2427 20.507C12.3374 20.507 13.268 20.1199 14.0343 19.3458C14.8006 18.5717 15.1994 17.6294 15.2307 16.519V7.3701H17.1074V16.519Z"
                            fill="white" />
                    </svg>
                    <span class="workspace__header--title text-body-medium bold">Loading...</span>
                </a>
                <div class="workspace__header--links">
                    <button class="btn btn--secondary" id="inviteBtn">Invite</button>
                    <button class="btn btn--secondary" id="createFromTemplateBtn" style="display: none;">Use Template</button>
                    <button class="btn btn--primary" id="createFormBtn" style="display: none;">Create New Form</button>
                </div>
            </div>

            <div class="workspace__filter">
                <button class="workspace__filter-btn">
                    <span>Recent</span>
                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M4.92796 4.44951C4.96791 4.49671 5.04068 4.49671 5.08063 4.44951L8.84403 0.00268555H9.9084C9.99354 0.00268555 10.0397 0.102297 9.98473 0.167287L5.08063 5.96197C5.04068 6.00916 4.96791 6.00916 4.92796 5.96197L0.0238596 0.167287C-0.0311422 0.102297 0.0150517 0.00268555 0.100192 0.00268555H1.16456L4.92796 4.44951Z"
                            fill="#D9D9D9" />
                    </svg>
                </button>
            </div>

            <div class="workspace__search">
                <input type="text" name="workspace_search" id="workspace__search" class="workspace__search--input form__input" required
                    placeholder="Search forms">
            </div>

            <div class="workspace__forms workspace__forms--grid">
                <!-- Loading state -->
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
            </div>
        </div>
    </section>

    <!-- Create Form Popup -->
    <div id="createFormPopup" class="popup">
        <div class="popup__content">
            <span class="popup__close" id="closeFormPopupBtn">&times;</span>
            <h2 class="popup__title">Create New Form</h2>
            <form id="createFormForm" class="form">
                <div class="form__group">
                    <label class="form__label" for="formTitle">Form Title</label>
                    <input type="text" id="formTitle" name="formTitle" class="form__input" placeholder="Enter form title" required>
                </div>

                <div class="form__group">
                    <label class="form__label" for="formDescription">Form Description</label>
                    <textarea id="formDescription" name="formDescription" class="form__input" placeholder="Enter form description"></textarea>
                </div>

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">Create Form</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Popup -->
    <div id="templatesPopup" class="popup">
        <div class="popup__content">
            <span class="popup__close" id="closeTemplatesPopupBtn">&times;</span>
            <h2 class="popup__title">Choose a Template</h2>
            <div class="templates-grid">
                <div class="template-card" data-template="contact">
                    <h3>Contact Form</h3>
                    <p>Basic contact form with name, email, and message fields</p>
                </div>
                <div class="template-card" data-template="survey">
                    <h3>Survey Template</h3>
                    <p>Customer feedback survey with multiple choice questions</p>
                </div>
                <div class="template-card" data-template="registration">
                    <h3>Registration Form</h3>
                    <p>User registration form with personal details</p>
                </div>
                <div class="template-card" data-template="event">
                    <h3>Event Registration</h3>
                    <p>Event signup form with attendee information</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Invite Popup -->
    <div id="invitePopup" class="popup">
        <div class="popup__content">
            <span class="popup__close" id="closeInvitePopupBtn">&times;</span>
            <h2 class="popup__title">Invite Collaborators</h2>
            
            <form id="inviteForm" class="form">
                <div class="form__group">
                    <label class="form__label" for="inviteRole">Role</label>
                    <select id="inviteRole" name="role" class="form__input" required>
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                    <p class="form__help-text">
                        Members can view and edit forms. Admins can also manage workspace settings and members.
                    </p>
                </div>

                <div class="invite-link-container">
                    <input type="text" id="inviteLink" class="form__input" readonly 
                           placeholder="Click 'Generate New Link' to create an invite link">
                    <button type="button" id="copyInviteLink" class="btn btn--secondary" disabled>
                        <i class="fa-regular fa-copy"></i>
                        Copy
                    </button>
                    <button type="button" id="generateInviteLink" class="btn btn--primary">
                        <i class="fa-solid fa-rotate"></i>
                        Generate New Link
                    </button>
                </div>
                <p class="form__help-text">Share this link with people you want to invite to your workspace</p>
            </form>

            <div class="invite-list">
                <h3>Current Members</h3>
                <div id="collaboratorsList" class="collaborators-list">
                    <!-- Members will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="../public/js/workspace.js"></script>
</body>

</html>
