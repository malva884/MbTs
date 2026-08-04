-- IT Asset Management System - Database Schema for SQL Server
-- Generated from Laravel migrations

-- ============================================
-- Drop existing tables (in correct order to handle foreign keys)
-- ============================================
IF OBJECT_ID('it_asset_supplier', 'U') IS NOT NULL
    DROP TABLE it_asset_supplier;
IF OBJECT_ID('it_network_device_logs', 'U') IS NOT NULL
    DROP TABLE it_network_device_logs;
IF OBJECT_ID('it_network_devices', 'U') IS NOT NULL
    DROP TABLE it_network_devices;
IF OBJECT_ID('it_asset_assignments', 'U') IS NOT NULL
    DROP TABLE it_asset_assignments;
IF OBJECT_ID('it_transactions', 'U') IS NOT NULL
    DROP TABLE it_transactions;
IF OBJECT_ID('it_machines', 'U') IS NOT NULL
    DROP TABLE it_machines;
IF OBJECT_ID('it_assets', 'U') IS NOT NULL
    DROP TABLE it_assets;
IF OBJECT_ID('it_asset_groups', 'U') IS NOT NULL
    DROP TABLE it_asset_groups;
IF OBJECT_ID('it_suppliers', 'U') IS NOT NULL
    DROP TABLE it_suppliers;
IF OBJECT_ID('it_locations', 'U') IS NOT NULL
    DROP TABLE it_locations;
IF OBJECT_ID('it_categories', 'U') IS NOT NULL
    DROP TABLE it_categories;

-- ============================================
-- Table: it_categories
-- ============================================
CREATE TABLE it_categories (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    description NVARCHAR(MAX) NULL,
    parent_id UNIQUEIDENTIFIER NULL,
    disabled BIT DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    require_label BIT DEFAULT 0
);

CREATE INDEX idx_it_categories_parent_id ON it_categories(parent_id);
CREATE INDEX idx_it_categories_disabled ON it_categories(disabled);

