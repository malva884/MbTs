-- Query SQL per la creazione delle tabelle della gestione degli accessi
-- Database: Microsoft SQL Server

-- Droppare le tabelle se esistono (per ricreazione con tipi corretti)
IF OBJECT_ID('hr_employee_accesses', 'U') IS NOT NULL
    DROP TABLE [hr_employee_accesses];
IF OBJECT_ID('hr_access_resources', 'U') IS NOT NULL
    DROP TABLE [hr_access_resources];
IF OBJECT_ID('hr_access_types', 'U') IS NOT NULL
    DROP TABLE [hr_access_types];

-- Tabella: hr_access_types
-- Memorizza i tipi di accesso (es. Google Drive, Server, Software, ecc.)
CREATE TABLE [hr_access_types] (
    [id] UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    [name] NVARCHAR(255) NOT NULL,
    [description] NVARCHAR(255) NULL,
    [icon] NVARCHAR(255) NULL,
    [disabled] BIT NOT NULL DEFAULT 0,
    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    PRIMARY KEY ([id]),
    CONSTRAINT [hr_access_types_name_unique] UNIQUE ([name])
);

-- Tabella: hr_access_resources
-- Memorizza le risorse di accesso (es. cartelle Drive, server, applicazioni)
CREATE TABLE [hr_access_resources] (
    [id] UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    [access_type_id] UNIQUEIDENTIFIER NOT NULL,
    [name] NVARCHAR(255) NOT NULL,
    [path] NVARCHAR(255) NULL,
    [drive_file_id] NVARCHAR(255) NULL,
    [server_ip] NVARCHAR(255) NULL,
    [import_method] NVARCHAR(255) NULL DEFAULT 'manual',
    [description] NVARCHAR(255) NULL,
    [disabled] BIT NOT NULL DEFAULT 0,
    [default_permission] NVARCHAR(255) NOT NULL DEFAULT 'read',
    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    PRIMARY KEY ([id]),
    CONSTRAINT [hr_access_resources_access_type_id_foreign] 
        FOREIGN KEY ([access_type_id]) 
        REFERENCES [hr_access_types] ([id]) 
        ON DELETE CASCADE
);
CREATE INDEX [hr_access_resources_access_type_id_index] ON [hr_access_resources] ([access_type_id]);

-- Tabella: hr_employee_accesses
-- Memorizza gli accessi dei dipendenti alle risorse
CREATE TABLE [hr_employee_accesses] (
    [id] UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    [employee_id] UNIQUEIDENTIFIER NOT NULL,
    [access_resource_id] UNIQUEIDENTIFIER NOT NULL,
    [permission] NVARCHAR(50) NOT NULL DEFAULT 'read' CHECK ([permission] IN ('read', 'write', 'admin')),
    [notes] NVARCHAR(MAX) NULL,
    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    PRIMARY KEY ([id]),
    CONSTRAINT [hr_employee_accesses_employee_id_access_resource_id_unique] UNIQUE ([employee_id], [access_resource_id]),
    CONSTRAINT [hr_employee_accesses_employee_id_foreign] 
        FOREIGN KEY ([employee_id]) 
        REFERENCES [hr_employees] ([id]) 
        ON DELETE CASCADE,
    CONSTRAINT [hr_employee_accesses_access_resource_id_foreign] 
        FOREIGN KEY ([access_resource_id]) 
        REFERENCES [hr_access_resources] ([id]) 
        ON DELETE CASCADE
);
CREATE INDEX [hr_employee_accesses_employee_id_index] ON [hr_employee_accesses] ([employee_id]);
CREATE INDEX [hr_employee_accesses_access_resource_id_index] ON [hr_employee_accesses] ([access_resource_id]);
