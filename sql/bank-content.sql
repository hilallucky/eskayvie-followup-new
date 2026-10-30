ALTER TABLE users
  ADD COLUMN content_role ENUM('Member','Supervisor','Administrator') NOT NULL DEFAULT 'Member' AFTER status;

UPDATE users u
JOIN members m ON m.member_code = u.member_code
LEFT JOIN ranks r ON r.rank_code = m.rank_code
SET u.content_role = 'Supervisor'
WHERE r.rank_name = 'Supervisor' AND u.content_role = 'Member';

CREATE TABLE bc_items (
  no BIGINT NOT NULL AUTO_INCREMENT,
  id CHAR(36) NOT NULL,
  parent_id CHAR(36) DEFAULT NULL,
  name VARCHAR(180) NOT NULL,
  kind ENUM('folder','file','link') NOT NULL,
  mime_type VARCHAR(255) DEFAULT NULL,
  size BIGINT UNSIGNED DEFAULT NULL,
  modified_at VARCHAR(30) NOT NULL,
  owner VARCHAR(120) NOT NULL,
  status ENUM('Approved','In review','Ready') NOT NULL DEFAULT 'Approved',
  starred TINYINT(1) NOT NULL DEFAULT 0,
  shared TINYINT(1) NOT NULL DEFAULT 0,
  stored_name VARCHAR(255) DEFAULT NULL,
  url VARCHAR(2048) DEFAULT NULL,
  thumbnail_url VARCHAR(2048) DEFAULT NULL,
  description VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY bc_items_no_unique (no),
  UNIQUE KEY items_parent_name_idx (parent_id, name),
  KEY items_modified_idx (modified_at),
  KEY items_starred_idx (starred),
  KEY items_shared_idx (shared),
  CONSTRAINT items_parent_fk FOREIGN KEY (parent_id) REFERENCES bc_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bc_notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  item_id CHAR(36) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY bc_notifications_user_created_idx (user_id, created_at),
  KEY bc_notifications_user_read_idx (user_id, read_at),
  KEY bc_notifications_item_fk (item_id),
  CONSTRAINT bc_notifications_item_fk FOREIGN KEY (item_id) REFERENCES bc_items (id) ON DELETE CASCADE,
  CONSTRAINT bc_notifications_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO bc_items (id, parent_id, name, kind, modified_at, owner, status) VALUES
  ('00000000-0000-4000-8000-000000000001', NULL, 'Accounts', 'folder', '2026-10-14T13:30:12Z', 'System', 'Approved'),
  ('00000000-0000-4000-8000-000000000002', NULL, 'Lending', 'folder', '2026-10-14T13:30:12Z', 'System', 'Approved'),
  ('00000000-0000-4000-8000-000000000003', NULL, 'Compliance', 'folder', '2026-10-14T13:30:12Z', 'System', 'Approved'),
  ('00000000-0000-4000-8000-000000000004', NULL, 'Marketing', 'folder', '2026-10-14T13:30:12Z', 'System', 'Approved'),
  ('00000000-0000-4000-8000-000000000005', '00000000-0000-4000-8000-000000000002', 'test', 'folder', '2026-10-14T13:30:12Z', 'System', 'Ready');

INSERT INTO bc_items (id, parent_id, name, kind, mime_type, size, modified_at, owner, status, starred, shared) VALUES
  ('00000000-0000-4000-8000-000000000101', '00000000-0000-4000-8000-000000000001', 'Quarterly account statement — Q3 2026.pdf', 'file', 'application/pdf', 1887436, '2026-10-15T13:30:12.041000Z', 'Alex Morgan', 'Approved', 1, 0),
  ('00000000-0000-4000-8000-000000000102', '00000000-0000-4000-8000-000000000001', 'Interest rates — October 2026.xlsx', 'file', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 700416, '2026-10-10T13:30:12.041000Z', 'Taylor Reed', 'Approved', 0, 0),
  ('00000000-0000-4000-8000-000000000103', '00000000-0000-4000-8000-000000000001', 'Monthly transaction summary.csv', 'file', 'text/csv', 421888, '2026-10-07T13:30:12.041000Z', 'Jordan Lee', 'Approved', 0, 0),
  ('00000000-0000-4000-8000-000000000104', '00000000-0000-4000-8000-000000000001', 'Customer onboarding checklist.docx', 'file', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 253952, '2026-10-14T13:30:12.041000Z', 'Priya Shah', 'Approved', 0, 1),
  ('00000000-0000-4000-8000-000000000105', '00000000-0000-4000-8000-000000000002', 'Mortgage application guide.pdf', 'file', 'application/pdf', 2202010, '2026-10-14T13:30:12.041000Z', 'Jordan Lee', 'Approved', 0, 0),
  ('00000000-0000-4000-8000-000000000106', '00000000-0000-4000-8000-000000000002', 'Loan servicing procedures.docx', 'file', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 546816, '2026-10-11T13:30:12.041000Z', 'Alex Morgan', 'In review', 0, 0),
  ('00000000-0000-4000-8000-000000000107', '00000000-0000-4000-8000-000000000002', 'Small business lending criteria.xlsx', 'file', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1468006, '2026-10-09T13:30:12.041000Z', 'Priya Shah', 'Approved', 0, 0),
  ('00000000-0000-4000-8000-000000000108', '00000000-0000-4000-8000-000000000003', 'Customer data handling policy.pdf', 'file', 'application/pdf', 942080, '2026-10-12T13:30:12.041000Z', 'Priya Shah', 'Approved', 1, 0),
  ('00000000-0000-4000-8000-000000000109', '00000000-0000-4000-8000-000000000003', 'Anti-money laundering overview.pdf', 'file', 'application/pdf', 1258291, '2026-10-10T13:30:12.041000Z', 'Priya Shah', 'Approved', 0, 1),
  ('00000000-0000-4000-8000-000000000110', '00000000-0000-4000-8000-000000000003', 'Annual compliance training.docx', 'file', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 333824, '2026-10-07T13:30:12.041000Z', 'Alex Morgan', 'In review', 0, 0),
  ('00000000-0000-4000-8000-000000000111', '00000000-0000-4000-8000-000000000004', 'New checking account campaign.pptx', 'file', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 5872640, '2026-10-09T13:30:12.041000Z', 'Taylor Reed', 'Approved', 0, 0),
  ('00000000-0000-4000-8000-000000000112', '00000000-0000-4000-8000-000000000004', 'Brand guidelines 2026.pdf', 'file', 'application/pdf', 3565158, '2026-10-08T13:30:12.041000Z', 'Taylor Reed', 'Approved', 0, 0),
  ('00000000-0000-4000-8000-000000000113', '00000000-0000-4000-8000-000000000004', 'October newsletter draft.docx', 'file', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 798720, '2026-10-06T13:30:12.041000Z', 'Jordan Lee', 'In review', 0, 0),
  ('00000000-0000-4000-8000-000000000114', '00000000-0000-4000-8000-000000000001', 'Wire transfer guide.pdf', 'file', 'application/pdf', 1153433, '2026-10-09T13:30:12.041000Z', 'Alex Morgan', 'In review', 0, 0),
  ('00000000-0000-4000-8000-000000000115', '00000000-0000-4000-8000-000000000001', 'Branch performance report.xlsx', 'file', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 3355443, '2026-10-13T13:30:12.041000Z', 'Jordan Lee', 'In review', 0, 0);
