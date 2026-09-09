-- Phase 2 database initialization
-- PostgreSQL / Supabase
--
-- IMPORTANT:
-- Do not add domain tables until they have been derived from and checked
-- against the approved Phase 1 requirements and ERD.

create extension if not exists "pgcrypto";

-- ------------------------------------------------------------
-- Database connectivity check
-- ------------------------------------------------------------
-- Used only by GET /api/db-health.
-- It gives the backend a simple way to verify that Supabase/PostgreSQL
-- is reachable before domain tables exist.

create or replace function public.health_check()
returns jsonb
language sql
stable
as $$
  select jsonb_build_object(
    'status', 'ok',
    'checked_at', now()
  );
$$;

grant execute on function public.health_check() to anon;
grant execute on function public.health_check() to authenticated;

-- ------------------------------------------------------------
-- DOMAIN TABLES
-- ------------------------------------------------------------
-- Add approved entities below after completing:
--
-- docs/requirements.md
-- docs/database/ERD.md
--
-- Recommended order:
--
-- 1. independent/reference tables
-- 2. user/account-related tables if required
-- 3. core transactional tables
-- 4. junction tables for many-to-many relationships
-- 5. indexes
-- 6. RLS policies
-- 7. database functions/triggers only where justified
