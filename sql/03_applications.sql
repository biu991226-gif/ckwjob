CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    job_seeker_user_id INT UNSIGNED NOT NULL,
    status ENUM('applied', 'screening', 'rejected', 'accepted') NOT NULL DEFAULT 'applied',
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_applications_job_id
        FOREIGN KEY (job_id) REFERENCES jobs(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_applications_job_seeker_user_id
        FOREIGN KEY (job_seeker_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_applications_job_user (job_id, job_seeker_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
