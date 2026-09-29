-- schema.sql
-- Run this ENTIRE file once in phpMyAdmin (locally) and once in your
-- online database (TiDB Cloud / Aiven / wherever you host it live).
-- It creates the 3 tables Eventra needs and adds sample rows so the
-- site isn't empty when you demo it.

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('attendee','organizer','admin') NOT NULL DEFAULT 'attendee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    venue VARCHAR(150) NOT NULL,
    city VARCHAR(50) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    price DECIMAL(8,2) NOT NULL DEFAULT 0,
    total_seats INT NOT NULL,
    seats_left INT NOT NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(user_id)
);

CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    num_tickets INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) DEFAULT NULL,
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);

-- ===== SAMPLE DATA =====

-- A demo organizer, just to satisfy the foreign key on sample events.
-- Its password hash is a placeholder and won't log in - use the
-- Register page to create real accounts for testing.
INSERT INTO users (name, email, password, role)
VALUES ('Demo Organizer', 'organizer@eventra.com', '$2y$10$examplehashwontwork000000000000000000000000000000', 'organizer');

-- A varied set of general-public events across several cities
INSERT INTO events (organizer_id, title, description, category, venue, city, event_date, event_time, price, total_seats, seats_left, status) VALUES
(1, 'Arijit Singh Live in Concert', 'A live music night featuring popular Bollywood hits.', 'Music', 'DY Patil Stadium', 'Mumbai', '2026-11-15', '19:00:00', 1499.00, 500, 500, 'approved'),
(1, 'Stand-Up Comedy Night', 'An evening of stand-up comedy with top touring comedians.', 'Comedy', 'The Habitat', 'Mumbai', '2026-10-25', '20:00:00', 499.00, 150, 150, 'approved'),
(1, 'City Marathon 2026', 'A 10K/21K run open to all fitness levels, through the city center.', 'Sports', 'Riverfront Park', 'Ahmedabad', '2026-12-05', '06:00:00', 300.00, 1000, 1000, 'approved'),
(1, 'AI & Data Science Summit', 'Talks and workshops from industry practitioners on AI and data.', 'Tech', 'Bangalore International Centre', 'Bangalore', '2026-10-20', '10:00:00', 999.00, 300, 300, 'approved'),
(1, 'Street Food Festival', 'A weekend of food stalls from across the country, live music, and games.', 'Food & Culture', 'Jawaharlal Nehru Stadium Grounds', 'Delhi', '2026-11-08', '12:00:00', 199.00, 800, 800, 'approved'),
(1, 'Sunburn Music Festival', 'A weekend electronic music festival with international DJs.', 'Music', 'Vagator Beach', 'Goa', '2026-12-27', '16:00:00', 2499.00, 2000, 2000, 'approved'),
(1, 'Watercolor Painting Workshop', 'A beginner-friendly hands-on workshop, materials included.', 'Art & Craft', 'Community Arts Center', 'Pune', '2026-10-18', '11:00:00', 799.00, 40, 40, 'approved'),
(1, 'Startup Networking Mixer', 'An evening to connect with founders, investors, and freelancers.', 'Business', 'WeWork Galaxy', 'Bangalore', '2026-11-02', '18:30:00', 0.00, 120, 120, 'approved'),
(1, 'Book Fair & Author Meet', 'Browse thousands of titles and meet bestselling authors.', 'Literature', 'Exhibition Grounds', 'Kolkata', '2026-11-22', '10:00:00', 0.00, 2000, 2000, 'approved'),
(1, 'Classical Dance Recital', 'An evening of Bharatanatyam and Kathak performances.', 'Dance', 'Kalakshetra Auditorium', 'Chennai', '2026-11-30', '18:00:00', 350.00, 250, 250, 'approved');

-- ===== HOW TO CREATE AN ADMIN ACCOUNT =====
-- There's no public "sign up as admin" option (on purpose - anyone could
-- misuse it). Instead:
--   1. Register a normal account through the website (as an attendee).
--   2. Then run this command, replacing the email with the one you used:
--      UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
--   3. Log out and log back in - you'll now see the Admin Dashboard.
