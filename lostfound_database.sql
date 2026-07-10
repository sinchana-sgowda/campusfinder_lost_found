CREATE DATABASE IF NOT EXISTS LostFoundSystem;
USE LostFoundSystem;

CREATE TABLE STUDENT (
    StudentID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Phone VARCHAR(15) NOT NULL,
    Department VARCHAR(100),
    Password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ADMIN (
    AdminID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Password VARCHAR(255) NOT NULL
);

CREATE TABLE ITEM (
    ItemID INT AUTO_INCREMENT PRIMARY KEY,
    StudentID INT NOT NULL,
    Type ENUM('Lost','Found') NOT NULL,
    Title VARCHAR(200) NOT NULL,
    Description TEXT,
    Category VARCHAR(100),
    Location VARCHAR(200),
    Date DATE NOT NULL,
    Image VARCHAR(255) DEFAULT NULL,
    Status ENUM('Open','Claimed','Closed') DEFAULT 'Open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StudentID) REFERENCES STUDENT(StudentID) ON DELETE CASCADE
);

CREATE TABLE CLAIM (
    ClaimID INT AUTO_INCREMENT PRIMARY KEY,
    ItemID INT NOT NULL,
    StudentID INT NOT NULL,
    Message TEXT,
    Status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ItemID) REFERENCES ITEM(ItemID) ON DELETE CASCADE,
    FOREIGN KEY (StudentID) REFERENCES STUDENT(StudentID) ON DELETE CASCADE
);

INSERT INTO ADMIN (Name, Password) VALUES ('Administrator', 'admin123');

INSERT INTO STUDENT (Name, Email, Phone, Department, Password) VALUES
('Sinchana MS', 'sinchana@college.com', '9876543210', 'ECE', 'pass123'),
('Sanjana L', 'sanjana@college.com', '9876543211', 'CSE', 'pass123');

INSERT INTO ITEM (StudentID, Type, Title, Description, Category, Location, Date, Status) VALUES
(1, 'Lost', 'Blue Water Bottle', 'Blue colored steel water bottle with sticker on it', 'Accessories', 'Library 2nd Floor', '2026-06-01', 'Open'),
(2, 'Found', 'Black Wallet', 'Found a black leather wallet near the canteen', 'Wallet', 'Canteen', '2026-06-02', 'Open'),
(1, 'Lost', 'Student ID Card', 'Lost my college ID card near the main gate', 'ID/Cards', 'Main Gate', '2026-06-03', 'Open'),
(2, 'Found', 'Umbrella', 'Blue umbrella found in classroom 204', 'Accessories', 'Block B Room 204', '2026-06-04', 'Open');
