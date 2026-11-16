-- Apply to an existing MySQL database created from the GymFinder schema.
-- SQLite installations are migrated automatically by backend/db.php.
ALTER TABLE trainer_specialties
  DROP FOREIGN KEY fk_ts_trainer,
  DROP FOREIGN KEY fk_ts_specialty,
  ADD CONSTRAINT fk_ts_trainer
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ts_specialty
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE RESTRICT;

ALTER TABLE gym_trainers
  DROP FOREIGN KEY fk_gt_gym,
  DROP FOREIGN KEY fk_gt_trainer,
  ADD CONSTRAINT fk_gt_gym
    FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_gt_trainer
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE RESTRICT;
