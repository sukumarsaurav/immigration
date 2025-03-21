-- Add debug mode setting
INSERT INTO system_settings (setting_key, setting_value, setting_type, setting_description, is_public, setting_group) 
VALUES ('debug_mode', 'false', 'boolean', 'Enable debug mode for detailed error reporting (should be OFF in production)', 0, 'system'); 