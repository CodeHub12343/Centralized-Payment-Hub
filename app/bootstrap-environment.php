<?php
/**
 * Environment Bootstrap - Loads /etc/environment for Docker container
 * This file is auto-prepended to all PHP requests via php.ini
 */

// Load environment variables from /etc/environment if not already set by getenv()
if (!getenv('JWT_SECRET') && file_exists('/etc/environment')) {
    $envVars = [];
    $lines = @file('/etc/environment', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines !== false) {
        foreach ($lines as $line) {
            // Skip comments
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') === false) {
                continue;
            }
            
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '\'"');
            
            // Set via putenv if not already set
            if ($key && $value && !getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Debug: Log that environment was loaded
error_log('[Environment Bootstrap] Loaded environment variables at ' . date('Y-m-d H:i:s'));
if (getenv('JWT_SECRET')) {
    error_log('[Environment Bootstrap] JWT_SECRET is set (length: ' . strlen(getenv('JWT_SECRET')) . ')');
} else {
    error_log('[Environment Bootstrap] WARNING: JWT_SECRET not found!');
}
?>
