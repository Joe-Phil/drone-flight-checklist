

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- USER TABLE

CREATE TABLE `user` (
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- PASSWORD RESET TABLE

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- FORM TABLE

CREATE TABLE `form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formName` varchar(255) NOT NULL,
  `formType` varchar(100) DEFAULT NULL,
  `updatedBy` varchar(100) DEFAULT NULL,
  `updatedDate` datetime DEFAULT NULL,
  `formData` longtext DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  CONSTRAINT `form_owner_fk` FOREIGN KEY (`owner`) REFERENCES `user` (`username`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- TEMPLATE TABLE

CREATE TABLE `template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `templateName` varchar(255) NOT NULL,
  `assessmentId` int(11) DEFAULT NULL,
  `preId` int(11) DEFAULT NULL,
  `postId` int(11) DEFAULT NULL,
  `updatedBy` varchar(100) DEFAULT NULL,
  `updatedDate` datetime DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  CONSTRAINT `template_owner_fk` FOREIGN KEY (`owner`) REFERENCES `user` (`username`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- SUBMISSION TABLE

CREATE TABLE `submission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submissionName` varchar(255) DEFAULT NULL,
  `templateId` int(11) DEFAULT NULL,
  `submittedBy` varchar(100) DEFAULT NULL,
  `submittedDate` datetime DEFAULT NULL,
  `formData` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submittedBy` (`submittedBy`),
  CONSTRAINT `submission_submittedBy_fk` FOREIGN KEY (`submittedBy`) REFERENCES `user` (`username`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- STEP 5: SUBMISSION_FILE TABLE

CREATE TABLE `submission_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `form_type` varchar(100) DEFAULT NULL,
  `question_key` varchar(255) DEFAULT NULL,
  `file_path` varchar(512) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  CONSTRAINT `submission_file_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submission` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- this is one of the insert into submission
-- (109, 'Test Mobile Dupicate Question', 25, 'admin321', '2026-05-11 11:27:45', '[{\"type\":\"assessment\",\"answer\":[{\"questionName\":\"pertanyaan form 1\",\"answer\":\"Test Assignement\",\"option\":[],\"qType\":\"text\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 2\",\"answer\":\"checklist2\",\"option\":[\"checklist1\",\"checklist2\",\"checklist3\"],\"qType\":\"checklist\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 3\",\"answer\":\"multiple2\",\"option\":[\"multiple1\",\"multiple2\",\"multiple3\"],\"qType\":\"multiple\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 4\",\"answer\":\"Test Assignment\",\"option\":[],\"qType\":\"longtext\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"}]},{\"type\":\"pre\",\"answer\":[{\"flightNum\":1,\"data\":[{\"questionName\":\"pertanyaan form 1\",\"answer\":\"Test Post 1\",\"option\":[],\"qType\":\"text\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 2\",\"answer\":\"checklist1\",\"option\":[\"checklist1\",\"checklist2\",\"checklist3\"],\"qType\":\"checklist\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 3\",\"answer\":\"multiple1\",\"option\":[\"multiple1\",\"multiple2\",\"multiple3\"],\"qType\":\"multiple\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 4\",\"answer\":\"Test Post 1\",\"option\":[],\"qType\":\"longtext\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"}]},{\"flightNum\":2,\"data\":[{\"questionName\":\"pertanyaan form 1\",\"answer\":\"Test Post 2\",\"option\":[],\"qType\":\"text\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 2\",\"answer\":\"checklist2\",\"option\":[\"checklist1\",\"checklist2\",\"checklist3\"],\"qType\":\"checklist\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 3\",\"answer\":\"multiple2\",\"option\":[\"multiple1\",\"multiple2\",\"multiple3\"],\"qType\":\"multiple\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 4\",\"answer\":\"Test Post 2\",\"option\":[],\"qType\":\"longtext\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"}]},{\"flightNum\":3,\"data\":[{\"questionName\":\"pertanyaan form 1\",\"answer\":\"Test Post 3\",\"option\":[],\"qType\":\"text\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 2\",\"answer\":\"checklist3\",\"option\":[\"checklist1\",\"checklist2\",\"checklist3\"],\"qType\":\"checklist\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 3\",\"answer\":\"multiple3\",\"option\":[\"multiple1\",\"multiple2\",\"multiple3\"],\"qType\":\"multiple\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 4\",\"answer\":\"Test Post 3\",\"option\":[],\"qType\":\"longtext\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"}]}]},{\"type\":\"post\",\"answer\":[{\"flightNum\":1,\"data\":[{\"questionName\":\"pertanyaan form 1\",\"answer\":\"Test Post 1\",\"option\":[],\"qType\":\"text\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 2\",\"answer\":\"checklist1\",\"option\":[\"checklist1\",\"checklist2\",\"checklist3\"],\"qType\":\"checklist\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 3\",\"answer\":\"multiple1\",\"option\":[\"multiple1\",\"multiple2\",\"multiple3\"],\"qType\":\"multiple\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 4\",\"answer\":\"Test Post 1\",\"option\":[],\"qType\":\"longtext\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"}]},{\"flightNum\":2,\"data\":[{\"questionName\":\"pertanyaan form 1\",\"answer\":\"Test Post 2\",\"option\":[],\"qType\":\"text\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 2\",\"answer\":\"checklist2\",\"option\":[\"checklist1\",\"checklist2\",\"checklist3\"],\"qType\":\"checklist\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 3\",\"answer\":\"multiple2\",\"option\":[\"multiple1\",\"multiple2\",\"multiple3\"],\"qType\":\"multiple\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 4\",\"answer\":\"Test Post 2\",\"option\":[],\"qType\":\"longtext\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"}]},{\"flightNum\":3,\"data\":[{\"questionName\":\"pertanyaan form 1\",\"answer\":\"Test Post 3\",\"option\":[],\"qType\":\"text\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 2\",\"answer\":\"checklist3\",\"option\":[\"checklist1\",\"checklist2\",\"checklist3\"],\"qType\":\"checklist\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 3\",\"answer\":\"multiple3\",\"option\":[\"multiple1\",\"multiple2\",\"multiple3\"],\"qType\":\"multiple\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"},{\"questionName\":\"pertanyaan form 4\",\"answer\":\"Test Post 3\",\"option\":[],\"qType\":\"longtext\",\"isRequired\":true,\"dataChanged\":\"2026/05/11 11:27:39\"}]}]}]');

COMMIT;
