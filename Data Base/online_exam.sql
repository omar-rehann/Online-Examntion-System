-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2025 at 02:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Online_Exam`
--

DELIMITER $$

-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `checkAnswer` (`resID` INT, `qID` INT) RETURNS TINYINT(1)  BEGIN
    DECLARE RES INT;
    DECLARE questionType INT;

    -- Get the question type
    SELECT type INTO questionType FROM question WHERE id = qID;

    -- Check based on question type
    IF (questionType = 0 OR questionType = 3) THEN
        -- For multiple-choice or multiple-answer questions
        SELECT COUNT(*) INTO RES
        FROM (
            SELECT answerID
            FROM result_answers ra
            WHERE resultID = resID AND questionID = qID
              AND answerID IN (
                  SELECT id
                  FROM question_answers
                  WHERE isCorrect AND questionID = ra.questionID
              )
        ) AS t
        HAVING COUNT(*) = (
            SELECT COUNT(*)
            FROM question_answers
            WHERE questionID = qID AND isCorrect
        );

        IF RES > 0 THEN
            RETURN TRUE;
        ELSE
            RETURN FALSE;
        END IF;
    ELSEIF (questionType = 2) THEN
        -- For text-based questions
        SELECT COUNT(*) INTO RES
        FROM result_answers ra
        WHERE resultID = resID AND questionID = qID
          AND textAnswer IN (
              SELECT answer
              FROM question_answers
              WHERE questionID = ra.questionID
          );

        IF RES > 0 THEN
            RETURN TRUE;
        ELSE
            RETURN FALSE;
        END IF;
    ELSE
        -- For unsupported question types
        RETURN FALSE;
    END IF;
END$$


CREATE DEFINER=`root`@`localhost` FUNCTION `getQuestionRightAnswers` (`qid` INT) RETURNS VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci  BEGIN
DECLARE C VARCHAR(255);
DECLARE qtype INT;
SET qtype = (select type from question where id = qid);
IF (qtype = 1) THEN
SELECT 'True' INTO C FROM question WHERE id = qID AND isTrue = 1;
	IF C IS NULL THEN
	SET C = 'False';
	END IF;
ELSEIF (qtype = 2) THEN
SELECT GROUP_CONCAT(answer SEPARATOR ', ') into C FROM question_answers
WHERE questionID = qid
GROUP BY questionID;

ELSEIF (qtype = 4) THEN
SELECT GROUP_CONCAT(CONCAT(answer, ' => ', matchAnswer) ORDER BY id SEPARATOR ', ') into C FROM question_answers
WHERE questionID = qid
GROUP BY questionID;
ELSE
SELECT GROUP_CONCAT(answer SEPARATOR ', ') into C FROM question_answers
WHERE questionID = qid AND isCorrect
GROUP BY questionID;
END IF;
RETURN C;

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `getQuestionsInTest` (`tID` INT) RETURNS INT(11)  BEGIN
   DECLARE C INT(11);
   SELECT count(*) FROM tests_has_questions WHERE testID = tID INTO C;
   IF (C IS NULL) THEN
      SET C = 0;
   END IF;

   RETURN C;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `getResultGivenAnswers` (`rid` INT, `qid` INT) RETURNS VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci  BEGIN
DECLARE C VARCHAR(255);
DECLARE qtype INT;
SET qtype = (select type from question where id = qID);
IF (qtype = 1) THEN
	SELECT "True" INTO C FROM result_answers WHERE questionID = qid AND resultID = rid AND isTrue = 1;

	SELECT "False" INTO C FROM result_answers WHERE questionID = qid AND resultID = rid AND isTrue = 0;
ELSEIF (qtype = 4) THEN 
SELECT GROUP_CONCAT(CONCAT(answer, ' => ', textAnswer) ORDER BY a.id SEPARATOR ', ') INTO C FROM result_answers ra
INNER JOIN question_answers a
ON a.id = ra.answerID
WHERE ra.questionID = qid AND ra.resultID = rid;
ELSEIF (qtype = 2 || qtype = 5) THEN 
SELECT textAnswer INTO C FROM result_answers WHERE questionID = qid AND resultID = rid;
ELSE
SELECT GROUP_CONCAT(answer SEPARATOR ', ') INTO C FROM result_answers ra
INNER JOIN question_answers a
ON a.id = ra.answerID
WHERE ra.questionID = qid AND ra.resultID = rid;
END IF;
RETURN C;

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `getResultGrade` (`rid` INT) RETURNS INT(11)  BEGIN
DECLARE C INT(11);
SELECT SUM(points) INTO C
FROM (
SELECT CASE (SELECT type from question where id = questionID) WHEN 4 THEN
(SELECT SUM(points) FROM question_answers qa WHERE qa.questionID = ra.questionID) 
ELSE 
(SELECT SUM(points) FROM question q WHERE q.id = ra.questionID) 
END AS points from result_answers ra where resultID = rid and isCorrect GROUP BY questionID) as t;


   IF (C IS NULL) THEN
      SET C = 0;
   END IF;


RETURN C;

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `getResultMaxGrade` (`rid` INT) RETURNS INT(11)  BEGIN
DECLARE C INT(11);
SELECT SUM(points) INTO C
FROM (SELECT CASE (SELECT type FROM question WHERE id = ra.questionID) 
WHEN 4 THEN
(SELECT SUM(points) FROM question_answers WHERE questionID = ra.questionID) 
ELSE 
(SELECT SUM(points) FROM question q WHERE q.id = ra.questionID) 
END points
FROM result_answers ra
WHERE resultID = rid
GROUP BY questionID) AS T;
   IF (C IS NULL) THEN
      SET C = 0;
   END IF;


RETURN C;

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `getTestGrade` (`test_id` INT) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    DECLARE total_grade DECIMAL(10,2);
    
    SELECT SUM(points) INTO total_grade 
    FROM result_answers 
    WHERE resultID IN (SELECT id FROM result WHERE testID = test_id);
    
    RETURN IFNULL(total_grade, 0);
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `Result_CorrectQuestions` (`rid` INT) RETURNS INT(11)  BEGIN
DECLARE C INT(11);
select count(*) INTO C from (select questionID from result_answers where resultID = rid  GROUP BY questionID 
HAVING CASE (SELECT type from question where id = questionID) WHEN 4 THEN 
MAX(isCorrect) = 1 ELSE MIN(isCorrect) = 1 END) t;
IF (C IS NULL) THEN
      SET C = 0;
   END IF;

RETURN C;

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `Result_WrongQuestions` (`rid` INT) RETURNS INT(11)  BEGIN
DECLARE C INT(11);
select count(*) INTO C from (
select questionID from result_answers where resultID = rid  GROUP BY questionID 
HAVING CASE (SELECT type from question where id = questionID) WHEN 4 THEN 
MAX(isCorrect) = 0 ELSE MIN(isCorrect) = 0 END) t;
IF (C IS NULL) THEN
      SET C = 0;
   END IF;
RETURN C;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `parent` int(11) DEFAULT NULL,
  `instructorID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`id`, `name`, `parent`, `instructorID`) VALUES
