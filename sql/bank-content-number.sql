-- Apply once to an existing Bank Content database.
ALTER TABLE bc_items
  ADD COLUMN no BIGINT NOT NULL AUTO_INCREMENT FIRST,
  ADD UNIQUE KEY bc_items_no_unique (no);
