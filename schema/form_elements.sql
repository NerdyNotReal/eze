CREATE TABLE IF NOT EXISTS form_elements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    element_type VARCHAR(50) NOT NULL,
    element_data JSON NOT NULL,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 