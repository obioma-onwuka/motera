# MOTERA Phase 1: Foundation Implementation Plan

This plan details the technical steps to execute **Phase 1 (Foundation)** of the MOTERA open-source banking platform, based on the `bkapproach.md` blueprint.

## 1. Project Setup & Configuration
- Ensure PostgreSQL is configured in `.env` (the blueprint specifies PostgreSQL over SQLite/MySQL).
- Install required foundational dependencies:
  - `spatie/laravel-permission` (Roles & Permissions)
  - `livewire/livewire` (Reactive UI forms/tables)
  - `spatie/laravel-activitylog` (Audit trails)

## 2. Base Architecture (Scaffolding the "Strict" Patterns)
As requested, MOTERA uses a strict architectural pattern outside the standard MVC approach.
- **Base DTOs:** Create an abstract `DataTransferObject` pattern.
- **Base Actions:** Create an interface/abstract class for Domain Actions.
- **Enums Directory:** Set up stub Enums for statuses (`AccountStatus`, `TransactionStatus`, etc.).
- **UUID Strategy:** 
  - Overwrite the default Laravel `User` migration to use `uuid` as the primary key.
  - Create a reusable `HasUuid` trait (or use Laravel 11/12's `HasUuids` correctly) for all entities.

## 3. Database Foundations
- Modify the `0001_01_01_000000_create_users_table.php` migration to use `uuid('id')->primary()` instead of auto-incrementing IDs.
- Run migrations for Spatie Permissions and Activity Logs, customizing them to support UUID morphs if necessary.
- **Roles to Seed:** `Customer`, `Support Admin`, `Operations Admin`, `Compliance Admin`, `Super Admin`.

## 4. Authentication & Core UI Shells
- Install Laravel Breeze (or Fortify) customized heavily to eliminate unnecessary logic and integrate our new UI principles.
- Apply the **"Financial Blue" (#1D4ED8)** UI theme in Tailwind config.
- Scaffold the **Mobile-First** User Dashboard layout (base Blade components).
- Scaffold the Admin Dashboard layout.

## User Review Required

> [!IMPORTANT]
> - Do you already have a PostgreSQL database running and credentials available for `.env`, or should we default to SQLite for local MVP dev until production?
> - For authentication, shall we use standard Laravel Breeze (Blade/Alpine version) as the starting point and refactor its controllers to use the Action/DTO approach, or build custom Auth controllers from scratch?

## Verification Plan
1. **Automated Tests:** Verify UUID creation on User models and successful Role assignment.
2. **Manual Verification:** Build the app, load the initial login/register flows in the browser, and ensure the "Financial Blue" styling and mobile responsiveness are intact.