-- ============================================
-- Table: it_locations
-- ============================================
CREATE TABLE it_locations (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    type NVARCHAR(50) DEFAULT 'Office',
    description NVARCHAR(MAX) NULL,
    parent_id UNIQUEIDENTIFIER NULL,
    disabled BIT DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX idx_it_locations_parent_id ON it_locations(parent_id);
CREATE INDEX idx_it_locations_disabled ON it_locations(disabled);

-- ============================================
-- Table: it_assets
-- ============================================
CREATE TABLE it_assets (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    category_id UNIQUEIDENTIFIER NOT NULL,
    location_id UNIQUEIDENTIFIER NULL,
    serial_number NVARCHAR(255) NULL,
    brand NVARCHAR(255) NULL,
    model NVARCHAR(255) NULL,
    purchase_date DATE NULL,
    product_link NVARCHAR(255) NULL,
    unit_cost DECIMAL(10, 2) NULL,
    warranty_expiry DATE NULL,
    quantity INT DEFAULT 1,
    status NVARCHAR(50) DEFAULT 'Available',
    notes NVARCHAR(MAX) NULL,
    disabled BIT DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    asset_tag NVARCHAR(255) NULL
);

CREATE INDEX idx_it_assets_category_id ON it_assets(category_id);
CREATE INDEX idx_it_assets_location_id ON it_assets(location_id);
CREATE INDEX idx_it_assets_status ON it_assets(status);
CREATE INDEX idx_it_assets_disabled ON it_assets(disabled);
CREATE INDEX idx_it_assets_serial_number ON it_assets(serial_number);
CREATE INDEX idx_it_assets_asset_tag ON it_assets(asset_tag);

ALTER TABLE it_assets ADD CONSTRAINT chk_it_assets_status 
CHECK (status IN ('Available', 'Assigned', 'In Repair', 'Retired', 'Lost'));

ALTER TABLE it_assets ADD CONSTRAINT fk_it_assets_category 
FOREIGN KEY (category_id) REFERENCES it_categories(id);

ALTER TABLE it_assets ADD CONSTRAINT fk_it_assets_location 
FOREIGN KEY (location_id) REFERENCES it_locations(id);

-- ============================================
-- Table: it_asset_assignments
-- ============================================
CREATE TABLE it_asset_assignments (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    asset_id UNIQUEIDENTIFIER NOT NULL,
    employee_id UNIQUEIDENTIFIER NULL,
    assignable_type NVARCHAR(255) NULL,
    assignable_id UNIQUEIDENTIFIER NULL,
    assigned_by BIGINT NOT NULL,
    assigned_quantity INT DEFAULT 1,
    assigned_at DATETIME DEFAULT GETDATE(),
    returned_at DATETIME NULL,
    returned_quantity INT NULL,
    status NVARCHAR(50) DEFAULT 'Active',
    notes NVARCHAR(MAX) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX idx_it_asset_assignments_asset_id ON it_asset_assignments(asset_id);
CREATE INDEX idx_it_asset_assignments_employee_id ON it_asset_assignments(employee_id);
CREATE INDEX idx_it_asset_assignments_assigned_by ON it_asset_assignments(assigned_by);
CREATE INDEX idx_it_asset_assignments_status ON it_asset_assignments(status);
CREATE INDEX idx_it_asset_assignments_assignable ON it_asset_assignments(assignable_type, assignable_id);

ALTER TABLE it_asset_assignments ADD CONSTRAINT chk_it_asset_assignments_status 
CHECK (status IN ('Active', 'Returned'));

ALTER TABLE it_asset_assignments ADD CONSTRAINT fk_it_asset_assignments_asset 
FOREIGN KEY (asset_id) REFERENCES it_assets(id);

-- Note: Foreign key to hr_employees - enabling to check data type mismatch
ALTER TABLE it_asset_assignments ADD CONSTRAINT fk_it_asset_assignments_employee 
FOREIGN KEY (employee_id) REFERENCES hr_employees(id);

-- Note: Foreign key to users - enabling to check data type mismatch
ALTER TABLE it_asset_assignments ADD CONSTRAINT fk_it_asset_assignments_assigned_by 
FOREIGN KEY (assigned_by) REFERENCES users(id);

-- ============================================
-- Table: it_network_devices
-- ============================================
CREATE TABLE it_network_devices (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    asset_id UNIQUEIDENTIFIER NOT NULL,
    ip_address NVARCHAR(45) NULL,
    mac_address NVARCHAR(17) NULL,
    device_type NVARCHAR(50) DEFAULT 'Other',
    location NVARCHAR(255) NULL,
    rack_position NVARCHAR(50) NULL,
    vlan NVARCHAR(50) NULL,
    subnet NVARCHAR(50) NULL,
    notes NVARCHAR(MAX) NULL,
    disabled BIT DEFAULT 0,
    monitor_enabled BIT DEFAULT 0,
    status NVARCHAR(20) DEFAULT 'unknown',
    last_check_at DATETIME NULL,
    last_online_at DATETIME NULL,
    response_time_ms INT NULL,
    uptime_percentage DECIMAL(5,2) DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX idx_it_network_devices_asset_id ON it_network_devices(asset_id);
CREATE INDEX idx_it_network_devices_disabled ON it_network_devices(disabled);
CREATE INDEX idx_it_network_devices_monitor_enabled ON it_network_devices(monitor_enabled);

ALTER TABLE it_network_devices ADD CONSTRAINT chk_it_network_devices_device_type
CHECK (device_type IN ('Router', 'Switch', 'Access Point', 'Server', 'Firewall', 'Other'));

ALTER TABLE it_network_devices ADD CONSTRAINT chk_it_network_devices_status
CHECK (status IN ('online', 'offline', 'unknown'));

ALTER TABLE it_network_devices ADD CONSTRAINT fk_it_network_devices_asset
FOREIGN KEY (asset_id) REFERENCES it_assets(id) ON DELETE CASCADE;

-- ============================================
-- Table: it_network_device_logs
-- ============================================
CREATE TABLE it_network_device_logs (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    network_device_id UNIQUEIDENTIFIER NOT NULL,
    status NVARCHAR(20) NOT NULL,
    response_time_ms INT NULL,
    checked_at DATETIME NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX idx_it_network_device_logs_network_device_id ON it_network_device_logs(network_device_id);
CREATE INDEX idx_it_network_device_logs_checked_at ON it_network_device_logs(checked_at);

ALTER TABLE it_network_device_logs ADD CONSTRAINT chk_it_network_device_logs_status
CHECK (status IN ('online', 'offline', 'unknown'));

ALTER TABLE it_network_device_logs ADD CONSTRAINT fk_it_network_device_logs_network_device
FOREIGN KEY (network_device_id) REFERENCES it_network_devices(id) ON DELETE CASCADE;

-- ============================================
-- Table: it_transactions
-- ============================================
CREATE TABLE it_transactions (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    asset_id UNIQUEIDENTIFIER NOT NULL,
    type NVARCHAR(50) DEFAULT 'In',
    from_location_id UNIQUEIDENTIFIER NULL,
    to_location_id UNIQUEIDENTIFIER NULL,
    performed_by BIGINT NOT NULL,
    date DATETIME DEFAULT GETDATE(),
    notes NVARCHAR(MAX) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX idx_it_transactions_asset_id ON it_transactions(asset_id);
CREATE INDEX idx_it_transactions_type ON it_transactions(type);
CREATE INDEX idx_it_transactions_from_location_id ON it_transactions(from_location_id);
CREATE INDEX idx_it_transactions_to_location_id ON it_transactions(to_location_id);
CREATE INDEX idx_it_transactions_performed_by ON it_transactions(performed_by);

ALTER TABLE it_transactions ADD CONSTRAINT chk_it_transactions_type 
CHECK (type IN ('In', 'Out', 'Transfer', 'Maintenance', 'Return', 'Retire'));

ALTER TABLE it_transactions ADD CONSTRAINT fk_it_transactions_asset 
FOREIGN KEY (asset_id) REFERENCES it_assets(id);

ALTER TABLE it_transactions ADD CONSTRAINT fk_it_transactions_from_location 
FOREIGN KEY (from_location_id) REFERENCES it_locations(id);

ALTER TABLE it_transactions ADD CONSTRAINT fk_it_transactions_to_location 
FOREIGN KEY (to_location_id) REFERENCES it_locations(id);

-- Note: Foreign key to users - enabling to check data type mismatch
ALTER TABLE it_transactions ADD CONSTRAINT fk_it_transactions_performed_by 
FOREIGN KEY (performed_by) REFERENCES users(id);

-- ============================================
-- Table: it_suppliers
-- ============================================
CREATE TABLE it_suppliers (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    contact_person NVARCHAR(255) NULL,
    email NVARCHAR(255) NULL,
    phone NVARCHAR(50) NULL,
    address NVARCHAR(MAX) NULL,
    city NVARCHAR(255) NULL,
    country NVARCHAR(255) NULL,
    vat_number NVARCHAR(50) NULL,
    notes NVARCHAR(MAX) NULL,
    disabled BIT DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX idx_it_suppliers_disabled ON it_suppliers(disabled);

-- ============================================
-- Table: it_asset_supplier (Pivot Table)
-- ============================================
CREATE TABLE it_asset_supplier (
    asset_id UNIQUEIDENTIFIER NOT NULL,
    supplier_id UNIQUEIDENTIFIER NOT NULL,
    unit_cost DECIMAL(10, 2) NULL,
    purchase_date DATE NULL,
    order_reference NVARCHAR(255) NULL,
    product_link NVARCHAR(255) NULL,
    notes NVARCHAR(MAX) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (asset_id, supplier_id)
);

ALTER TABLE it_asset_supplier ADD CONSTRAINT fk_it_asset_supplier_asset 
FOREIGN KEY (asset_id) REFERENCES it_assets(id) ON DELETE CASCADE;

ALTER TABLE it_asset_supplier ADD CONSTRAINT fk_it_asset_supplier_supplier 
FOREIGN KEY (supplier_id) REFERENCES it_suppliers(id) ON DELETE CASCADE;

-- ============================================
-- Table: it_asset_groups
-- ============================================
CREATE TABLE it_asset_groups (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    brand NVARCHAR(255) NULL,
    model NVARCHAR(255) NULL,
    min_stock INT DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE UNIQUE INDEX uk_it_asset_groups_brand_model ON it_asset_groups(brand, model);

-- ============================================
-- Table: it_machines
-- ============================================
CREATE TABLE it_machines (
    id UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID() PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    code NVARCHAR(255) NOT NULL,
    description NVARCHAR(MAX) NULL,
    location_id UNIQUEIDENTIFIER NULL,
    status NVARCHAR(50) DEFAULT 'Active',
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE UNIQUE INDEX uk_it_machines_code ON it_machines(code);
CREATE INDEX idx_it_machines_location_id ON it_machines(location_id);

ALTER TABLE it_machines ADD CONSTRAINT chk_it_machines_status 
CHECK (status IN ('Active', 'Inactive', 'Maintenance'));

ALTER TABLE it_machines ADD CONSTRAINT fk_it_machines_location 
FOREIGN KEY (location_id) REFERENCES it_locations(id);
