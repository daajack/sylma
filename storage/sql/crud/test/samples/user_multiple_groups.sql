
SET foreign_key_checks = 0;

DELETE FROM `sylma_stepper_user02`;
DELETE FROM `sylma_stepper_group01`;
ALTER TABLE `sylma_stepper_user02` AUTO_INCREMENT = 1;

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

INSERT INTO `sylma_stepper_user02` (
`id` ,
`name`,
`group1`,
`group2`
)
VALUES (
1 , 'john', 1, 2
), (
2 , 'sam', 2, 1
), (
3 , 'dean', 3, 3
);