(70, 'c++', NULL, 31),
(71, 'quiz', 70, 31);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `assignedTest` int(11) DEFAULT NULL,
  `settingID` int(11) DEFAULT NULL,
  `instructorID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `assignedTest`, `settingID`, `instructorID`) VALUES
(13, 'C', 42, 82, 31);

-- --------------------------------------------------------

--
-- Table structure for table `groups_has_students`
--

CREATE TABLE `groups_has_students` (
  `groupID` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `joinDate` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `groups_has_students`
--

INSERT INTO `groups_has_students` (`groupID`, `studentID`, `joinDate`) VALUES
(13, 201234567, '2025-04-13 23:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `instructor`
--

CREATE TABLE `instructor` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(100) NOT NULL,
  `phone` varchar(13) NOT NULL,
  `password_token` varchar(100) DEFAULT NULL,
  `token_expire` timestamp NULL DEFAULT NULL,
  `suspended` int(11) NOT NULL DEFAULT 0,
  `isAdmin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `instructor`
--

INSERT INTO `instructor` (`id`, `name`, `email`, `password`, `phone`, `password_token`, `token_expire`, `suspended`, `isAdmin`) VALUES
(7, 'System Administrator', 'admin@gmail.com', '21232f297a57a5a743894a0e4a801fc3', '', NULL, NULL, 0, 1),
(31, 'jamal jado', 'jamal@gmail.com', '7592628bce37dd14e0b36ea66d5ba847', '01276612119', NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mails`
--

CREATE TABLE `mails` (
  `id` int(11) NOT NULL,
  `resultID` int(11) DEFAULT NULL,
  `studentID` int(11) DEFAULT NULL,
  `instructorID` int(11) DEFAULT NULL,
  `sends_at` timestamp NULL DEFAULT NULL,
  `sent` tinyint(1) DEFAULT 0,
  `type` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `mails`
--

INSERT INTO `mails` (`id`, `resultID`, `studentID`, `instructorID`, `sends_at`, `sent`, `type`) VALUES
(10, 39, NULL, NULL, '2025-04-13 21:46:58', 0, 3),
(11, 39, NULL, NULL, '2025-04-13 21:51:39', 0, 2),
(12, 40, NULL, NULL, '2025-04-13 22:18:20', 0, 2),
(13, 40, NULL, NULL, '2025-04-13 22:18:20', 0, 3),
(14, 41, NULL, NULL, '2025-04-13 22:21:15', 0, 2),
(15, 41, NULL, NULL, '2025-04-13 22:21:15', 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `id` int(11) NOT NULL,
  `question` varchar(2000) DEFAULT NULL,
  `type` int(1) DEFAULT NULL COMMENT '0 - MCQ / 1 - T/F /2- COMPLETE/',
  `points` int(11) NOT NULL DEFAULT 1,
  `difficulty` tinyint(1) DEFAULT 1,
  `isTrue` tinyint(1) NOT NULL DEFAULT 1,
  `instructorID` int(11) NOT NULL,
  `courseID` int(11) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`id`, `question`, `type`, `points`, `difficulty`, `isTrue`, `instructorID`, `courseID`, `deleted`) VALUES
(188, 'Complete', 2, 3, 2, 0, 31, 71, 1),
(189, 'True / False', 1, 3, 2, 0, 31, 71, 1),
(190, 'Match The Following', 4, 2, 3, 0, 31, 71, 1),
(191, 'What is the….?', 5, 1, 2, 0, 31, 71, 1),
(192, 'pointer refer to value ?', 1, 5, 1, 0, 31, 71, 1),
(193, 'define omar rehan', 1, 5, 1, 0, 31, 71, 1),
(194, 'Complete', 2, 3, 2, 0, 31, 71, 1),
(195, 'True / False', 1, 3, 2, 0, 31, 71, 1),
(196, 'Match The Following', 4, 2, 3, 0, 31, 71, 1),
(197, 'What is the….?', 5, 1, 2, 0, 31, 71, 1),
(198, 'pointer refer to value ?', 1, 5, 1, 0, 31, 71, 0),
(199, 'define omar rehan', 1, 5, 1, 0, 31, 71, 1),
(200, '<p>&nbsp; &nbsp; Define Data base ?<br></p>', 5, 5, 1, 1, 31, 71, 0);

-- --------------------------------------------------------

--
-- Table structure for table `question_answers`
--

CREATE TABLE `question_answers` (
  `id` int(11) NOT NULL,
  `questionID` int(11) DEFAULT NULL,
  `answer` varchar(2000) DEFAULT NULL,
  `matchAnswer` varchar(255) DEFAULT NULL,
  `isCorrect` tinyint(1) DEFAULT 1,
  `points` int(2) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `question_answers`
--

INSERT INTO `question_answers` (`id`, `questionID`, `answer`, `matchAnswer`, `isCorrect`, `points`) VALUES
(917, 188, 'Accepted Answer', NULL, 1, 1),
(918, 188, 'Accepted Answer', NULL, 1, 1),
(919, 188, 'Accepted Answer', NULL, 1, 1),
(920, 190, 'Answer 1', 'Match 1', 1, 1),
(921, 190, 'Answer 2', 'Match 2', 1, 1),
(922, 190, 'Answer 3', 'Match 3', 1, 1),
(923, 194, 'Accepted Answer', NULL, 1, 1),
(924, 194, 'Accepted Answer', NULL, 1, 1),
(925, 194, 'Accepted Answer', NULL, 1, 1),
(926, 196, 'Answer 1', 'Match 1', 1, 1),
(927, 196, 'Answer 2', 'Match 2', 1, 1),
(928, 196, 'Answer 3', 'Match 3', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

CREATE TABLE `result` (
  `id` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `testID` int(11) NOT NULL,
  `groupID` int(11) DEFAULT NULL,
  `settingID` int(11) DEFAULT NULL,
  `startTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `endTime` timestamp NULL DEFAULT NULL,
  `isTemp` tinyint(1) NOT NULL DEFAULT 1,
  `hostname` varchar(255) DEFAULT NULL,
  `ipaddr` varchar(15) DEFAULT NULL,
  `score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `result`
--

INSERT INTO `result` (`id`, `studentID`, `testID`, `groupID`, `settingID`, `startTime`, `endTime`, `isTemp`, `hostname`, `ipaddr`, `score`) VALUES
(39, 201234567, 40, 13, 80, '2025-04-13 21:46:34', '2025-04-13 21:46:58', 0, 'DESKTOP-P8VG2E9', '::1', NULL),
(40, 201234567, 41, 13, 81, '2025-04-13 22:18:12', '2025-04-13 22:18:20', 0, 'DESKTOP-P8VG2E9', '::1', NULL),
(41, 201234567, 42, 13, 82, '2025-04-13 22:20:58', '2025-04-13 22:21:15', 0, 'DESKTOP-P8VG2E9', '::1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `result_answers`
--

CREATE TABLE `result_answers` (
  `id` int(11) NOT NULL,
  `resultID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `answerID` int(11) DEFAULT NULL,
  `isTrue` tinyint(1) DEFAULT NULL,
  `textAnswer` varchar(2000) DEFAULT NULL,
  `points` int(3) DEFAULT -1,
  `isCorrect` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `result_answers`
--

INSERT INTO `result_answers` (`id`, `resultID`, `questionID`, `answerID`, `isTrue`, `textAnswer`, `points`, `isCorrect`) VALUES
(463, 39, 198, NULL, 0, NULL, 5, 1),
(464, 39, 200, NULL, 0, 'data base stote large data ', 5, 1),
(465, 40, 198, NULL, 0, NULL, 5, 1),
(466, 41, 198, NULL, 0, NULL, 5, 1),
(467, 41, 200, NULL, 0, 'data base store large data', -1, 0);

--
-- Triggers `result_answers`
--
DELIMITER $$
CREATE TRIGGER `as` BEFORE INSERT ON `result_answers` FOR EACH ROW BEGIN
		DECLARE qtype INT;
		DECLARE qpoints INT;
    SET qtype = (SELECT type FROM question where id = NEW.questionID);
		SET qpoints = (SELECT points from question WHERE id = NEW.questionID);
    IF(qtype = 1) THEN
			IF NEW.isTrue = (SELECT isTrue from question where id = NEW.questionID) THEN
			SET NEW.isCorrect = 1;
			SET NEW.points = qpoints;
			ELSE
			SET NEW.isCorrect = 0;
			SET NEW.points = 0;
			END IF;
		ELSEIF(qtype = 5) THEN
			IF NEW.textAnswer = '' THEN
			SET NEW.isCorrect = 0;
			SET NEW.points = 0;
			END IF;
		ELSEIF(qtype = 4) THEN
			IF (NEW.textAnswer = (SELECT matchAnswer from question_answers where id = NEW.answerID)) THEN
				SET NEW.isCorrect = 1;
				SET NEW.points = (SELECT points FROM question_answers where id = NEW.answerID);
			ELSE
				SET NEW.isCorrect = 0;
				SET NEW.points = 0;
			END IF;
    END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_token` varchar(100) DEFAULT NULL,
  `token_expire` timestamp NULL DEFAULT NULL,
  `suspended` tinyint(1) DEFAULT 0,
  `sessionID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `name`, `email`, `phone`, `password`, `password_token`, `token_expire`, `suspended`, `sessionID`) VALUES
