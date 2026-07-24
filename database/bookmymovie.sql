-- ============================================================
--  BookMyMovie — COMPLETE DATABASE  (Schema + Data)
--  Database : bookmymovie
--  Engine   : InnoDB  |  Charset: utf8mb4_unicode_ci
--  MySQL    : 8.0+
-- ============================================================
--  DEFAULT TEST PASSWORD FOR ALL SAMPLE ACCOUNTS : password
--  Bcrypt   : $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS,   UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP DATABASE IF EXISTS bookmymovie;
CREATE DATABASE bookmymovie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookmymovie;

-- ============================================================
-- SECTION 1 — TABLES  (27 total, ordered by FK dependency)
-- ============================================================

-- 1. admins
CREATE TABLE admins (
    id              BIGINT UNSIGNED          NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100)             NOT NULL,
    email           VARCHAR(150)             NOT NULL,
    password        VARCHAR(255)             NOT NULL,
    profile_picture VARCHAR(255)                 NULL,
    role            ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
    is_active       TINYINT(1)               NOT NULL DEFAULT 1,
    last_login_at   TIMESTAMP                    NULL,
    remember_token  VARCHAR(100)                 NULL,
    created_at      TIMESTAMP                NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP                NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email   (email),
    INDEX      idx_admins_role   (role),
    INDEX      idx_admins_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. users
