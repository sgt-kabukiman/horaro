SET NAMES utf8;

-- this creates the first and probably only op account; password is "operator" <- change it after your first login!
INSERT INTO `users` (`id`, `login`, `password`, `display_name`, `gravatar_hash`, `language`, `role`, `created_at`, `max_events`) VALUES
	(1, 'operator', '$2y$11$sItgjzvoNnRrnJw1zzKOde6n3.qwaBSAJDeu9Cg2EreFnxrf/AVVi', 'System Operator', NULL, 'en_us', 'ROLE_OP', '2014-10-11 04:20:00', 10);

INSERT INTO `config` (`key`, `value`) VALUES
	('bcrypt_cost',         '11'),
	('cookie_lifetime',     '86400'),
	('csrf_token_name',     '"_csrf_token"'),
	('default_language',    '"en_us"'),
	('default_event_theme', '"yeti"'),
	('featured_events',     '[]'),
	('max_events',          '10'),
	('max_schedule_items',  '200'),
	('max_schedules',       '10'),
	('max_users',           '50'),
	('sentry_dsn',          '""');