(200000000, 'Elzonfly', 'zonfol@gmail.com', '1279819191', '$2y$10$sJeTXheh9B.Fo7WEFayBbejPqT5MCYag7JpkuV4/Y4HQBYcSSmOVq', NULL, NULL, 0, NULL),
(201111111, 'Reem Adel', 'Reem@gmail.com', '1276612119', '$2y$10$KXHAzUGzhCJOo283pdSDXO0fucC4h5irrp6RO8GCswkuIVkO14Gx2', NULL, NULL, 0, NULL),
(201212121, 'Maryam Rashad', 'mariamm@gmail.com', '12766191919', '$2y$10$aQYlYFrlvsr159z82DGPNO3Rfo6ghfT9MmZ7YTeqZL9wBTV.Fb5zu', NULL, NULL, 0, NULL),
(201234567, 'omar rehan', 'omar@gmail.com', '01276612119', '276506d3704c67d67ff9a500be50dd95', NULL, NULL, 0, '8c78pugjjq6hm5eqcfvl9p7cu3'),
(202222222, 'esraa soliman', 'esraa@gmail.com', '12872891991', '$2y$10$PkV5hSCjYe0hrGiHWSfeLeMGYYqpIepJ6hWxEvesRAT9m0rpNym0m', NULL, NULL, 0, NULL),
(204444444, 'bassem ', 'beso@gmail.com', '1277712119', '$2y$10$/DzXvhETLxgAgiAZgRDSW.0930ORAF3qAWTxX4mL60MXkHfHL1Agq', NULL, NULL, 0, NULL),
(209999999, 'Maryam rabi', 'mariam@gmail.com', '1276619911', '$2y$10$Y9TS.UXHjLNqLWsf7XEwdOXjBUL/eInYKXcMyygWqm179Xj7dlGnS', NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tempquestions`
--

CREATE TABLE `tempquestions` (
  `resultID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

CREATE TABLE `test` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `courseID` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `instructorID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `test`
--

INSERT INTO `test` (`id`, `name`, `courseID`, `deleted`, `instructorID`) VALUES
(40, 'omarrehan', 70, 0, 31),
(41, 'ahmed', 70, 0, 31),
(42, 'last', 70, 0, 31);

-- --------------------------------------------------------

--
-- Table structure for table `tests_has_questions`
--

CREATE TABLE `tests_has_questions` (
  `testID` int(11) DEFAULT NULL,
  `questionID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `tests_has_questions`
--

INSERT INTO `tests_has_questions` (`testID`, `questionID`) VALUES
(40, 198),
(40, 200),
(41, 198),
(42, 198),
(42, 200);

-- --------------------------------------------------------

--
-- Table structure for table `test_invitations`
--

CREATE TABLE `test_invitations` (
  `id` int(15) NOT NULL,
  `name` varchar(255) NOT NULL,
  `testID` int(11) DEFAULT NULL,
  `settingID` int(11) DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0,
  `useLimit` int(11) DEFAULT NULL,
  `instructorID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `test_settings`
--

CREATE TABLE `test_settings` (
  `id` int(11) NOT NULL,
  `startTime` datetime DEFAULT NULL,
  `endTime` datetime DEFAULT NULL,
  `duration` int(3) DEFAULT NULL,
  `prevQuestion` int(1) DEFAULT NULL,
  `viewAnswers` tinyint(1) DEFAULT NULL,
  `releaseResult` int(1) DEFAULT 1,
  `sendToStudent` tinyint(1) DEFAULT NULL,
  `sendToInstructor` tinyint(1) DEFAULT NULL,
  `passPercent` int(3) DEFAULT NULL,
  `instructorID` int(11) DEFAULT NULL,
  `testName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `test_settings`
--

INSERT INTO `test_settings` (`id`, `startTime`, `endTime`, `duration`, `prevQuestion`, `viewAnswers`, `releaseResult`, `sendToStudent`, `sendToInstructor`, `passPercent`, `instructorID`, `testName`) VALUES
(80, '2025-04-13 23:13:00', '2025-04-14 00:13:00', 30, 1, 0, 1, 1, 1, 60, 31, ''),
(81, '2025-04-13 23:59:00', '2025-04-14 00:59:00', 30, 0, 0, 1, 1, 1, 60, 31, ''),
(82, '2025-04-14 00:20:00', '2025-04-14 01:20:00', 30, 0, 0, 1, 1, 1, 60, 31, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `instructorID` (`instructorID`) USING BTREE,
  ADD KEY `parent` (`parent`) USING BTREE;

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `instructorID` (`instructorID`) USING BTREE,
  ADD KEY `settingID` (`settingID`) USING BTREE,
  ADD KEY `groups_ibfk_2` (`assignedTest`) USING BTREE;

--
-- Indexes for table `groups_has_students`
--
ALTER TABLE `groups_has_students`
  ADD UNIQUE KEY `my_unique_key` (`groupID`,`studentID`) USING BTREE,
  ADD KEY `groups_has_students_ibfk_2` (`studentID`) USING BTREE;

--
-- Indexes for table `instructor`
--
ALTER TABLE `instructor`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `mails`
--
ALTER TABLE `mails`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `resultID` (`resultID`) USING BTREE,
  ADD KEY `instructorID` (`instructorID`) USING BTREE,
  ADD KEY `studentID` (`studentID`) USING BTREE;

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `question_ibfk_1` (`instructorID`) USING BTREE,
  ADD KEY `question_ibfk_2` (`courseID`) USING BTREE;

--
-- Indexes for table `question_answers`
--
ALTER TABLE `question_answers`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `answers_ibfk_1` (`questionID`) USING BTREE,
  ADD KEY `matchAnswer` (`matchAnswer`) USING BTREE;

--
-- Indexes for table `result`
--
ALTER TABLE `result`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `testID_2` (`testID`,`studentID`) USING BTREE,
  ADD KEY `result_ibfk_2` (`studentID`) USING BTREE,
  ADD KEY `settingID` (`settingID`) USING BTREE,
  ADD KEY `groupID` (`groupID`) USING BTREE;

--
-- Indexes for table `result_answers`
--
ALTER TABLE `result_answers`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FK_result_answers_result` (`resultID`) USING BTREE,
  ADD KEY `FK_result_answers_question` (`questionID`) USING BTREE,
  ADD KEY `answerID` (`answerID`) USING BTREE;

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `email` (`email`) USING BTREE;

--
-- Indexes for table `tempquestions`
--
ALTER TABLE `tempquestions`
  ADD UNIQUE KEY `resultID` (`resultID`,`questionID`) USING BTREE,
  ADD KEY `quest` (`questionID`) USING BTREE;

--
-- Indexes for table `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `instructorID` (`instructorID`) USING BTREE,
  ADD KEY `courseID` (`courseID`) USING BTREE;

--
-- Indexes for table `tests_has_questions`
--
ALTER TABLE `tests_has_questions`
  ADD UNIQUE KEY `my_unique_key` (`testID`,`questionID`) USING BTREE,
  ADD KEY `tests_has_questions_ibfk_2` (`questionID`) USING BTREE;

--
-- Indexes for table `test_invitations`
--
ALTER TABLE `test_invitations`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `instructorID` (`instructorID`) USING BTREE,
  ADD KEY `settingID` (`settingID`) USING BTREE,
  ADD KEY `test_invitations_ibfk_1` (`testID`) USING BTREE;

--
-- Indexes for table `test_settings`
--
ALTER TABLE `test_settings`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `instructorID` (`instructorID`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `instructor`
--
ALTER TABLE `instructor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `mails`
--
ALTER TABLE `mails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `question_answers`
--
ALTER TABLE `question_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=929;

--
-- AUTO_INCREMENT for table `result`
--
ALTER TABLE `result`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `result_answers`
--
ALTER TABLE `result_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=468;

--
-- AUTO_INCREMENT for table `test`
--
ALTER TABLE `test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `test_invitations`
--
ALTER TABLE `test_invitations`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `test_settings`
--
ALTER TABLE `test_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`instructorID`) REFERENCES `instructor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_ibfk_2` FOREIGN KEY (`parent`) REFERENCES `course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`instructorID`) REFERENCES `instructor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`assignedTest`) REFERENCES `test` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `groups_ibfk_3` FOREIGN KEY (`settingID`) REFERENCES `test_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `groups_has_students`
--
ALTER TABLE `groups_has_students`
  ADD CONSTRAINT `groups_has_students_ibfk_1` FOREIGN KEY (`groupID`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `groups_has_students_ibfk_2` FOREIGN KEY (`studentID`) REFERENCES `student` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mails`
--
ALTER TABLE `mails`
  ADD CONSTRAINT `mails_ibfk_1` FOREIGN KEY (`resultID`) REFERENCES `result` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mails_ibfk_2` FOREIGN KEY (`instructorID`) REFERENCES `instructor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mails_ibfk_3` FOREIGN KEY (`studentID`) REFERENCES `student` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `question_ibfk_1` FOREIGN KEY (`instructorID`) REFERENCES `instructor` (`id`),
  ADD CONSTRAINT `question_ibfk_2` FOREIGN KEY (`courseID`) REFERENCES `course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `question_answers`
--
ALTER TABLE `question_answers`
  ADD CONSTRAINT `question_answers_ibfk_1` FOREIGN KEY (`questionID`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `result`
--
ALTER TABLE `result`
  ADD CONSTRAINT `result_ibfk_2` FOREIGN KEY (`studentID`) REFERENCES `student` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `result_ibfk_3` FOREIGN KEY (`testID`) REFERENCES `test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `result_ibfk_4` FOREIGN KEY (`settingID`) REFERENCES `test_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `result_ibfk_5` FOREIGN KEY (`groupID`) REFERENCES `groups` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `result_answers`
--
ALTER TABLE `result_answers`
  ADD CONSTRAINT `FK_result_answers_result` FOREIGN KEY (`resultID`) REFERENCES `result` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `result_answers_ibfk_1` FOREIGN KEY (`answerID`) REFERENCES `question_answers` (`id`),
  ADD CONSTRAINT `result_answers_ibfk_2` FOREIGN KEY (`questionID`) REFERENCES `question` (`id`);

--
-- Constraints for table `tempquestions`
--
ALTER TABLE `tempquestions`
  ADD CONSTRAINT `tempquestions_ibfk_1` FOREIGN KEY (`resultID`) REFERENCES `result` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `test`
--
ALTER TABLE `test`
  ADD CONSTRAINT `test_ibfk_1` FOREIGN KEY (`instructorID`) REFERENCES `instructor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `test_ibfk_2` FOREIGN KEY (`courseID`) REFERENCES `course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tests_has_questions`
--
ALTER TABLE `tests_has_questions`
  ADD CONSTRAINT `tests_has_questions_ibfk_1` FOREIGN KEY (`testID`) REFERENCES `test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tests_has_questions_ibfk_2` FOREIGN KEY (`questionID`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `test_invitations`
--
ALTER TABLE `test_invitations`
  ADD CONSTRAINT `test_invitations_ibfk_1` FOREIGN KEY (`testID`) REFERENCES `test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `test_invitations_ibfk_3` FOREIGN KEY (`instructorID`) REFERENCES `instructor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `test_invitations_ibfk_4` FOREIGN KEY (`settingID`) REFERENCES `test_settings` (`id`);

--
-- Constraints for table `test_settings`
--
ALTER TABLE `test_settings`
  ADD CONSTRAINT `test_settings_ibfk_1` FOREIGN KEY (`instructorID`) REFERENCES `instructor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