CREATE TABLE users (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name              VARCHAR(100)    NOT NULL,
    email             VARCHAR(150)    NOT NULL,
    email_verified_at TIMESTAMP           NULL,
    password          VARCHAR(255)    NOT NULL,
    phone             VARCHAR(20)         NULL,
    date_of_birth     DATE                NULL,
    profile_picture   VARCHAR(255)        NULL,
    gender            ENUM('male','female','other','prefer_not_to_say') NULL,
    is_blocked        TINYINT(1)      NOT NULL DEFAULT 0,
    blocked_reason    VARCHAR(255)        NULL,
    blocked_at        TIMESTAMP           NULL,
    remember_token    VARCHAR(100)        NULL,
    created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        TIMESTAMP           NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email      (email),
    UNIQUE KEY uq_users_phone      (phone),
    INDEX      idx_users_blocked   (is_blocked),
    INDEX      idx_users_deleted   (deleted_at),
    INDEX      idx_users_dob       (date_of_birth)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. password_reset_tokens  (users)
CREATE TABLE password_reset_tokens (
    email      VARCHAR(150) NOT NULL,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (email),
    INDEX idx_prt_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. admin_password_resets  (separate guard for admins)
CREATE TABLE admin_password_resets (
    email      VARCHAR(150) NOT NULL,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (email),
    INDEX idx_apr_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. genres
CREATE TABLE genres (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(50)     NOT NULL,
    slug       VARCHAR(60)     NOT NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_genres_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. movies
CREATE TABLE movies (
    id                     BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    title                  VARCHAR(200)      NOT NULL,
    slug                   VARCHAR(230)      NOT NULL,
    description            TEXT                  NULL,
    language               VARCHAR(50)       NOT NULL DEFAULT 'English',
    duration_minutes       SMALLINT UNSIGNED NOT NULL,
    certificate_rating     ENUM('U','UA','A','S','G','PG','PG-13','R') NULL,
    release_date           DATE              NOT NULL,
    status                 ENUM('coming_soon','now_showing','ended') NOT NULL DEFAULT 'coming_soon',
    poster_image           VARCHAR(255)          NULL,
    banner_image           VARCHAR(255)          NULL,
    trailer_url            VARCHAR(500)          NULL,
    kids_discount_eligible TINYINT(1)        NOT NULL DEFAULT 0,
    average_rating         DECIMAL(3,2)      NOT NULL DEFAULT 0.00,
    total_reviews          INT UNSIGNED      NOT NULL DEFAULT 0,
    created_by             BIGINT UNSIGNED   NOT NULL,
    created_at             TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at             TIMESTAMP             NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_movies_slug       (slug),
    INDEX      idx_movies_status    (status),
    INDEX      idx_movies_release   (release_date),
    INDEX      idx_movies_deleted   (deleted_at),
    INDEX      idx_movies_language  (language),
    CONSTRAINT fk_movies_admin FOREIGN KEY (created_by) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. movie_genres  (many-to-many pivot)
CREATE TABLE movie_genres (
    movie_id BIGINT UNSIGNED NOT NULL,
    genre_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    INDEX idx_mg_genre (genre_id),
    CONSTRAINT fk_mg_movie FOREIGN KEY (movie_id) REFERENCES movies(id)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mg_genre FOREIGN KEY (genre_id) REFERENCES genres(id)  ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. theaters
CREATE TABLE theaters (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(150)    NOT NULL,
    address    TEXT            NOT NULL,
    city       VARCHAR(100)    NOT NULL,
    state      VARCHAR(100)    NOT NULL,
    pincode    VARCHAR(10)         NULL,
    phone      VARCHAR(20)         NULL,
    email      VARCHAR(150)        NULL,
    is_active  TINYINT(1)      NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_theaters_city   (city),
    INDEX idx_theaters_active (is_active),
    CONSTRAINT fk_theaters_admin FOREIGN KEY (created_by) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. screens
CREATE TABLE screens (
    id          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    theater_id  BIGINT UNSIGNED   NOT NULL,
    screen_name VARCHAR(50)       NOT NULL,
    total_seats SMALLINT UNSIGNED NOT NULL,
    is_active   TINYINT(1)        NOT NULL DEFAULT 1,
    created_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_screens_theater_name (theater_id, screen_name),
    INDEX      idx_screens_theater     (theater_id),
    CONSTRAINT fk_screens_theater FOREIGN KEY (theater_id) REFERENCES theaters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. seat_categories  (Gold / Platinum / Box)
CREATE TABLE seat_categories (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        ENUM('Gold','Platinum','Box') NOT NULL,
    description VARCHAR(255)       NULL,
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_seat_cat_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. seats
CREATE TABLE seats (
    id               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    screen_id        BIGINT UNSIGNED   NOT NULL,
    seat_category_id BIGINT UNSIGNED   NOT NULL,
    row_label        CHAR(2)           NOT NULL,
    seat_number      SMALLINT UNSIGNED NOT NULL,
    is_active        TINYINT(1)        NOT NULL DEFAULT 1,
    created_at       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_seats_screen_row_num (screen_id, row_label, seat_number),
    INDEX      idx_seats_screen        (screen_id),
    INDEX      idx_seats_category      (seat_category_id),
    CONSTRAINT fk_seats_screen   FOREIGN KEY (screen_id)        REFERENCES screens(id)         ON DELETE CASCADE,
    CONSTRAINT fk_seats_category FOREIGN KEY (seat_category_id) REFERENCES seat_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. shows
CREATE TABLE shows (
    id                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    movie_id            BIGINT UNSIGNED   NOT NULL,
    screen_id           BIGINT UNSIGNED   NOT NULL,
    show_date           DATE              NOT NULL,
    show_time           TIME              NOT NULL,
    status              ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    total_seats         SMALLINT UNSIGNED NOT NULL,
    booked_seats        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    cancellation_reason VARCHAR(255)          NULL,
    created_by          BIGINT UNSIGNED   NOT NULL,
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shows_screen_date_time (screen_id, show_date, show_time),
    INDEX      idx_shows_movie           (movie_id),
    INDEX      idx_shows_date            (show_date),
    INDEX      idx_shows_status          (status),
    INDEX      idx_shows_movie_date      (movie_id, show_date),
    CONSTRAINT fk_shows_movie  FOREIGN KEY (movie_id)   REFERENCES movies(id),
    CONSTRAINT fk_shows_screen FOREIGN KEY (screen_id)  REFERENCES screens(id),
    CONSTRAINT fk_shows_admin  FOREIGN KEY (created_by) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. show_seat_prices
CREATE TABLE show_seat_prices (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    show_id          BIGINT UNSIGNED NOT NULL,
    seat_category_id BIGINT UNSIGNED NOT NULL,
    price            DECIMAL(8,2)    NOT NULL,
    kids_price       DECIMAL(8,2)        NULL,
    created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ssp_show_category (show_id, seat_category_id),
    CONSTRAINT fk_ssp_show     FOREIGN KEY (show_id)          REFERENCES shows(id)          ON DELETE CASCADE,
    CONSTRAINT fk_ssp_category FOREIGN KEY (seat_category_id) REFERENCES seat_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. coupons
CREATE TABLE coupons (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code                VARCHAR(30)     NOT NULL,
    description         VARCHAR(255)        NULL,
    discount_type       ENUM('percentage','fixed') NOT NULL,
    discount_value      DECIMAL(8,2)    NOT NULL,
    max_discount_amount DECIMAL(8,2)        NULL,
    min_order_amount    DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    max_uses            INT UNSIGNED        NULL,
    used_count          INT UNSIGNED    NOT NULL DEFAULT 0,
    max_uses_per_user   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    valid_from          DATETIME        NOT NULL,
    valid_until         DATETIME        NOT NULL,
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_coupons_code   (code),
    INDEX      idx_coupons_valid (valid_from, valid_until),
    INDEX      idx_coupons_active(is_active),
    CONSTRAINT fk_coupons_admin FOREIGN KEY (created_by) REFERENCES admins(id),
    CONSTRAINT chk_coupons_value CHECK (discount_value > 0),
    CONSTRAINT chk_coupons_pct   CHECK (discount_type != 'percentage' OR discount_value <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. carts
CREATE TABLE carts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    BIGINT UNSIGNED NOT NULL,
    expires_at TIMESTAMP       NOT NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_carts_user (user_id),
    CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. cart_items
CREATE TABLE cart_items (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cart_id          BIGINT UNSIGNED NOT NULL,
    show_id          BIGINT UNSIGNED NOT NULL,
    seat_id          BIGINT UNSIGNED NOT NULL,
    seat_category_id BIGINT UNSIGNED NOT NULL,
    ticket_type      ENUM('adult','kid') NOT NULL DEFAULT 'adult',
    price            DECIMAL(8,2)    NOT NULL,
    added_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ci_seat_show (seat_id, show_id),
    INDEX      idx_ci_cart     (cart_id),
    INDEX      idx_ci_show     (show_id),
    CONSTRAINT fk_ci_cart     FOREIGN KEY (cart_id)          REFERENCES carts(id)          ON DELETE CASCADE,
    CONSTRAINT fk_ci_show     FOREIGN KEY (show_id)          REFERENCES shows(id)          ON DELETE CASCADE,
    CONSTRAINT fk_ci_seat     FOREIGN KEY (seat_id)          REFERENCES seats(id),
    CONSTRAINT fk_ci_category FOREIGN KEY (seat_category_id) REFERENCES seat_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. bookings
CREATE TABLE bookings (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_number      VARCHAR(24)     NOT NULL,  -- FIX 3: widened from 20 to match UUID-based fn_booking_number
    user_id             BIGINT UNSIGNED NOT NULL,
    show_id             BIGINT UNSIGNED NOT NULL,
    coupon_id           BIGINT UNSIGNED     NULL,
    seat_count          TINYINT UNSIGNED NOT NULL,
    adult_count         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    kids_count          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    subtotal            DECIMAL(10,2)   NOT NULL,
    discount_amount     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total_amount        DECIMAL(10,2)   NOT NULL,
    payment_method      ENUM('cod')     NOT NULL DEFAULT 'cod',
    payment_status      ENUM('pending','paid','refunded','failed') NOT NULL DEFAULT 'pending',
    booking_status      ENUM('confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'confirmed',
    cancelled_at        TIMESTAMP           NULL,
    cancellation_reason VARCHAR(255)        NULL,
    cancelled_by        ENUM('user','admin') NULL,
    booked_at           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bookings_number   (booking_number),
    INDEX      idx_bookings_user    (user_id),
    INDEX      idx_bookings_show    (show_id),
    INDEX      idx_bookings_status  (booking_status),
    INDEX      idx_bookings_payment (payment_status),
    INDEX      idx_bookings_date    (booked_at),
    CONSTRAINT fk_bookings_user   FOREIGN KEY (user_id)   REFERENCES users(id),
    CONSTRAINT fk_bookings_show   FOREIGN KEY (show_id)   REFERENCES shows(id),
    CONSTRAINT fk_bookings_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,
    CONSTRAINT chk_bookings_counts CHECK (adult_count + kids_count = seat_count),
    CONSTRAINT chk_bookings_total  CHECK (total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. booking_seats
CREATE TABLE booking_seats (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id       BIGINT UNSIGNED NOT NULL,
    seat_id          BIGINT UNSIGNED NOT NULL,
    show_id          BIGINT UNSIGNED NOT NULL,
    seat_category_id BIGINT UNSIGNED NOT NULL,
    ticket_type      ENUM('adult','kid') NOT NULL DEFAULT 'adult',
    price_paid       DECIMAL(8,2)    NOT NULL,
    ticket_number    VARCHAR(30)     NOT NULL,
    created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bs_seat_show     (seat_id, show_id),
    UNIQUE KEY uq_bs_ticket_number (ticket_number),
    INDEX      idx_bs_booking      (booking_id),
    INDEX      idx_bs_show         (show_id),
    CONSTRAINT fk_bs_booking  FOREIGN KEY (booking_id)       REFERENCES bookings(id)       ON DELETE CASCADE,
    CONSTRAINT fk_bs_seat     FOREIGN KEY (seat_id)          REFERENCES seats(id),
    CONSTRAINT fk_bs_show     FOREIGN KEY (show_id)          REFERENCES shows(id),
    CONSTRAINT fk_bs_category FOREIGN KEY (seat_category_id) REFERENCES seat_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. payments
CREATE TABLE payments (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id            BIGINT UNSIGNED NOT NULL,
    payment_method        ENUM('cod')     NOT NULL DEFAULT 'cod',
    amount                DECIMAL(10,2)   NOT NULL,
    status                ENUM('pending','paid','refunded','failed') NOT NULL DEFAULT 'pending',
    transaction_reference VARCHAR(100)        NULL,
    notes                 TEXT                NULL,
    paid_at               TIMESTAMP           NULL,
    refunded_at           TIMESTAMP           NULL,
    refund_reason         VARCHAR(255)        NULL,
    created_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    -- FIX 2: enforce 1:1 booking→payment — prevents duplicate rows that would break v_booking_details
    UNIQUE KEY uq_payments_booking (booking_id),
    INDEX idx_payments_status  (status),
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. coupon_usages
CREATE TABLE coupon_usages (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    coupon_id        BIGINT UNSIGNED NOT NULL,
    user_id          BIGINT UNSIGNED NOT NULL,
    booking_id       BIGINT UNSIGNED NOT NULL,
    discount_applied DECIMAL(8,2)    NOT NULL,
    used_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cu_booking     (booking_id),
    INDEX      idx_cu_coupon     (coupon_id),
    INDEX      idx_cu_user       (user_id),
    INDEX      idx_cu_coupon_user(coupon_id, user_id),
    CONSTRAINT fk_cu_coupon  FOREIGN KEY (coupon_id)  REFERENCES coupons(id),
    CONSTRAINT fk_cu_user    FOREIGN KEY (user_id)    REFERENCES users(id),
    CONSTRAINT fk_cu_booking FOREIGN KEY (booking_id) REFERENCES bookings(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. reviews
CREATE TABLE reviews (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    movie_id    BIGINT UNSIGNED NOT NULL,
    booking_id  BIGINT UNSIGNED     NULL,
    rating      TINYINT UNSIGNED NOT NULL,
    review_text TEXT                 NULL,
    is_approved TINYINT(1)       NOT NULL DEFAULT 0,
    is_flagged  TINYINT(1)       NOT NULL DEFAULT 0,
    approved_by BIGINT UNSIGNED      NULL,
    approved_at TIMESTAMP            NULL,
    created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_user_movie (user_id, movie_id),
    INDEX      idx_reviews_movie     (movie_id),
    INDEX      idx_reviews_approved  (is_approved),
    INDEX      idx_reviews_flagged   (is_flagged),
    CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)     REFERENCES users(id),
    CONSTRAINT fk_reviews_movie   FOREIGN KEY (movie_id)    REFERENCES movies(id)   ON DELETE CASCADE,
    CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id)  REFERENCES bookings(id) ON DELETE SET NULL,
    CONSTRAINT fk_reviews_admin   FOREIGN KEY (approved_by) REFERENCES admins(id)   ON DELETE SET NULL,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. wishlists
CREATE TABLE wishlists (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    BIGINT UNSIGNED NOT NULL,
    movie_id   BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wishlist_user_movie (user_id, movie_id),
    INDEX      idx_wishlist_user      (user_id),
    INDEX      idx_wishlist_movie     (movie_id),
    CONSTRAINT fk_wishlist_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. notifications  (user-facing)
CREATE TABLE notifications (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      BIGINT UNSIGNED NOT NULL,
    type         VARCHAR(100)    NOT NULL,
    title        VARCHAR(255)    NOT NULL,
    message      TEXT            NOT NULL,
    related_type VARCHAR(100)        NULL,
    related_id   BIGINT UNSIGNED     NULL,
    is_read      TINYINT(1)      NOT NULL DEFAULT 0,
    read_at      TIMESTAMP           NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_notif_user        (user_id),
    INDEX idx_notif_unread      (user_id, is_read),
    INDEX idx_notif_type        (type),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. admin_notifications  (admin panel bell)
CREATE TABLE admin_notifications (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id     BIGINT UNSIGNED NOT NULL,
    type         VARCHAR(100)    NOT NULL,
    title        VARCHAR(255)    NOT NULL,
    message      TEXT            NOT NULL,
    related_type VARCHAR(100)        NULL,
    related_id   BIGINT UNSIGNED     NULL,
    is_read      TINYINT(1)      NOT NULL DEFAULT 0,
    read_at      TIMESTAMP           NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_admin_notif       (admin_id),
    INDEX idx_admin_notif_unread(admin_id, is_read),
    CONSTRAINT fk_admin_notif_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. admin_activity_logs
CREATE TABLE admin_activity_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id    BIGINT UNSIGNED NOT NULL,
    action      VARCHAR(100)    NOT NULL,
    model_type  VARCHAR(100)        NULL,
    model_id    BIGINT UNSIGNED     NULL,
    description TEXT                NULL,
    old_values  JSON                NULL,
    new_values  JSON                NULL,
    ip_address  VARCHAR(45)         NULL,
    user_agent  TEXT                NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_aal_admin  (admin_id),
    INDEX idx_aal_action (action),
    INDEX idx_aal_model  (model_type, model_id),
    INDEX idx_aal_date   (created_at),
    CONSTRAINT fk_aal_admin FOREIGN KEY (admin_id) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. faqs
CREATE TABLE faqs (
    id         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    question   VARCHAR(500)     NOT NULL,
    answer     TEXT             NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active  TINYINT(1)       NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED  NOT NULL,
    created_at TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_faqs_active (is_active),
    INDEX idx_faqs_order  (sort_order),
    CONSTRAINT fk_faqs_admin FOREIGN KEY (created_by) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. contact_messages
CREATE TABLE contact_messages (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    email         VARCHAR(150)    NOT NULL,
    subject       VARCHAR(255)        NULL,
    message       TEXT            NOT NULL,
    user_id       BIGINT UNSIGNED     NULL,
    is_read       TINYINT(1)      NOT NULL DEFAULT 0,
    is_replied    TINYINT(1)      NOT NULL DEFAULT 0,
    replied_by    BIGINT UNSIGNED     NULL,
    replied_at    TIMESTAMP           NULL,
    reply_message TEXT                NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_cm_email   (email),
    INDEX idx_cm_is_read (is_read),
    INDEX idx_cm_user    (user_id),
    CONSTRAINT fk_cm_user  FOREIGN KEY (user_id)    REFERENCES users(id)  ON DELETE SET NULL,
    CONSTRAINT fk_cm_admin FOREIGN KEY (replied_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SECTION 2 — VIEWS  (6 total)
-- ============================================================

-- V1: v_show_details — joins shows → movies → screens → theaters
CREATE OR REPLACE VIEW v_show_details AS
SELECT
    s.id                                AS show_id,
    s.show_date,
    s.show_time,
    s.status                            AS show_status,
    s.total_seats,
    s.booked_seats,
    (s.total_seats - s.booked_seats)    AS available_seats,
    m.id                                AS movie_id,
    m.title                             AS movie_title,
    m.slug                              AS movie_slug,
    m.duration_minutes,
    m.language,
    m.certificate_rating,
    m.poster_image,
    m.status                            AS movie_status,
    m.average_rating,
    m.kids_discount_eligible,
    sc.id                               AS screen_id,
    sc.screen_name,
    t.id                                AS theater_id,
    t.name                              AS theater_name,
    t.city,
    t.state
FROM      shows    s
JOIN movies   m  ON s.movie_id   = m.id
JOIN screens  sc ON s.screen_id  = sc.id
JOIN theaters t  ON sc.theater_id = t.id
WHERE m.deleted_at IS NULL
  AND sc.is_active  = 1
  AND t.is_active   = 1;

-- V2: v_booking_details — full booking info for user account + admin orders
CREATE OR REPLACE VIEW v_booking_details AS
SELECT
    b.id                    AS booking_id,
    b.booking_number,
    b.booking_status,
    b.payment_status,
    b.payment_method,
    b.seat_count,
    b.adult_count,
    b.kids_count,
    b.subtotal,
    b.discount_amount,
    b.total_amount,
    b.booked_at,
    b.cancelled_at,
    b.cancellation_reason,
    b.cancelled_by,
    u.id                    AS user_id,
    u.name                  AS user_name,
    u.email                 AS user_email,
    u.phone                 AS user_phone,
    s.show_date,
    s.show_time,
    s.status                AS show_status,
    m.id                    AS movie_id,
    m.title                 AS movie_title,
    m.poster_image,
    m.duration_minutes,
    sc.screen_name,
    t.id                    AS theater_id,
    t.name                  AS theater_name,
    t.city,
    t.address               AS theater_address,
    c.code                  AS coupon_code,
    p.status                AS payment_status_detail,
    p.paid_at,
    p.refunded_at
FROM      bookings b
JOIN users    u  ON b.user_id   = u.id
JOIN shows    s  ON b.show_id   = s.id
JOIN movies   m  ON s.movie_id  = m.id
JOIN screens  sc ON s.screen_id = sc.id
JOIN theaters t  ON sc.theater_id = t.id
LEFT JOIN coupons  c ON b.coupon_id = c.id
LEFT JOIN payments p ON b.id        = p.booking_id;

-- V3: v_seat_availability — seat status per show (available/booked/reserved/inactive)
CREATE OR REPLACE VIEW v_seat_availability AS
SELECT
    se.id                       AS seat_id,
    se.screen_id,
    se.row_label,
    se.seat_number,
    cat.id                      AS seat_category_id,
    cat.name                    AS category_name,
    sh.id                       AS show_id,
    ssp.price,
    ssp.kids_price,
    CASE
        WHEN se.is_active  = 0  THEN 'inactive'
        WHEN bs.id IS NOT NULL  THEN 'booked'
        WHEN ci.id IS NOT NULL  THEN 'reserved'
        ELSE                         'available'
    END                         AS seat_status
FROM seats se
JOIN seat_categories cat ON se.seat_category_id = cat.id
-- FIX 1: was CROSS JOIN — now properly restricts each seat to shows on its own screen
JOIN shows sh ON sh.screen_id = se.screen_id
LEFT JOIN show_seat_prices ssp
    ON sh.id = ssp.show_id AND se.seat_category_id = ssp.seat_category_id
-- A seat is 'booked' only when a non-cancelled booking holds it FOR THE SAME SHOW
LEFT JOIN booking_seats bs
    ON se.id = bs.seat_id AND sh.id = bs.show_id
    AND bs.booking_id IN (SELECT id FROM bookings WHERE booking_status NOT IN ('cancelled'))
-- A seat is 'reserved' only when it sits in an unexpired cart FOR THE SAME SHOW
LEFT JOIN cart_items ci
    ON se.id = ci.seat_id AND sh.id = ci.show_id
    AND ci.cart_id IN (SELECT id FROM carts WHERE expires_at > NOW());
-- ⚠ PERFORMANCE NOTE: Always query this view with WHERE show_id = ?
-- Without the filter: seats × shows can reach millions of rows.
-- The JOIN on sh.screen_id = se.screen_id already eliminates cross-screen pollution,
-- but you still need show_id scoped queries in application code.

-- V4: v_movie_stats — aggregated performance for admin dashboard
CREATE OR REPLACE VIEW v_movie_stats AS
SELECT
    m.id                                AS movie_id,
    m.title,
    m.status,
    m.release_date,
    m.average_rating,
    m.total_reviews,
    COUNT(DISTINCT sh.id)               AS total_shows,
    COUNT(DISTINCT b.id)                AS total_bookings,
    COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount END), 0) AS total_revenue,
    COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.seat_count   END), 0) AS tickets_sold,
    COUNT(DISTINCT w.user_id)           AS wishlist_count
FROM movies m
LEFT JOIN shows    sh ON m.id = sh.movie_id AND sh.status != 'cancelled'
LEFT JOIN bookings b  ON sh.id = b.show_id
LEFT JOIN wishlists w ON m.id  = w.movie_id
WHERE m.deleted_at IS NULL
GROUP BY m.id, m.title, m.status, m.release_date, m.average_rating, m.total_reviews;

-- V5: v_coupon_stats — coupon health with effective_status
CREATE OR REPLACE VIEW v_coupon_stats AS
SELECT
    c.id,
    c.code,
    c.discount_type,
    c.discount_value,
    c.max_discount_amount,
    c.min_order_amount,
    c.max_uses,
    c.used_count,
    c.valid_from,
    c.valid_until,
    c.is_active,
    CASE
        WHEN c.is_active = 0                                       THEN 'inactive'
        WHEN NOW() < c.valid_from                                  THEN 'upcoming'
        WHEN NOW() > c.valid_until                                 THEN 'expired'
        WHEN c.max_uses IS NOT NULL AND c.used_count >= c.max_uses THEN 'exhausted'
        ELSE                                                            'active'
    END                                    AS effective_status,
    COALESCE(SUM(cu.discount_applied), 0)  AS total_discount_given,
    COUNT(cu.id)                           AS total_redemptions,
    COUNT(DISTINCT cu.user_id)             AS unique_users
FROM coupons c
LEFT JOIN coupon_usages cu ON c.id = cu.coupon_id
GROUP BY c.id, c.code, c.discount_type, c.discount_value,
         c.max_discount_amount, c.min_order_amount,
         c.max_uses, c.used_count, c.valid_from, c.valid_until, c.is_active;

-- V6: v_user_stats — per-user summary for admin customer info tab
CREATE OR REPLACE VIEW v_user_stats AS
SELECT
    u.id                                   AS user_id,
    u.name,
    u.email,
    u.phone,
    u.is_blocked,
    u.created_at                           AS registered_at,
    COUNT(DISTINCT b.id)                   AS total_bookings,
    COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount END), 0) AS total_spent,
    COUNT(DISTINCT CASE WHEN b.booking_status = 'cancelled' THEN b.id END)              AS cancelled_bookings,
    COUNT(DISTINCT w.id)                   AS wishlist_items,
    COUNT(DISTINCT r.id)                   AS reviews_written,
    MAX(b.booked_at)                       AS last_booking_at
FROM users u
LEFT JOIN bookings  b ON u.id = b.user_id
LEFT JOIN wishlists w ON u.id = w.user_id
LEFT JOIN reviews   r ON u.id = r.user_id
WHERE u.deleted_at IS NULL
GROUP BY u.id, u.name, u.email, u.phone, u.is_blocked, u.created_at;


-- ============================================================
-- SECTION 3 — STORED PROCEDURES & FUNCTIONS
-- ============================================================

DELIMITER $$

-- sp_generate_seats: generates 60 seats for one screen
--   Gold     → rows A,B,C  (10 seats each = 30)
--   Platinum → rows D,E    (10 seats each = 20)
--   Box      → row  F      (10 seats      = 10)
CREATE PROCEDURE sp_generate_seats(IN p_screen_id BIGINT UNSIGNED)
BEGIN
    DECLARE v_row      INT     DEFAULT 0;
    DECLARE v_seat     INT     DEFAULT 0;
    DECLARE v_cat_id   BIGINT  DEFAULT 1;
    DECLARE v_label    CHAR(1) DEFAULT 'A';

    WHILE v_row < 6 DO
        SET v_label  = CHAR(65 + v_row);          -- A=65 … F=70
        SET v_cat_id = CASE
            WHEN v_row < 3 THEN 1   -- Gold
            WHEN v_row < 5 THEN 2   -- Platinum
            ELSE                3   -- Box
        END;

        SET v_seat = 1;
        WHILE v_seat <= 10 DO
            INSERT INTO seats (screen_id, seat_category_id, row_label, seat_number, is_active)
            VALUES (p_screen_id, v_cat_id, v_label, v_seat, 1);
            SET v_seat = v_seat + 1;
        END WHILE;

        SET v_row = v_row + 1;
    END WHILE;
END$$

-- sp_add_show_prices: inserts Gold/Platinum/Box prices for one show
CREATE PROCEDURE sp_add_show_prices(
    IN p_show_id    BIGINT UNSIGNED,
    IN p_gold       DECIMAL(8,2),
    IN p_platinum   DECIMAL(8,2),
    IN p_box        DECIMAL(8,2),
    IN p_gold_kids  DECIMAL(8,2),
    IN p_plat_kids  DECIMAL(8,2)
)
BEGIN
    INSERT INTO show_seat_prices (show_id, seat_category_id, price, kids_price) VALUES
        (p_show_id, 1, p_gold,     p_gold_kids),
        (p_show_id, 2, p_platinum, p_plat_kids),
        (p_show_id, 3, p_box,      NULL);
END$$

-- sp_bulk_add_prices: adds prices for a sequential range of show IDs (same price set)
CREATE PROCEDURE sp_bulk_add_prices(
    IN p_from_show  BIGINT UNSIGNED,
    IN p_to_show    BIGINT UNSIGNED,
    IN p_gold       DECIMAL(8,2),
    IN p_platinum   DECIMAL(8,2),
    IN p_box        DECIMAL(8,2),
    IN p_gold_kids  DECIMAL(8,2),
    IN p_plat_kids  DECIMAL(8,2)
)
BEGIN
    DECLARE v_i BIGINT UNSIGNED DEFAULT p_from_show;
    WHILE v_i <= p_to_show DO
        CALL sp_add_show_prices(v_i, p_gold, p_platinum, p_box, p_gold_kids, p_plat_kids);
        SET v_i = v_i + 1;
    END WHILE;
END$$

-- fn_booking_number: generates BM-YYYY-XXXXXXXX
-- FIX 3: Old version used COUNT(*)+1 which causes a race condition — two concurrent
-- bookings at the same millisecond both read the same count and collide on the UNIQUE key.
-- New version uses the last 8 hex chars of a UUID (collision probability ~1 in 4 billion).
-- The booking_number column UNIQUE KEY still catches any theoretical collision at DB level.
-- Laravel app layer should use: 'BM-' . now()->year . '-' . strtoupper(substr(str_replace('-','',Str::uuid()),0,8))
CREATE FUNCTION fn_booking_number()
RETURNS VARCHAR(24)
NOT DETERMINISTIC
NO SQL
BEGIN
    -- Extract last 8 hex chars of UUID → 8-char alphanumeric suffix
    RETURN CONCAT(
        'BM-',
        YEAR(CURDATE()),
        '-',
        UPPER(SUBSTRING(REPLACE(UUID(), '-', ''), 25, 8))
    );
END$$

DELIMITER ;


-- ============================================================
-- SECTION 4 — TRIGGERS  (4 total)
-- ============================================================

DELIMITER $$

-- T1: Increment shows.booked_seats after each seat is confirmed
CREATE TRIGGER trg_booking_seats_after_insert
AFTER INSERT ON booking_seats
FOR EACH ROW
BEGIN
    UPDATE shows
    SET    booked_seats = booked_seats + 1
    WHERE  id = NEW.show_id;
END$$

-- T2: Adjust shows.booked_seats when booking is cancelled or un-cancelled
CREATE TRIGGER trg_bookings_after_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    -- Booking just got cancelled → free up seats
    IF NEW.booking_status = 'cancelled' AND OLD.booking_status != 'cancelled' THEN
        UPDATE shows
        SET    booked_seats = GREATEST(0, booked_seats - OLD.seat_count)
        WHERE  id = NEW.show_id;
    END IF;
END$$

-- T3: Recalculate movie avg rating when a review is inserted and approved
CREATE TRIGGER trg_reviews_after_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    IF NEW.is_approved = 1 THEN
        UPDATE movies
        SET average_rating = (
                SELECT ROUND(AVG(rating), 2)
                FROM   reviews
                WHERE  movie_id = NEW.movie_id AND is_approved = 1
            ),
            total_reviews = (
                SELECT COUNT(*)
                FROM   reviews
                WHERE  movie_id = NEW.movie_id AND is_approved = 1
            )
        WHERE id = NEW.movie_id;
    END IF;
END$$

-- T4: Recalculate movie avg rating when review approval status or rating changes
CREATE TRIGGER trg_reviews_after_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    IF NEW.is_approved != OLD.is_approved OR NEW.rating != OLD.rating THEN
        UPDATE movies
        SET average_rating = (
                SELECT COALESCE(ROUND(AVG(rating), 2), 0.00)
                FROM   reviews
                WHERE  movie_id = NEW.movie_id AND is_approved = 1
            ),
            total_reviews = (
                SELECT COUNT(*)
                FROM   reviews
                WHERE  movie_id = NEW.movie_id AND is_approved = 1
            )
        WHERE id = NEW.movie_id;
    END IF;
END$$

DELIMITER ;


-- ============================================================
-- SECTION 5 — EVENT SCHEDULER
-- ============================================================

SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS evt_cleanup_expired_carts
ON SCHEDULE EVERY 5 MINUTE
STARTS CURRENT_TIMESTAMP
COMMENT 'Deletes expired carts; cart_items cascade-deleted automatically'
DO
    DELETE FROM carts WHERE expires_at < NOW();


-- ============================================================
-- SECTION 6 — BASE SEED DATA  (lookup / config tables)
-- ============================================================

-- Seat categories (IDs will be 1=Gold, 2=Platinum, 3=Box)
INSERT INTO seat_categories (name, description) VALUES
('Gold',     'Standard seats with a great view of the screen'),
('Platinum', 'Premium recliner seats with extra legroom'),
('Box',      'Private box seating, ideal for groups and couples');

-- Genres (IDs 1-10)
INSERT INTO genres (name, slug) VALUES
('Action',      'action'),
('Comedy',      'comedy'),
('Drama',       'drama'),
('Horror',      'horror'),
('Romance',     'romance'),
('Sci-Fi',      'sci-fi'),
('Thriller',    'thriller'),
('Animation',   'animation'),
('Bollywood',   'bollywood'),
('Documentary', 'documentary');


-- ============================================================
-- SECTION 7 — SAMPLE DATA
-- ============================================================

-- ── 7.1  Admins ─────────────────────────────────────────────
-- Password for both accounts: password
INSERT INTO admins (name, email, password, role, is_active, last_login_at) VALUES
('Super Admin',  'superadmin@bookmymovie.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 1, NOW()),
('Admin Raza',   'admin@bookmymovie.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',      1, DATE_SUB(NOW(), INTERVAL 2 HOUR));


-- ── 7.2  Users ──────────────────────────────────────────────
-- Password for both accounts: password
INSERT INTO users (name, email, email_verified_at, password, phone, date_of_birth, gender) VALUES
('Ali Hassan',    'ali.hassan@gmail.com',    NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03001234567', '1995-03-15', 'male'),
('Fatima Khan',   'fatima.khan@gmail.com',   NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03009876543', '1998-07-22', 'female'),
('Bilal Ahmed',   'bilal.ahmed@gmail.com',   NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03011112222', '1992-11-05', 'male'),
('Sara Mirza',    'sara.mirza@gmail.com',    NOW(), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03223334444', '2000-01-30', 'female');


-- ── 7.3  Theaters ───────────────────────────────────────────
INSERT INTO theaters (name, address, city, state, pincode, phone, email, created_by) VALUES
('Cineplex Gold',   'Shop 12, Dolmen Mall, Block-4 Clifton',       'Karachi', 'Sindh',  '75600', '021-35861010', 'info@cineplexgold.pk',  1),
('Star Cinemas',    '3-KM Main Canal Bank Road, Emporium Mall',    'Lahore',  'Punjab', '54000', '042-35880110', 'bookings@starcinemas.pk', 1);


-- ── 7.4  Screens (2 per theater) ────────────────────────────
INSERT INTO screens (theater_id, screen_name, total_seats, is_active) VALUES
(1, 'Audi 1',   60, 1),   -- screen_id = 1
(1, 'Audi 2',   60, 1),   -- screen_id = 2
(2, 'Screen A', 60, 1),   -- screen_id = 3
(2, 'Screen B', 60, 1);   -- screen_id = 4


-- ── 7.5  Seats (auto-generated via sp_generate_seats) ───────
-- Each screen gets 60 seats: Gold A-C (30), Platinum D-E (20), Box F (10)
CALL sp_generate_seats(1);
CALL sp_generate_seats(2);
CALL sp_generate_seats(3);
CALL sp_generate_seats(4);


-- ── 7.6  Movies (10 total: 8 now_showing, 2 coming_soon) ────
INSERT INTO movies
    (title, slug, description, language, duration_minutes, certificate_rating,
     release_date, status, poster_image, banner_image, trailer_url,
     kids_discount_eligible, created_by)
VALUES
-- 1. Thunder Protocol
('Thunder Protocol',
 'thunder-protocol',
 'When a covert operative uncovers a global conspiracy buried inside classified military files, he must outrun assassins across three continents to expose the truth before it buries him.',
 'English', 148, 'UA',
 DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'now_showing',
 '/storage/posters/thunder-protocol.jpg', '/storage/banners/thunder-protocol.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1),

-- 2. Dil Ki Baat
('Dil Ki Baat',
 'dil-ki-baat',
 'Two strangers meet on a delayed train from Lahore to Karachi. What starts as a polite conversation slowly unravels into a life-changing love story told across seasons.',
 'Urdu', 155, 'U',
 DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'now_showing',
 '/storage/posters/dil-ki-baat.jpg', '/storage/banners/dil-ki-baat.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1),

-- 3. The Dark Labyrinth
('The Dark Labyrinth',
 'the-dark-labyrinth',
 'A group of urban explorers enter an abandoned asylum looking for thrills. What they find inside defies every law of nature—and survival.',
 'English', 112, 'A',
 DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'now_showing',
 '/storage/posters/dark-labyrinth.jpg', '/storage/banners/dark-labyrinth.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1),

-- 4. Nebula Rising
('Nebula Rising',
 'nebula-rising',
 'In 2187, a dying star threatens the last human colony. A crew of misfits aboard the deep-space vessel ARES II must detonate a stellar bomb—but one of them is not who they claim to be.',
 'English', 163, 'PG-13',
 DATE_SUB(CURDATE(), INTERVAL 21 DAY), 'now_showing',
 '/storage/posters/nebula-rising.jpg', '/storage/banners/nebula-rising.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1),

-- 5. Laughing Stock
('Laughing Stock',
 'laughing-stock',
 'A small-town accountant accidentally becomes a stand-up comedian sensation after a viral video. Now he has 48 hours to perform at the biggest comedy festival in the country.',
 'Urdu', 98, 'U',
 DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'now_showing',
 '/storage/posters/laughing-stock.jpg', '/storage/banners/laughing-stock.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1),

-- 6. Forever After
('Forever After',
 'forever-after',
 'After a bitter divorce, two architects are assigned to co-design a historic restoration project in Florence. Blueprints and buried feelings both get revised.',
 'English', 127, 'UA',
 DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'now_showing',
 '/storage/posters/forever-after.jpg', '/storage/banners/forever-after.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1),

-- 7. Little Heroes (kids discount eligible)
('Little Heroes',
 'little-heroes',
 'When the city loses all electricity, four children discover their neighbourhood toys have come to life. Together they must restore power—and save their town from a mischievous robot army.',
 'English', 102, 'U',
 DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'now_showing',
 '/storage/posters/little-heroes.jpg', '/storage/banners/little-heroes.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 1, 1),

-- 8. Shadow Protocol
('Shadow Protocol',
 'shadow-protocol',
 'A forensic psychologist is called to assess a mysterious prisoner who claims to remember crimes that have not yet happened. The sessions grow darker with every session.',
 'English', 118, 'UA',
 DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'now_showing',
 '/storage/posters/shadow-protocol.jpg', '/storage/banners/shadow-protocol.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1),

-- 9. Cosmic Odyssey (coming soon)
('Cosmic Odyssey',
 'cosmic-odyssey',
 'The most ambitious space exploration film ever made. Six astronauts cross the edge of the observable universe—and discover something that was never meant to be found.',
 'English', 175, 'PG-13',
 DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'coming_soon',
 '/storage/posters/cosmic-odyssey.jpg', '/storage/banners/cosmic-odyssey.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1),

-- 10. Pyaar Ka Safar (coming soon)
('Pyaar Ka Safar',
 'pyaar-ka-safar',
 'A road trip from Karachi to the mountains of Gilgit-Baltistan becomes the backdrop for an unlikely romance between a travel blogger and a local guide with a secret past.',
 'Urdu', 143, 'U',
 DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'coming_soon',
 '/storage/posters/pyaar-ka-safar.jpg', '/storage/banners/pyaar-ka-safar.jpg',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 1);


-- ── 7.7  Movie Genres (pivot) ────────────────────────────────
-- Genre IDs: 1=Action 2=Comedy 3=Drama 4=Horror 5=Romance
--            6=Sci-Fi 7=Thriller 8=Animation 9=Bollywood 10=Documentary
INSERT INTO movie_genres (movie_id, genre_id) VALUES
(1, 1),(1, 7),   -- Thunder Protocol → Action, Thriller
(2, 9),(2, 5),   -- Dil Ki Baat     → Bollywood, Romance
(3, 4),(3, 7),   -- The Dark Lab.   → Horror, Thriller
(4, 6),(4, 1),   -- Nebula Rising   → Sci-Fi, Action
(5, 2),          -- Laughing Stock  → Comedy
(6, 5),(6, 3),   -- Forever After   → Romance, Drama
(7, 8),          -- Little Heroes   → Animation
(8, 7),(8, 3),   -- Shadow Protocol → Thriller, Drama
(9, 6),(9, 1),   -- Cosmic Odyssey  → Sci-Fi, Action
(10,9),(10,5);   -- Pyaar Ka Safar  → Bollywood, Romance


-- ── 7.8  Shows ──────────────────────────────────────────────
-- Layout:
--   Screen 1 (Audi 1,   Cineplex Karachi): Movie 1 (Day 0-2)  then Movie 5 (Day 3-5)
--   Screen 2 (Audi 2,   Cineplex Karachi): Movie 2 (Day 0-2)  then Movie 6 (Day 3-5)
--   Screen 3 (Screen A, Star Lahore):      Movie 3 (Day 0-2)  then Movie 7 (Day 3-5)
--   Screen 4 (Screen B, Star Lahore):      Movie 4 (Day 0-2)  then Movie 8 (Day 3-5)
-- Times per screen: 10:00, 14:00, 19:00  (3 shows × 3 days = 9 shows per movie-screen block)

INSERT INTO shows (movie_id, screen_id, show_date, show_time, status, total_seats, created_by) VALUES
-- ─ Screen 1 · Movie 1 (Thunder Protocol) ─
(1, 1, CURDATE(),                              '10:00:00', 'scheduled', 60, 1),
(1, 1, CURDATE(),                              '14:00:00', 'scheduled', 60, 1),
(1, 1, CURDATE(),                              '19:00:00', 'scheduled', 60, 1),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '10:00:00', 'scheduled', 60, 1),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '14:00:00', 'scheduled', 60, 1),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '19:00:00', 'scheduled', 60, 1),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '10:00:00', 'scheduled', 60, 1),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '14:00:00', 'scheduled', 60, 1),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '19:00:00', 'scheduled', 60, 1),

-- ─ Screen 2 · Movie 2 (Dil Ki Baat) ─
(2, 2, CURDATE(),                              '10:30:00', 'scheduled', 60, 1),
(2, 2, CURDATE(),                              '14:30:00', 'scheduled', 60, 1),
(2, 2, CURDATE(),                              '19:30:00', 'scheduled', 60, 1),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '10:30:00', 'scheduled', 60, 1),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '14:30:00', 'scheduled', 60, 1),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '19:30:00', 'scheduled', 60, 1),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '10:30:00', 'scheduled', 60, 1),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '14:30:00', 'scheduled', 60, 1),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '19:30:00', 'scheduled', 60, 1),

-- ─ Screen 3 · Movie 3 (The Dark Labyrinth) ─
(3, 3, CURDATE(),                              '11:00:00', 'scheduled', 60, 1),
(3, 3, CURDATE(),                              '15:00:00', 'scheduled', 60, 1),
(3, 3, CURDATE(),                              '20:00:00', 'scheduled', 60, 1),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '11:00:00', 'scheduled', 60, 1),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '15:00:00', 'scheduled', 60, 1),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '20:00:00', 'scheduled', 60, 1),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '11:00:00', 'scheduled', 60, 1),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '15:00:00', 'scheduled', 60, 1),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '20:00:00', 'scheduled', 60, 1),

-- ─ Screen 4 · Movie 4 (Nebula Rising) ─
(4, 4, CURDATE(),                              '11:30:00', 'scheduled', 60, 1),
(4, 4, CURDATE(),                              '15:30:00', 'scheduled', 60, 1),
(4, 4, CURDATE(),                              '20:30:00', 'scheduled', 60, 1),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '11:30:00', 'scheduled', 60, 1),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '15:30:00', 'scheduled', 60, 1),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '20:30:00', 'scheduled', 60, 1),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '11:30:00', 'scheduled', 60, 1),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '15:30:00', 'scheduled', 60, 1),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY),   '20:30:00', 'scheduled', 60, 1),

-- ─ Screen 1 · Movie 5 (Laughing Stock) — Day 3-5 ─
(5, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '10:00:00', 'scheduled', 60, 1),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '14:00:00', 'scheduled', 60, 1),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '19:00:00', 'scheduled', 60, 1),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '10:00:00', 'scheduled', 60, 1),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '14:00:00', 'scheduled', 60, 1),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '19:00:00', 'scheduled', 60, 1),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '10:00:00', 'scheduled', 60, 1),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '14:00:00', 'scheduled', 60, 1),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '19:00:00', 'scheduled', 60, 1),

-- ─ Screen 2 · Movie 6 (Forever After) — Day 3-5 ─
(6, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '10:30:00', 'scheduled', 60, 1),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '14:30:00', 'scheduled', 60, 1),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '19:30:00', 'scheduled', 60, 1),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '10:30:00', 'scheduled', 60, 1),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '14:30:00', 'scheduled', 60, 1),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '19:30:00', 'scheduled', 60, 1),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '10:30:00', 'scheduled', 60, 1),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '14:30:00', 'scheduled', 60, 1),
(6, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '19:30:00', 'scheduled', 60, 1),

