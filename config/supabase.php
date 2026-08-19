<?php

return [
    'url' => $_ENV['SUPABASE_URL'] ?? getenv('SUPABASE_URL') ?: '',
    'anon_key' => $_ENV['SUPABASE_ANON_KEY'] ?? getenv('SUPABASE_ANON_KEY') ?: '',
    'service_role_key' => $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '',
];