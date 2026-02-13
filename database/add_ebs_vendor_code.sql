-- Migration: Add EBS Vendor Code column to suppliers table
-- Run this on existing databases to add the new column without recreating the table

USE vendor_management;

ALTER TABLE suppliers ADD COLUMN ebs_vendor_code VARCHAR(50) NULL AFTER l3_comments;