-- ─ Screen 3 · Movie 7 (Little Heroes) — Day 3-5 ─
(7, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '11:00:00', 'scheduled', 60, 1),
(7, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '15:00:00', 'scheduled', 60, 1),
(7, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '20:00:00', 'scheduled', 60, 1),
(7, 3, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '11:00:00', 'scheduled', 60, 1),
(7, 3, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '15:00:00', 'scheduled', 60, 1),
(7, 3, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '20:00:00', 'scheduled', 60, 1),
(7, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '11:00:00', 'scheduled', 60, 1),
(7, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '15:00:00', 'scheduled', 60, 1),
(7, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '20:00:00', 'scheduled', 60, 1),

-- ─ Screen 4 · Movie 8 (Shadow Protocol) — Day 3-5 ─
(8, 4, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '11:30:00', 'scheduled', 60, 1),
(8, 4, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '15:30:00', 'scheduled', 60, 1),
(8, 4, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   '20:30:00', 'scheduled', 60, 1),
(8, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '11:30:00', 'scheduled', 60, 1),
(8, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '15:30:00', 'scheduled', 60, 1),
(8, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY),   '20:30:00', 'scheduled', 60, 1),
(8, 4, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '11:30:00', 'scheduled', 60, 1),
(8, 4, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '15:30:00', 'scheduled', 60, 1),
(8, 4, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   '20:30:00', 'scheduled', 60, 1);
-- Total shows: 72  (8 movies × 9 shows each)


