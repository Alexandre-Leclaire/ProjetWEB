CREATE TABLE EQUIPE (
    id INT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    image_url VARCHAR(250),
    winrate INT,
    classement INT
);

CREATE TABLE `MATCH` (
    id INT PRIMARY KEY,
    equipe1_id INT NOT NULL,
    equipe2_id INT NOT NULL,
    score_equipe1 INT,
    score_equipe2 INT,
    match_timestamp DATETIME NOT NULL,
    journee INT NOT NULL,
    status ENUM('READY', 'ONGOING', 'FINISHED') NOT NULL,
    date_update DATETIME,

    FOREIGN KEY (equipe1_id) REFERENCES EQUIPE(id),
    FOREIGN KEY (equipe2_id) REFERENCES EQUIPE(id)
);

CREATE TABLE USER (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(250) NOT NULL,
    username VARCHAR(20) NOT NULL,
    password VARCHAR(55) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    is_banned BOOLEAN DEFAULT FALSE,
    mail_token VARCHAR(250),
    date_creation DATETIME NOT NULL,
    date_update DATETIME
);

CREATE TABLE PARI (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    equipe_id INT NOT NULL,
    score_ecart INT,
    score_exact_winner INT,
    score_exact_looser INT,
    date_update DATETIME,

    FOREIGN KEY (user_id) REFERENCES USER(id),
    FOREIGN KEY (match_id) REFERENCES `MATCH`(id),
    FOREIGN KEY (equipe_id) REFERENCES EQUIPE(id)
);

CREATE TABLE `GROUPE` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(250) NOT NULL,
    date_creation DATETIME NOT NULL
);

CREATE TABLE USER_GROUPE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    is_banned BOOLEAN DEFAULT FALSE,
    date_update DATETIME,

    FOREIGN KEY (group_id) REFERENCES GROUPE(id),
    FOREIGN KEY (user_id) REFERENCES USER(id)
);

CREATE TABLE MESSAGE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    content VARCHAR(250) NOT NULL,
    date_creation DATETIME NOT NULL,
    date_update DATETIME,

    FOREIGN KEY (user_id) REFERENCES USER(id),
    FOREIGN KEY (group_id) REFERENCES GROUPE(id)
);