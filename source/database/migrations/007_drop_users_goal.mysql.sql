-- Remove the retired user goal field, if this legacy column exists.
SET @gf_drop_goal_sql = IF(
  (SELECT COUNT(*)
   FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name = 'users'
     AND column_name = 'goal') > 0,
  'ALTER TABLE users DROP COLUMN goal',
  'SELECT 1'
);
PREPARE gf_drop_goal_stmt FROM @gf_drop_goal_sql;
EXECUTE gf_drop_goal_stmt;
DEALLOCATE PREPARE gf_drop_goal_stmt;