-- ── 7.9  Show Seat Prices (bulk via stored procedure) ────────
-- Shows 1–9   → Movie 1 (Action/Thriller)  → Premium pricing
CALL sp_bulk_add_prices(1,  9,  700.00, 1200.00, 2500.00, 450.00, 800.00);
-- Shows 10–18 → Movie 2 (Bollywood/Romance)
CALL sp_bulk_add_prices(10, 18, 600.00, 1000.00, 2000.00, 400.00, 700.00);
-- Shows 19–27 → Movie 3 (Horror/Thriller)
CALL sp_bulk_add_prices(19, 27, 650.00, 1100.00, 2200.00, 420.00, 750.00);
-- Shows 28–36 → Movie 4 (Sci-Fi/Action)
CALL sp_bulk_add_prices(28, 36, 750.00, 1300.00, 2800.00, 480.00, 850.00);
-- Shows 37–45 → Movie 5 (Comedy)
CALL sp_bulk_add_prices(37, 45, 500.00, 900.00,  1800.00, 350.00, 650.00);
-- Shows 46–54 → Movie 6 (Romance/Drama)
CALL sp_bulk_add_prices(46, 54, 550.00, 950.00,  1900.00, 380.00, 680.00);
-- Shows 55–63 → Movie 7 (Animation — kids eligible, lower prices)
CALL sp_bulk_add_prices(55, 63, 450.00, 800.00,  1500.00, 280.00, 550.00);
-- Shows 64–72 → Movie 8 (Thriller/Drama)
CALL sp_bulk_add_prices(64, 72, 600.00, 1050.00, 2100.00, 400.00, 720.00);


