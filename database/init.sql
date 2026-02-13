-- JAGATJIT INDUSTRIES LIMITED
-- Vendor Management System (VMS) - Unified Database Schema

CREATE DATABASE IF NOT EXISTS vendor_management;
USE vendor_management;

-- 1. INTERNAL STAFF USERS (RBAC)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('PURCHASER', 'FINANCE', 'IT', 'ADMIN') NOT NULL,
    force_password_change TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Staff Credentials (Password: 'password')
INSERT IGNORE INTO users (username, password_hash, role) VALUES 
('purchaser.jil@swanrose.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PURCHASER'),
('finance.jil@swanrose.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'FINANCE'),
('it.jil@swanrose.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'IT'),
('admin.jil@swanrose.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN');

-- 2. SUPPLIERS / VENDORS
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Account Credentials
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    
    -- Status & Workflow
    status ENUM('DRAFT', 'SUBMITTED', 'APPROVED_L1', 'APPROVED_L2', 'ACTIVE', 'REJECTED') DEFAULT 'DRAFT',
    rejection_reason TEXT,
    
    -- Approval Tracking
    l1_approved_by INT NULL,
    l1_approved_at DATETIME NULL,
    l2_approved_by INT NULL,
    l2_approved_at DATETIME NULL,
    l3_approved_by INT NULL,
    l3_approved_at DATETIME NULL,

    -- Business/Company Detail
    company_name VARCHAR(255) NULL,
    company_address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    supplier_website VARCHAR(255) NULL,
    nature_of_business TEXT NULL, 
    product_services_type VARCHAR(255) NULL,
    market_type ENUM('Domestic', 'International', 'Both') DEFAULT 'Domestic',
    
    -- Communication Detail
    contact_first_name VARCHAR(100) NULL,
    contact_middle_name VARCHAR(100) NULL,
    contact_last_name VARCHAR(100) NULL,
    mobile_number VARCHAR(20) NULL,
    alt_mobile_number VARCHAR(20) NULL,
    
    -- Business Contact Detail
    landline_number VARCHAR(20) NULL,
    
    -- Internal Process
    supplier_type VARCHAR(100) NULL,
    item_main_group VARCHAR(100) NULL,
    
    -- Tax & Legal Detail
    registered_msme ENUM('Yes', 'No') DEFAULT 'No',
    msme_reg_number VARCHAR(50) NULL,
    msme_type VARCHAR(50) NULL,
    itr_status VARCHAR(50) NULL,
    pan_number VARCHAR(20) NULL,
    under_gst ENUM('Yes', 'No') DEFAULT 'No',
    gst_reg_number VARCHAR(20) NULL, 
    tan_number VARCHAR(20) NULL,
    
    -- Bank Detail
    bank_name VARCHAR(255) NULL,
    account_type VARCHAR(50) NULL,
    account_number VARCHAR(100) NULL,
    ifsc_code VARCHAR(20) NULL,
    bank_branch_address TEXT NULL,
    bank_city VARCHAR(100) NULL,
    bank_state VARCHAR(100) NULL,
    bank_postal_code VARCHAR(20) NULL,

    -- Risk Compliance
    risk_classification ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
    declaration_accepted TINYINT(1) DEFAULT 0,

    -- Document Paths
    pan_card_doc VARCHAR(255) NULL,
    gst_cert_doc VARCHAR(255) NULL,
    cancelled_cheque_doc VARCHAR(255) NULL,
    msme_cert_doc VARCHAR(255) NULL,

    -- Reset Flow
    otp VARCHAR(10) NULL,
    otp_expiry DATETIME NULL,
    
    -- Internal Team Comments
    l1_comments TEXT NULL,
    l2_comments TEXT NULL,
    l3_comments TEXT NULL,

    -- EBS Vendor Code (assigned by IT Team on final approval)
    ebs_vendor_code VARCHAR(50) NULL,
    
    -- Document Availability Flags
    has_pan ENUM('Yes', 'No') DEFAULT 'Yes',
    has_gst ENUM('Yes', 'No') DEFAULT 'Yes',
    has_cheque ENUM('Yes', 'No') DEFAULT 'Yes',
    has_msme ENUM('Yes', 'No') DEFAULT 'No',

    force_password_change TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (l1_approved_by) REFERENCES users(id),
    FOREIGN KEY (l2_approved_by) REFERENCES users(id),
    FOREIGN KEY (l3_approved_by) REFERENCES users(id)
);
