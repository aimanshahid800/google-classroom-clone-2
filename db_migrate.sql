-- Migration: Add description column to assignments table
-- Run this if you already have the database set up from a previous version

ALTER TABLE assignments ADD COLUMN description TEXT DEFAULT NULL AFTER title;