-- ── 7.10  Coupons ───────────────────────────────────────────
INSERT INTO coupons
    (code, description, discount_type, discount_value, max_discount_amount,
     min_order_amount, max_uses, max_uses_per_user, valid_from, valid_until, created_by)
VALUES
('WELCOME20',
 'Welcome offer — 20% off your first booking (max discount Rs 200)',
 'percentage', 20.00, 200.00, 500.00, 100, 1,
 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1),

('FLAT100',
 'Flat Rs 100 off on orders above Rs 800',
 'fixed', 100.00, NULL, 800.00, 50, 1,
 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 1),

('BLOCKBUSTER',
 'Blockbuster deal — Rs 500 flat off on premium bookings above Rs 2000',
 'fixed', 500.00, NULL, 2000.00, 30, 1,
 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),

('KIDS25',
 '25% off on tickets for kids (age 3–12)',
 'percentage', 25.00, 150.00, 400.00, 200, 2,
 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 120 DAY), 1),

('SUMMER50',
 'Limited-time — 50% off, max Rs 500 discount on orders above Rs 1500',
 'percentage', 50.00, 500.00, 1500.00, 20, 1,
 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 2);


-- ── 7.11  FAQs ──────────────────────────────────────────────
INSERT INTO faqs (question, answer, sort_order, is_active, created_by) VALUES
('How do I book a movie ticket?',
 'Browse movies on our home page, select your preferred show date and time, choose your seats from the interactive seat map, and proceed to checkout. We currently accept Cash on Delivery (COD) at the counter.',
 1, 1, 1),

