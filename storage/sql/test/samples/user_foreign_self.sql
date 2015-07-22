
INSERT INTO user_foreign_self (
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

INSERT INTO user_user (
`id_user_foreign_self_source` ,
`id_user_foreign_self_target`
)
VALUES (
1 , 2
), (
1 , 3
), (
3 , 1
), (
3 , 3
);


