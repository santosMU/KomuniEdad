<?php

// Identity is validated by CommunitySession through Supabase Auth.
return ['defaults' => ['guard' => 'supabase', 'passwords' => null], 'guards' => ['supabase' => ['driver' => 'supabase']], 'providers' => [], 'passwords' => []];