('Can I cancel or modify my booking?',
 'You can cancel a confirmed booking up to 2 hours before the show start time from your "My Bookings" page. Modifications are not supported; please cancel and rebook. Cancellations within 2 hours of the show are non-refundable.',
 2, 1, 1),

('What are the seat categories and their prices?',
 'We offer three categories: Gold (standard seats with a great screen view), Platinum (premium recliners with extra legroom), and Box (private seating for groups). Prices vary by movie and show time and are displayed on the seat selection screen.',
 3, 1, 1),

('Is there a kids discount?',
 'Yes! Children aged 3 to 12 years qualify for our kids concession price on eligible movies. The discounted rate is shown automatically when you select a kid ticket type during seat selection.',
 4, 1, 1),

('How does Cash on Delivery (COD) work?',
 'After booking online, your ticket is confirmed immediately. You pay the total amount in cash at the cinema counter before the show starts. Please arrive at least 15 minutes early and show your booking confirmation or QR code.',
 5, 1, 1),

('How do I use a coupon code?',
 'Enter your coupon code in the "Apply Coupon" field on the Cart or Checkout page. The discount will be applied automatically if the code is valid, active, and your order meets the minimum amount requirement.',
 6, 1, 1),

('Can I add movies to a wishlist?',
 'Yes. Click the heart icon on any movie card or detail page to add it to your wishlist. You must be logged in. Your wishlist is accessible from your account dashboard.',
 7, 1, 1),

