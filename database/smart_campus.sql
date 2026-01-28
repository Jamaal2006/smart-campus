-- MySQL database schema for Smart Campus Resource Hub

CREATE TABLE IF NOT EXISTS resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity INT NOT NULL,
    location VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_date DATETIME NOT NULL,
    FOREIGN KEY (resource_id) REFERENCES resources(resource_id)
);

INSERT INTO resources (name, description, quantity, location) VALUES
('Room A', 'Large seminar room', 5, 'Building 1'),
('Room B', 'Medium meeting room', 10, 'Building 1');

INSERT INTO bookings (resource_id, user_id, booking_date) VALUES
(1, 1, '2026-01-28 13:53:03');