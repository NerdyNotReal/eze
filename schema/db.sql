-- Database creation
DROP DATABASE IF EXISTS ezepze_db;
CREATE DATABASE ezepze_db;
USE ezepze_db;

-- Users Table (For storing basic user information)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,    -- Unique username for login
    email VARCHAR(100) UNIQUE NOT NULL,       -- User's email for notifications and recovery
    password_hash VARCHAR(255) NOT NULL,      -- Securely hashed password
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Workspaces Table (For organizing forms)
CREATE TABLE workspaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,              -- Workspace name
    description TEXT,                        -- Workspace description
    owner_id INT NOT NULL,                   -- Owner of the workspace
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- Forms Table (Stores forms created by users)
CREATE TABLE forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,               -- Workspace the form belongs to
    title VARCHAR(255) NOT NULL,             -- Form title
    description TEXT,                        -- Description for the form
    owner_id INT NOT NULL,                   -- Owner of the form (user ID)
    status ENUM('draft', 'published') DEFAULT 'draft',  -- Form status
    is_public BOOLEAN DEFAULT FALSE,         -- Whether form is public
    theme_settings JSON,                     -- Custom styling for the entire form
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id),
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);

-- Workspace Users Table (Manages access to workspaces)
CREATE TABLE workspace_users (
    workspace_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'admin', 'member', 'viewer') DEFAULT 'member',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (workspace_id, user_id),
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Workspace Invite Links Table
CREATE TABLE workspace_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    role ENUM('viewer', 'member', 'admin') DEFAULT 'member',
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    used_at DATETIME,
    used_by INT,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Form Collaboration Table (Manages access to forms by collaborators)
CREATE TABLE form_collaborators (
    form_id INT NOT NULL,                     -- Form ID
    user_id INT NOT NULL,                     -- Collaborator's user ID
    role ENUM('viewer', 'editor', 'admin') DEFAULT 'viewer',  -- Access level
    invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- When the user was invited
    accepted_at DATETIME,                     -- When the user accepted the invitation
    PRIMARY KEY (form_id, user_id),
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Form Elements Table (Stores form fields/elements for each form)
CREATE TABLE form_elements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,                     -- Associated form ID
    element_type VARCHAR(50) NOT NULL,        -- Type of element (text, checkbox, etc.)
    element_data JSON NOT NULL,               -- Stores all element properties as JSON
    position INT NOT NULL DEFAULT 0,          -- Order of elements within the form
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

-- Files Table (For storing uploaded files)
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_element_id INT NOT NULL,              -- Foreign key linking to the form element
    file_name VARCHAR(255) NOT NULL,           -- Original file name
    file_path VARCHAR(255) NOT NULL,           -- Storage path
    file_size INT NOT NULL,                    -- Size in bytes
    file_type VARCHAR(50) NOT NULL,            -- MIME type
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_element_id) REFERENCES form_elements(id) ON DELETE CASCADE
);

-- Form Responses Table (Tracks submissions for forms)
CREATE TABLE form_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,                     -- Associated form ID
    respondent_id INT,                        -- User who responded (NULL for anonymous)
    response_data JSON NOT NULL,              -- Stores all responses as JSON
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (respondent_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create indexes for better query performance
CREATE INDEX idx_form_elements_form_id ON form_elements(form_id);
CREATE INDEX idx_form_responses_form_id ON form_responses(form_id);
CREATE INDEX idx_files_form_element_id ON files(form_element_id);
CREATE INDEX idx_workspace_users_user_id ON workspace_users(user_id);
CREATE INDEX idx_form_collaborators_user_id ON form_collaborators(user_id);

-- Add full-text search capabilities
ALTER TABLE forms ADD FULLTEXT(title, description);
ALTER TABLE form_elements ADD FULLTEXT(element_type);
