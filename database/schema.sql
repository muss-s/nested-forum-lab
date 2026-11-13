-- Nested forum lab schema (MySQL 8 / MariaDB). Seed users are fictional and have NULL
-- password hashes, so they cannot log in. Register a new account to post.
CREATE DATABASE IF NOT EXISTS nested_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nested_forum;

DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS discussions;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  use_gravatar TINYINT(1) NOT NULL DEFAULT 0, -- explicit opt-in only
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE discussions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  discussion_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,           -- NULL = top-level comment
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_comment_discussion (discussion_id, id),
  KEY idx_parent (discussion_id, parent_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE,
  -- composite FK: a parent must live in the SAME discussion (NULL parent skips the check)
  FOREIGN KEY (discussion_id, parent_id) REFERENCES comments(discussion_id, id)
) ENGINE=InnoDB;

INSERT INTO users (id, username, email, password_hash) VALUES
 (1,'alice','alice@example.test',NULL),(2,'ben','ben@example.test',NULL),
 (3,'cara','cara@example.test',NULL),(4,'dee','dee@example.test',NULL),
 (5,'eli','eli@example.test',NULL);
INSERT INTO discussions (id, user_id, title, body) VALUES
 (1,1,'How do I learn PHP?','Share your learning tips.'),
 (2,2,'Second topic (for cross-discussion tests)','Used to test that a parent from another discussion is rejected.');
INSERT INTO comments (id, discussion_id, parent_id, user_id, body) VALUES
 (1,1,NULL,1,'Start with the PHP manual.'),
 (2,1,1,2,'Try the examples as you read.'),
 (3,1,2,3,'Build a small project too.'),
 (4,1,1,4,'Which manual section first?'),
 (5,1,NULL,5,'Practice SQL as well.'),
 (6,1,3,2,'Start with a forum.'),
 (7,1,6,5,'Add replies next.');
