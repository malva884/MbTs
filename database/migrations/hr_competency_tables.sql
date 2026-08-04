-- Query SQL per la creazione delle tabelle delle competenze
-- Database: Microsoft SQL Server

-- Tabella: hr_competency_activities
-- Memorizza le attività di competenza
IF OBJECT_ID('hr_competency_activity_role', 'U') IS NOT NULL
    DROP TABLE [hr_competency_activity_role];
IF OBJECT_ID('hr_competency_activities', 'U') IS NOT NULL
    DROP TABLE [hr_competency_activities];

CREATE TABLE [hr_competency_activities] (
    [id] UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    [attivita] NVARCHAR(255) NOT NULL,
    [disattivo] BIT NOT NULL DEFAULT 0,
    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    PRIMARY KEY ([id])
);
CREATE INDEX [hr_competency_activities_disattivo_index] ON [hr_competency_activities] ([disattivo]);

-- Tabella: hr_competency_activity_role
-- Tabella pivot tra attività e ruoli
CREATE TABLE [hr_competency_activity_role] (
    [activity_id] UNIQUEIDENTIFIER NOT NULL,
    [hr_role_id] UNIQUEIDENTIFIER NOT NULL,
    [valutazione_ideale] TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY ([activity_id], [hr_role_id]),
    CONSTRAINT [hr_comp_act_role_activity_id_foreign] 
        FOREIGN KEY ([activity_id]) 
        REFERENCES [hr_competency_activities] ([id]) 
        ON DELETE CASCADE,
    CONSTRAINT [hr_comp_act_role_hr_role_id_foreign] 
        FOREIGN KEY ([hr_role_id]) 
        REFERENCES [hr_roles] ([id]) 
        ON DELETE CASCADE
);

-- Tabella: hr_competency_evaluations
-- Memorizza le valutazioni delle competenze dei dipendenti
IF OBJECT_ID('hr_competency_evaluations', 'U') IS NOT NULL
    DROP TABLE [hr_competency_evaluations];

CREATE TABLE [hr_competency_evaluations] (
    [id] UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    [employee_id] UNIQUEIDENTIFIER NOT NULL,
    [activity_id] UNIQUEIDENTIFIER NOT NULL,
    [valutatore_id] BIGINT NULL,
    [valutazione] TINYINT NOT NULL DEFAULT 0,
    [data_valutazione] DATE NULL,
    [anno] SMALLINT NOT NULL,
    [note] NVARCHAR(MAX) NULL,
    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    PRIMARY KEY ([id]),
    CONSTRAINT [hr_comp_eval_employee_id_foreign] 
        FOREIGN KEY ([employee_id]) 
        REFERENCES [hr_employees] ([id]) 
        ON DELETE CASCADE,
    CONSTRAINT [hr_comp_eval_activity_id_foreign] 
        FOREIGN KEY ([activity_id]) 
        REFERENCES [hr_competency_activities] ([id]) 
        ON DELETE CASCADE,
    CONSTRAINT [hr_comp_eval_unique] UNIQUE ([employee_id], [activity_id], [anno])
);
CREATE INDEX [hr_competency_evaluations_valutatore_id_index] ON [hr_competency_evaluations] ([valutatore_id]);
CREATE INDEX [hr_competency_evaluations_anno_index] ON [hr_competency_evaluations] ([anno]);
