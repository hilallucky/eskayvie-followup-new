UPDATE monthly_target_entries SET target_income = ROUND(target_income) WHERE target_income IS NOT NULL;
ALTER TABLE monthly_target_entries MODIFY target_income BIGINT UNSIGNED NULL;