<?php

/**
 * Bybit PHP SDK - Example Configuration
 * 
 * Copy this file to config.php and fill in your credentials.
 * NEVER commit config.php to version control!
 */

return [
    // API Credentials
    'api_key' => 'your_api_key_here',
    'api_secret' => 'your_api_secret_here',
    
    // Environment
    'testnet' => true, // Set to false for mainnet (production)
    
    // Region (global, nl, tr, kz, ge, ae)
    'region' => 'global',
    
    // Request settings
    'recv_window' => 5000, // milliseconds
    
    // Signature type (hmac or rsa)
    'signature' => 'hmac',
    
    // RSA private key (only if using RSA signature)
    'rsa_private_key' => null,
];
