
DELETE FROM `user`;

INSERT INTO `user` (`id`, `name`, `email`, `group_id`) VALUES
(1, 'root', 'root@sylma.org', 2),
(2, 'admin', 'admin@sylma.org', 1),
(3, 'webmaster', 'webmaster@sylma.org', 0);

DELETE FROM `user7`;
DELETE FROM `group`;

INSERT INTO `group` (`id`, `name`, `url`) VALUES
(1, 'group01', 'http://sylma.org/groupe01'),
(2, 'group02', 'http://sylma.org/groupe02'),
(3, 'group03', 'http://sylma.org/groupe03');

TRUNCATE TABLE `user_group`;

INSERT INTO `user_group` (`id_user`, `id_group`) VALUES
(1, 1),
(1, 2),
(2, 1);
