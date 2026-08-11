-- Integration tests run against real PostgreSQL, never SQLite. A separate
-- database makes it impossible for a test run to point at the dev data.
CREATE DATABASE kms_test OWNER kms;
