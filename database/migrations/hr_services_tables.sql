-- ============================================================
-- Migration: HR Services Management System
-- Database: Microsoft SQL Server
-- Date: 2026-06-30
-- ============================================================

-- 1. Tabella hr_service_types
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='hr_service_types' AND xtype='U')
BEGIN
    CREATE TABLE hr_service_types (
        id UNIQUEIDENTIFIER NOT NULL PRIMARY KEY,
        name NVARCHAR(255) NOT NULL UNIQUE,
        description NVARCHAR(255) NULL,
        icon NVARCHAR(255) NULL,
        disabled BIT NOT NULL DEFAULT 0,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
END
GO

-- 2. Tabella hr_services
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='hr_services' AND xtype='U')
BEGIN
    CREATE TABLE hr_services (
        id UNIQUEIDENTIFIER NOT NULL PRIMARY KEY,
        service_type_id UNIQUEIDENTIFIER NOT NULL,
        name NVARCHAR(255) NOT NULL,
        description NVARCHAR(255) NULL,
        provider NVARCHAR(255) NULL,
        server_url NVARCHAR(255) NULL,
        domain NVARCHAR(255) NULL,
        disabled BIT NOT NULL DEFAULT 0,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        CONSTRAINT fk_hr_services_service_type_id FOREIGN KEY (service_type_id) REFERENCES hr_service_types(id) ON DELETE CASCADE
    );

    CREATE INDEX idx_hr_services_service_type_id ON hr_services(service_type_id);
END
GO

-- 3. Tabella hr_employee_services
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='hr_employee_services' AND xtype='U')
BEGIN
    CREATE TABLE hr_employee_services (
        id UNIQUEIDENTIFIER NOT NULL PRIMARY KEY,
        employee_id UNIQUEIDENTIFIER NOT NULL,
        service_id UNIQUEIDENTIFIER NOT NULL,
        username NVARCHAR(255) NULL,
        email NVARCHAR(255) NULL,
        phone NVARCHAR(255) NULL,
        status NVARCHAR(10) NOT NULL DEFAULT 'Active',
        activated_at DATE NULL,
        expires_at DATE NULL,
        notes NVARCHAR(MAX) NULL,
        assigned_by BIGINT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        CONSTRAINT fk_hr_employee_services_employee_id FOREIGN KEY (employee_id) REFERENCES hr_employees(id) ON DELETE CASCADE,
        CONSTRAINT fk_hr_employee_services_service_id FOREIGN KEY (service_id) REFERENCES hr_services(id) ON DELETE CASCADE,
        CONSTRAINT fk_hr_employee_services_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
    );

    CREATE INDEX idx_hr_employee_services_employee_id ON hr_employee_services(employee_id);
    CREATE INDEX idx_hr_employee_services_service_id ON hr_employee_services(service_id);
    CREATE INDEX idx_hr_employee_services_status ON hr_employee_services(status);
END
GO

-- ============================================================
-- Rollback
-- ============================================================
-- DROP TABLE hr_employee_services;
-- DROP TABLE hr_services;
-- DROP TABLE hr_service_types;
-- GO