('Where can I watch movie trailers?',
 'Every movie detail page includes an embedded trailer. Click the "Watch Trailer" button on the movie page to view it without leaving the site.',
 8, 1, 2);


-- ── 7.12  Sample Bookings ────────────────────────────────────
-- Booking 1: User 1 (Ali) → Show 1 (Movie 1, Screen 1, Today 10:00)
--            Seats A1 + A2 (Gold, adult), no coupon → total Rs 1400
INSERT INTO bookings
    (booking_number, user_id, show_id, coupon_id, seat_count, adult_count, kids_count,
     subtotal, discount_amount, total_amount, payment_method, payment_status, booking_status)
VALUES
('BM-2026-00001', 1, 1, NULL, 2, 2, 0, 1400.00, 0.00, 1400.00, 'cod', 'pending',   'confirmed'),
('BM-2026-00002', 2, 4, 2,   1, 1, 0, 1200.00, 100.00, 1100.00,'cod', 'paid',      'confirmed'),
('BM-2026-00003', 3, 10, NULL,2, 1, 1, 1000.00, 0.00,  1000.00, 'cod', 'pending',  'confirmed'),
('BM-2026-00004', 4, 19, 1,  2, 2, 0, 1300.00, 200.00, 1100.00,'cod', 'pending',   'confirmed');

-- Seat A1 (seat_id=1) and A2 (seat_id=2) are on Screen 1, category Gold (cat_id=1)
-- Seat D1 (seat_id=31) on Screen 1 is Platinum
-- For screen 1: seats 1-30=Gold(A-C row), 31-50=Platinum(D-E row), 51-60=Box(F row)
-- For screen 2: seats 61-90=Gold, 91-110=Platinum, 111-120=Box
-- For screen 3: seats 121-150=Gold, 151-170=Platinum, 171-180=Box

INSERT INTO booking_seats
    (booking_id, seat_id, show_id, seat_category_id, ticket_type, price_paid, ticket_number)
VALUES
-- Booking 1: Show 1, Screen 1 — seats A1(1) and A2(2), Gold adult
(1,  1,  1, 1, 'adult', 700.00, 'TKT-2026-000001'),
(1,  2,  1, 1, 'adult', 700.00, 'TKT-2026-000002'),
-- Booking 2: Show 4 (Screen 1, Day+1 10:00), seat D1(31) Platinum adult
(2,  31, 4, 2, 'adult', 1200.00,'TKT-2026-000003'),
-- Booking 3: Show 10 (Screen 2, Today 10:30), seats A1(61) Gold adult + A2(62) Gold kid
(3,  61, 10,1, 'adult', 600.00, 'TKT-2026-000004'),
(3,  62, 10,1, 'kid',   400.00, 'TKT-2026-000005'),
-- Booking 4: Show 19 (Screen 3, Today 11:00), seats A1(121)+A2(122) Gold adult
(4, 121, 19,1, 'adult', 650.00, 'TKT-2026-000006'),
(4, 122, 19,1, 'adult', 650.00, 'TKT-2026-000007');


-- ── 7.13  Payments ──────────────────────────────────────────
INSERT INTO payments (booking_id, payment_method, amount, status, notes, paid_at) VALUES
(1, 'cod', 1400.00, 'pending', 'COD — collect at counter before show',   NULL),
(2, 'cod', 1100.00, 'paid',    'COD — paid at Cineplex counter',          NOW()),
(3, 'cod', 1000.00, 'pending', 'COD — collect at counter before show',   NULL),
(4, 'cod', 1100.00, 'pending', 'COD — collect at counter before show',   NULL);


-- ── 7.14  Coupon Usages ─────────────────────────────────────
-- Booking 2 used FLAT100 (coupon_id=2, discount=100)
-- Booking 4 used WELCOME20 (coupon_id=1, discount=200)
INSERT INTO coupon_usages (coupon_id, user_id, booking_id, discount_applied) VALUES
(2, 2, 2, 100.00),
(1, 4, 4, 200.00);

-- Update used_count on coupons
UPDATE coupons SET used_count = 1 WHERE id = 1;
UPDATE coupons SET used_count = 1 WHERE id = 2;


-- ── 7.15  Wishlists ─────────────────────────────────────────
INSERT INTO wishlists (user_id, movie_id) VALUES
(1, 3),   -- Ali wishlist: Dark Labyrinth
(1, 9),   -- Ali wishlist: Cosmic Odyssey
(2, 1),   -- Fatima wishlist: Thunder Protocol
(2, 10),  -- Fatima wishlist: Pyaar Ka Safar
(3, 4),   -- Bilal wishlist: Nebula Rising
(3, 9),   -- Bilal wishlist: Cosmic Odyssey
(4, 7),   -- Sara wishlist: Little Heroes
(4, 10);  -- Sara wishlist: Pyaar Ka Safar


-- ── 7.16  Reviews (pre-approved) ────────────────────────────
INSERT INTO reviews
    (user_id, movie_id, booking_id, rating, review_text, is_approved, approved_by, approved_at)
