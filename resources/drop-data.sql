SET @old_foreign_key_checks = @@foreign_key_checks, foreign_key_checks = 0;

DROP TABLE users;
DROP TABLE teams;
DROP TABLE users_teams;
DROP TABLE events;
DROP TABLE schedules;
DROP TABLE schedule_items;
DROP TABLE schedule_columns;
DROP TABLE sessions;

SET foreign_key_checks = @old_foreign_key_checks;
