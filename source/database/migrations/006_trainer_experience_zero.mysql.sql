-- Align the database CHECK with the specification: trainer experience is 0..60 years.
USE gymfinder;

ALTER TABLE trainers
  DROP CHECK chk_trainers_exp,
  ADD CONSTRAINT chk_trainers_exp CHECK (years_experience BETWEEN 0 AND 60);