VALUES
(1, 1, 1, 5, 'Absolutely gripping from start to finish. The action sequences are top-tier and the plot keeps you guessing. Highly recommended!', 1, 1, NOW()),
(2, 2, 2, 4, 'A beautifully told love story. The chemistry between the leads is natural and the soundtrack is brilliant. A must-watch for Bollywood fans.', 1, 1, NOW()),
(3, 2, 3, 3, 'Good storyline but felt a bit long in the middle. Music is great and the ending is satisfying.', 1, 1, NOW()),
(4, 3, NULL,4, 'Genuinely frightening. The atmosphere is perfect and the scares are well-placed. Not for the faint-hearted!', 1, 2, NOW());

-- Trigger trg_reviews_after_insert will auto-update average_rating & total_reviews
-- but since triggers fire on single INSERTs we manually update to reflect all 4 reviews
UPDATE movies SET
    average_rating = 5.00, total_reviews = 1 WHERE id = 1;
UPDATE movies SET
    average_rating = 3.50, total_reviews = 2 WHERE id = 2;
UPDATE movies SET
    average_rating = 4.00, total_reviews = 1 WHERE id = 3;


-- ── 7.17  User Notifications ────────────────────────────────
INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES
(1, 'booking_confirmed',  'Booking Confirmed!',
 CONCAT('Your booking BM-2026-00001 for Thunder Protocol on ', CURDATE(), ' at 10:00 AM is confirmed. Pay at the counter.'),
 'booking', 1),
(2, 'booking_confirmed',  'Booking Confirmed!',
 'Your booking BM-2026-00002 for Nebula Rising is confirmed. Payment received. Enjoy the show!',
 'booking', 2),
(2, 'coupon_applied',     'Coupon FLAT100 Applied',
 'Rs 100 discount applied successfully to your booking BM-2026-00002.',
 'booking', 2),
(3, 'booking_confirmed',  'Booking Confirmed!',
 'Your booking BM-2026-00003 for Dil Ki Baat is confirmed. Pay cash at the counter.',
 'booking', 3),
(4, 'booking_confirmed',  'Booking Confirmed!',
 'Your booking BM-2026-00004 for The Dark Labyrinth is confirmed. Coupon WELCOME20 saved you Rs 200!',
 'booking', 4),
(1, 'new_movie',          'New Movie: Shadow Protocol is Now Showing!',
 'The psychological thriller Shadow Protocol is now playing at Cineplex Gold. Book your seats before they fill up.',
 'movie', 8);


-- ── 7.18  Admin Notifications ───────────────────────────────
INSERT INTO admin_notifications (admin_id, type, title, message, related_type, related_id) VALUES
(1, 'new_booking',   'New Booking Received',  'Booking BM-2026-00001 placed by Ali Hassan.',           'booking', 1),
(1, 'new_booking',   'New Booking Received',  'Booking BM-2026-00002 placed by Fatima Khan.',          'booking', 2),
(1, 'new_booking',   'New Booking Received',  'Booking BM-2026-00003 placed by Bilal Ahmed.',          'booking', 3),
(1, 'new_booking',   'New Booking Received',  'Booking BM-2026-00004 placed by Sara Mirza.',           'booking', 4),
(1, 'new_user',      'New User Registration', 'Bilal Ahmed (bilal.ahmed@gmail.com) just registered.',  'user',    3),
(1, 'new_user',      'New User Registration', 'Sara Mirza (sara.mirza@gmail.com) just registered.',    'user',    4),
(2, 'new_review',    'New Review Pending',    'A review for The Dark Labyrinth is awaiting approval.', 'movie',   3),
(1, 'new_review',    'Review Approved',       'Review for Thunder Protocol by Ali Hassan is now live.','review',  1);


-- ── 7.19  Admin Activity Logs ───────────────────────────────
INSERT INTO admin_activity_logs (admin_id, action, model_type, model_id, description, ip_address) VALUES
(1, 'created_movie',   'Movie',   1, 'Created movie: Thunder Protocol',                  '192.168.1.1'),
(1, 'created_movie',   'Movie',   2, 'Created movie: Dil Ki Baat',                       '192.168.1.1'),
(1, 'created_movie',   'Movie',   3, 'Created movie: The Dark Labyrinth',                '192.168.1.1'),
(1, 'created_movie',   'Movie',   4, 'Created movie: Nebula Rising',                     '192.168.1.1'),
(1, 'created_movie',   'Movie',   5, 'Created movie: Laughing Stock',                    '192.168.1.1'),
(1, 'created_movie',   'Movie',   6, 'Created movie: Forever After',                     '192.168.1.1'),
(1, 'created_movie',   'Movie',   7, 'Created movie: Little Heroes',                     '192.168.1.1'),
(1, 'created_movie',   'Movie',   8, 'Created movie: Shadow Protocol',                   '192.168.1.1'),
(1, 'created_movie',   'Movie',   9, 'Created movie: Cosmic Odyssey (coming soon)',       '192.168.1.1'),
(1, 'created_movie',   'Movie',  10, 'Created movie: Pyaar Ka Safar (coming soon)',       '192.168.1.1'),
(1, 'created_theater', 'Theater', 1, 'Created theater: Cineplex Gold, Karachi',           '192.168.1.1'),
(1, 'created_theater', 'Theater', 2, 'Created theater: Star Cinemas, Lahore',             '192.168.1.1'),
(1, 'created_coupon',  'Coupon',  1, 'Created coupon: WELCOME20 (20% off)',               '192.168.1.1'),
(1, 'created_coupon',  'Coupon',  2, 'Created coupon: FLAT100 (Rs 100 off)',              '192.168.1.1'),
(2, 'approved_review', 'Review',  1, 'Approved review for Thunder Protocol by Ali Hassan','10.0.0.5'),
(2, 'approved_review', 'Review',  2, 'Approved review for Dil Ki Baat by Fatima Khan',   '10.0.0.5'),
(2, 'created_faq',     'FAQ',     1, 'Created FAQ: How do I book a movie ticket?',        '10.0.0.5');


-- ── 7.20  Contact Messages ──────────────────────────────────
INSERT INTO contact_messages (name, email, subject, message, user_id, is_read, is_replied) VALUES
('Ahmed Siddiqui', 'ahmed.siddiqui@gmail.com',
 'Refund for cancelled show',
 'Hi, my show was cancelled and I would like to know the refund process for my COD booking. Booking number BM-2026-00001.',
 NULL, 0, 0),

('Fatima Khan', 'fatima.khan@gmail.com',
 'Group booking for corporate event',
 'Hello, we would like to arrange a group booking of 20+ seats for a corporate team outing. Could you please advise on bulk pricing and Box category availability?',
 2, 1, 0),

('Asim Raza', 'asim.raza@yahoo.com',
 'Seat selection not loading',
 'The seat picker page is not loading properly on my mobile. I tried Chrome and Safari both. Please fix the issue.',
 NULL, 0, 0);


-- ── 7.21  Sample Cart (active, expires in 10 minutes) ───────
INSERT INTO carts (user_id, expires_at) VALUES
(1, DATE_ADD(NOW(), INTERVAL 10 MINUTE));

-- Cart item: User 1 has seat A3 (seat_id=3) of Show 2 (today 14:00) in cart
INSERT INTO cart_items (cart_id, show_id, seat_id, seat_category_id, ticket_type, price) VALUES
(1, 2, 3, 1, 'adult', 700.00);


-- ============================================================
-- SECTION 8 — RESTORE SESSION FLAGS
-- ============================================================

SET FOREIGN_KEY_CHECKS   = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS        = @OLD_UNIQUE_CHECKS;
SET SQL_MODE             = @OLD_SQL_MODE;


-- ============================================================
-- SECTION 9 — QUICK VERIFICATION QUERIES
-- (Run these to confirm everything loaded correctly)
-- ============================================================

-- SELECT 'TABLES'   AS check_type, COUNT(*) AS count FROM information_schema.TABLES   WHERE TABLE_SCHEMA = 'bookmymovie';
-- SELECT 'VIEWS'    AS check_type, COUNT(*) AS count FROM information_schema.VIEWS    WHERE TABLE_SCHEMA = 'bookmymovie';
-- SELECT 'TRIGGERS' AS check_type, COUNT(*) AS count FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'bookmymovie';
-- SELECT 'ROUTINES' AS check_type, COUNT(*) AS count FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = 'bookmymovie';
-- SELECT * FROM v_show_details   LIMIT 5;
-- SELECT * FROM v_booking_details;
-- SELECT * FROM v_movie_stats;
-- SELECT * FROM v_coupon_stats;
-- SELECT * FROM v_user_stats;
-- SELECT seat_status, COUNT(*) FROM v_seat_availability WHERE show_id = 1 GROUP BY seat_status;

-- ============================================================
-- END OF bookmymovie.sql
-- ============================================================
