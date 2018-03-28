SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE `city_user`;
TRUNCATE TABLE `country_user`;
TRUNCATE TABLE `lang_user`;
DELETE FROM `user_duplicate_refs`;
SET FOREIGN_KEY_CHECKS=1;

INSERT INTO `user_duplicate_refs` (
`id` ,
`name` 
)
VALUES (
1 , 'alexandra'
), (
2 , 'faith'
), (
3 , 'isaac'
);

INSERT INTO `city_user` (
`id` ,
`name` ,
`user`,
`lang`
)
VALUES (
1 , 'moscow', 2, 5
), (
2 , 'san francisco', 1, 6
), (
3 , 'paris', 3, 4
);

INSERT INTO `country_user` (
`id` ,
`name` ,
`user`,
`lang`
)
VALUES (
1 , 'france', 3, 1
), (
2 , 'russia', 2, 2
), (
3 , 'usa', 1, 3
);

INSERT INTO `lang_user` (
`id` ,
`name` 
)
VALUES (
1 , 'fr'
), (
2 , 'ru'
), (
3 , 'en'
), (
4 , 'fr-local'
), (
5 , 'ru-local'
), (
6 , 'en-local'
);
