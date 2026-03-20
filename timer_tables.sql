CREATE TABLE IF NOT EXISTS ticket_timers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    total_seconds INT NOT NULL DEFAULT 0,
    is_running TINYINT(1) NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    stopped_permanently_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_ticket_timer (ticket_id),
    KEY idx_ticket_timer_user (user_id),
    CONSTRAINT fk_ticket_timers_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_timers_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS schedule_task_timers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_task_id INT NOT NULL,
    execution_date DATE NOT NULL,
    user_id INT NOT NULL,
    total_seconds INT NOT NULL DEFAULT 0,
    is_running TINYINT(1) NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_schedule_task_timer (schedule_task_id, execution_date),
    KEY idx_schedule_timer_user (user_id),
    CONSTRAINT fk_schedule_task_timers_task FOREIGN KEY (schedule_task_id) REFERENCES schedule_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_task_timers_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
