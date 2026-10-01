<?php
declare(strict_types=1);

// One timezone for the whole app. PHP and MySQL must agree on what "today" is.
date_default_timezone_set('Asia/Kolkata');

const APP_NAME     = 'Clinic Appointment System';
// Appointment slots
const SLOT_MINUTES     = 30;      // length of one appointment
const CLINIC_OPEN      = '09:00'; // first slot starts here
const CLINIC_CLOSE     = '17:00'; // last slot must END by here (so it starts 16:30)
const MAX_BOOKING_DAYS = 365;     // how far ahead a booking may be made

// Database
const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'clinic_db';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

// URL path under which the public/ folder is served (no trailing slash).
//  - XAMPP (htdocs/clinic-appointment-system/public):  '/clinic-appointment-system/public'
//  - php -S localhost:8000 -t public                  :  ''
const BASE_URL = '/clinic-appointment-system/public';
