import { createClient } from "@supabase/supabase-js";
import { getServerEnv } from "@/lib/env";

/**
 * Backend-only Supabase client for Phase 2 initialization.
 *
 * This uses the anon key because authentication and authorization have not
 * been initialized yet. Add authenticated server clients and RLS policies
 * when the approved Phase 1 requirements require them.
 */
export function createSupabaseServerClient() {
  const env = getServerEnv();

  return createClient(
    env.NEXT_PUBLIC_SUPABASE_URL,
    env.NEXT_PUBLIC_SUPABASE_ANON_KEY,
    {
      auth: {
        persistSession: false,
        autoRefreshToken: false,
      },
    }
  );
}
