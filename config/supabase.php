<?php

return [
    'url' => getenv('SUPABASE_URL') ?: '',
    'anon_key' => getenv('SUPABASE_ANON_KEY') ?: '',
    'service_role_key' => getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '',
];