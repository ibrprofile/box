-- Kayford (Кайфорд) — образовательная платформа для подготовки к ЕГЭ
-- Схема базы данных. MySQL 5.7+ / MariaDB 10.3+, кодировка utf8mb4.

SET NAMES utf8mb4;

-- Пользователи ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name      VARCHAR(80)  NOT NULL,
    last_name       VARCHAR(80)  NOT NULL,
    grade           TINYINT UNSIGNED NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NULL,           -- используется только для входа без passkey
    role            ENUM('student','manager','admin','superadmin') NOT NULL DEFAULT 'student',
    status          ENUM('active','blocked') NOT NULL DEFAULT 'active',
    xp              INT UNSIGNED NOT NULL DEFAULT 0,
    level           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    streak_count    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    streak_best     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    streak_day      DATE NULL,
    avatar_hue      SMALLINT UNSIGNED NOT NULL DEFAULT 260,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at    DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_xp (xp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Passkey / WebAuthn -------------------------------------------------------
CREATE TABLE IF NOT EXISTS webauthn_credentials (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    credential_id   VARCHAR(255) NOT NULL,      -- base64url
    public_key      TEXT NOT NULL,              -- PEM
    sign_count      INT UNSIGNED NOT NULL DEFAULT 0,
    label           VARCHAR(120) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at    DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cred (credential_id),
    KEY idx_cred_user (user_id),
    CONSTRAINT fk_cred_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Предметы ЕГЭ -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS subjects (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(60) NOT NULL,
    name        VARCHAR(120) NOT NULL,
    icon        VARCHAR(16) NOT NULL DEFAULT '',
    hue         SMALLINT UNSIGNED NOT NULL DEFAULT 260,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subject_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Темы внутри предмета -----------------------------------------------------
CREATE TABLE IF NOT EXISTS topics (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(160) NOT NULL,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_topic_subject (subject_id),
    CONSTRAINT fk_topic_subject FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Задачи каталога ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_id     INT UNSIGNED NOT NULL,
    topic_id       INT UNSIGNED NULL,
    title          VARCHAR(200) NOT NULL,
    statement      MEDIUMTEXT NOT NULL,          -- HTML (редактор)
    answer_type    ENUM('text','choice') NOT NULL DEFAULT 'text',
    correct_answer VARCHAR(500) NOT NULL,        -- для text; для choice — индекс(ы) через запятую
    options        JSON NULL,                    -- для choice: массив вариантов
    solution       MEDIUMTEXT NULL,              -- HTML разбор
    difficulty     TINYINT UNSIGNED NOT NULL DEFAULT 2,   -- 1..5
    xp_reward      SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_by     INT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_task_subject (subject_id, is_active),
    KEY idx_task_topic (topic_id),
    CONSTRAINT fk_task_subject FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE,
    CONSTRAINT fk_task_topic FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Попытки по задачам -------------------------------------------------------
CREATE TABLE IF NOT EXISTS task_attempts (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    task_id       INT UNSIGNED NOT NULL,
    user_answer   VARCHAR(500) NOT NULL,
    is_correct    TINYINT(1) NOT NULL,
    time_spent_ms INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempt_user (user_id, created_at),
    KEY idx_attempt_task (task_id),
    CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_attempt_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Персональная статистика по задаче (для умного подбора) -------------------
CREATE TABLE IF NOT EXISTS user_task_stats (
    user_id      INT UNSIGNED NOT NULL,
    task_id      INT UNSIGNED NOT NULL,
    attempts     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    correct      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    wrong        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    weight       FLOAT NOT NULL DEFAULT 1.0,     -- вес показа: растёт при ошибках
    solved       TINYINT(1) NOT NULL DEFAULT 0,
    last_seen_at DATETIME NULL,
    PRIMARY KEY (user_id, task_id),
    KEY idx_uts_user (user_id, weight),
    CONSTRAINT fk_uts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_uts_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Тренажёр ударений: слова -------------------------------------------------
CREATE TABLE IF NOT EXISTS stress_words (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    word           VARCHAR(80) NOT NULL,
    stress_index   TINYINT UNSIGNED NOT NULL,     -- индекс ударной буквы (0-based)
    difficulty     TINYINT UNSIGNED NOT NULL DEFAULT 2,
    hint           VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_word (word)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_word_stats (
    user_id      INT UNSIGNED NOT NULL,
    word_id      INT UNSIGNED NOT NULL,
    attempts     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    wrong        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    streak       SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- подряд верных
    weight       FLOAT NOT NULL DEFAULT 1.0,
    last_seen_at DATETIME NULL,
    PRIMARY KEY (user_id, word_id),
    KEY idx_uws_user (user_id, weight),
    CONSTRAINT fk_uws_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_uws_word FOREIGN KEY (word_id) REFERENCES stress_words (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Достижения ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS achievements (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code           VARCHAR(60) NOT NULL,
    title          VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NOT NULL,
    icon           VARCHAR(16) NOT NULL DEFAULT '',
    xp_reward      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    metric         ENUM('tasks_solved','words_correct','streak_days','level','perfect_run') NOT NULL,
    threshold      INT UNSIGNED NOT NULL,
    sort_order     SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ach_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_achievements (
    user_id        INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    unlocked_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, achievement_id),
    CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_ua_ach FOREIGN KEY (achievement_id) REFERENCES achievements (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Чат с менеджером ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_threads (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED NOT NULL,
    status         ENUM('open','closed') NOT NULL DEFAULT 'open',
    last_message_at DATETIME NULL,
    unread_user    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    unread_staff   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    assigned_to    INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_thread_user (user_id),
    CONSTRAINT fk_thread_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    thread_id    INT UNSIGNED NOT NULL,
    sender_type  ENUM('user','staff') NOT NULL,
    sender_id    INT UNSIGNED NOT NULL,
    body         TEXT NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_thread (thread_id, id),
    CONSTRAINT fk_msg_thread FOREIGN KEY (thread_id) REFERENCES chat_threads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Расширение групповых чатов ----------------------------------------------
CREATE TABLE IF NOT EXISTS chat_groups (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id      INT UNSIGNED NOT NULL,
    name          VARCHAR(120) NOT NULL,
    description   VARCHAR(500) NULL,
    avatar_path   VARCHAR(255) NULL,
    visibility    ENUM('private','discoverable') NOT NULL DEFAULT 'private',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_group_owner (owner_id),
    KEY idx_group_visibility (visibility, updated_at),
    CONSTRAINT fk_group_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_group_members (
    group_id      INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    role          ENUM('owner','admin','member') NOT NULL DEFAULT 'member',
    joined_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_read_id  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (group_id, user_id),
    KEY idx_member_user (user_id, joined_at),
    CONSTRAINT fk_member_group FOREIGN KEY (group_id) REFERENCES chat_groups (id) ON DELETE CASCADE,
    CONSTRAINT fk_member_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_group_messages (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    group_id       INT UNSIGNED NOT NULL,
    sender_id      INT UNSIGNED NOT NULL,
    body           TEXT NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_at      DATETIME NULL,
    deleted_at     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_group_message (group_id, id),
    CONSTRAINT fk_group_message_group FOREIGN KEY (group_id) REFERENCES chat_groups (id) ON DELETE CASCADE,
    CONSTRAINT fk_group_message_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_attachments (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id     BIGINT UNSIGNED NOT NULL,
    storage_key    VARCHAR(255) NOT NULL,
    original_name  VARCHAR(255) NOT NULL,
    mime_type      VARCHAR(120) NOT NULL,
    size_bytes     BIGINT UNSIGNED NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attachment_message (message_id),
    CONSTRAINT fk_attachment_message FOREIGN KEY (message_id) REFERENCES chat_group_messages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
