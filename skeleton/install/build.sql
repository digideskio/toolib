DROP TABLE IF EXISTS `users`;

-- Create users
create table `users` (
    `username` varchar(50) not null,
    `password` varchar(40) not null,
    `enabled` int(1) not null,
    primary key(`username`)
) DEFAULT CHARSET='UTF8';

INSERT INTO `users` (`username`, `password`) values ('root', sha1('root'));

