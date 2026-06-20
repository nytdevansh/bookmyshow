-- BookMyShow PostgreSQL Schema
-- Run this once against your Render PostgreSQL database

CREATE TABLE IF NOT EXISTS users (
    id         SERIAL PRIMARY KEY,
    name       TEXT        NOT NULL,
    email      TEXT        NOT NULL UNIQUE,
    password   TEXT        NOT NULL,
    role       TEXT        NOT NULL DEFAULT 'user',
    status     TEXT        NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS movies (
    id           SERIAL PRIMARY KEY,
    title        TEXT        NOT NULL,
    genre        TEXT        NOT NULL DEFAULT '',
    duration     TEXT        NOT NULL DEFAULT '',
    release_date DATE,
    description  TEXT        NOT NULL DEFAULT '',
    poster       TEXT        NOT NULL DEFAULT '',
    banner       TEXT        NOT NULL DEFAULT '',
    trailer_url  TEXT        NOT NULL DEFAULT '',
    status       TEXT        NOT NULL DEFAULT 'showing',
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS shows (
    id           SERIAL PRIMARY KEY,
    movie_id     INTEGER     NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    screen_name  TEXT        NOT NULL DEFAULT 'Screen 1',
    show_date    DATE        NOT NULL,
    show_time    TIME        NOT NULL,
    total_seats  INTEGER     NOT NULL DEFAULT 100,
    price        NUMERIC(10,2) NOT NULL DEFAULT 150.00,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS seats (
    id      SERIAL PRIMARY KEY,
    show_id INTEGER NOT NULL REFERENCES shows(id) ON DELETE CASCADE,
    seat_no TEXT    NOT NULL,
    status  TEXT    NOT NULL DEFAULT 'available',
    UNIQUE (show_id, seat_no)
);

CREATE TABLE IF NOT EXISTS bookings (
    id           SERIAL PRIMARY KEY,
    booking_code TEXT          NOT NULL UNIQUE,
    user_id      INTEGER       NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    show_id      INTEGER       NOT NULL REFERENCES shows(id) ON DELETE CASCADE,
    seats        TEXT          NOT NULL,
    total_amount NUMERIC(10,2) NOT NULL DEFAULT 0,
    status       TEXT          NOT NULL DEFAULT 'confirmed',
    booking_date TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- Default admin user (password: admin123)
-- Change this password immediately after first login!
INSERT INTO users (name, email, password, role)
VALUES (
    'Admin',
    'admin@bookmyshow.com',
    '$2y$10$lTLqzuRJD/ojTVrIFtZjIeijO1GDdms2gMhzyh1CzU2JwRAWLwXsi',
    'admin'
)
ON CONFLICT (email) DO NOTHING;
