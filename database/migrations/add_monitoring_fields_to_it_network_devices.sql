-- Add monitoring fields to existing it_network_devices table
-- Run this if the table already exists without the monitoring columns

-- Check if monitor_enabled column exists, if not add it
IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID('it_network_devices') 
    AND name = 'monitor_enabled'
)
BEGIN
    ALTER TABLE it_network_devices ADD monitor_enabled BIT DEFAULT 0;
END

-- Check if status column exists, if not add it
IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID('it_network_devices') 
    AND name = 'status'
)
BEGIN
    ALTER TABLE it_network_devices ADD status NVARCHAR(20) DEFAULT 'unknown';
END

-- Check if last_check_at column exists, if not add it
IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID('it_network_devices') 
    AND name = 'last_check_at'
)
BEGIN
    ALTER TABLE it_network_devices ADD last_check_at DATETIME NULL;
END

-- Check if last_online_at column exists, if not add it
IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID('it_network_devices') 
    AND name = 'last_online_at'
)
BEGIN
    ALTER TABLE it_network_devices ADD last_online_at DATETIME NULL;
END

-- Check if response_time_ms column exists, if not add it
IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID('it_network_devices') 
    AND name = 'response_time_ms'
)
BEGIN
    ALTER TABLE it_network_devices ADD response_time_ms INT NULL;
END

-- Check if uptime_percentage column exists, if not add it
IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID('it_network_devices') 
    AND name = 'uptime_percentage'
)
BEGIN
    ALTER TABLE it_network_devices ADD uptime_percentage DECIMAL(5,2) DEFAULT 0;
END

-- Add index for monitor_enabled if it doesn't exist
IF NOT EXISTS (
    SELECT * FROM sys.indexes 
    WHERE object_id = OBJECT_ID('it_network_devices') 
    AND name = 'idx_it_network_devices_monitor_enabled'
)
BEGIN
    CREATE INDEX idx_it_network_devices_monitor_enabled ON it_network_devices(monitor_enabled);
END
