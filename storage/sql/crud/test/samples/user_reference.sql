TRUNCATE TABLE `sylma_stepper_user01`;
DROP TABLE IF EXISTS `stepper_user_group`;
DELETE FROM `sylma_stepper_group01`;

INSERT INTO `sylma_stepper_group01` (
`id` ,
`name`
)
VALUES (
1 , 'green'
), (
2 , 'blue'
), (
3 , 'yellow'
);
