# Laravel Multi-Tenant REST API

Production-ready multi-tenant REST API built with **Laravel 11**, **JWT authentication** with refresh token rotation, **RBAC** (4 roles), and **API key management**. Every resource is scoped to a tenant — cross-tenant data leakage is structurally impossible.

## Architecture

```
app/
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── AuthController.php       # Register, login, refresh, logout, me
│   │   ├── UsersController.php      # Tenant user management
│   │   ├── ProjectsController.php   # Project CRUD with soft delete
│   │   └── ApiKeysController.php    # API key create, list, revoke, verify
│   ├── Middleware/
│   │   ├── EnsureUserBelongsToTenant.php
│   │   └── RequireRole.php
│   └── Requests/Api/               # FormRequest validation classes
├── Models/
│   ├── Tenant.php                   # SoftDeletes, trial period, settings JSON
│   ├── User.php                     # HasApiTokens, role helper
│   ├── RefreshToken.php             # Rotation with revocation tracking
│   ├── ApiKey.php                   # SHA-256 hashed, prefix-indexed
│   └── Project.php                  # SoftDeletes, metadata JSON
├── Services/
│   └── AuthService.php              # Token generation, login, refresh logic
└── Traits/
    └── ApiResponse.php              # Consistent JSON envelope helpers
database/migrations/                 # 5 migrations: tenants, users, tokens, api_keys, projects
routes/
└── api.php                          # Versioned routes with middleware groups
```

## Stack

- **Framework**: Laravel 11
- **Auth**: JWT (`tymon/jwt-auth`) with SHA-256 hashed refresh tokens stored in DB
- **Validation**: FormRequest classes for all write operations
- **Permissions**: Custom `RequireRole` middleware (owner / admin / member / viewer)
- **Database**: MySQL with proper indexes on all foreign keys and common filter columns

## API Endpoints

### Auth (rate limited: 10 req / 15 min)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/auth/register` | Register tenant + owner |
| POST | `/api/v1/auth/login` | Login with tenant slug |
| POST | `/api/v1/auth/refresh` | Rotate refresh token |
| POST | `/api/v1/auth/logout` | Revoke refresh token |
| GET  | `/api/v1/auth/me` | Current user + tenant |

### Users (OWNER, ADMIN only)
| Method | Path | Description |
|--------|------|-------------|
| GET    | `/api/v1/users` | List with pagination |
| GET    | `/api/v1/users/{id}` | Get user |
| POST   | `/api/v1/users` | Invite user |
| PATCH  | `/api/v1/users/{id}/role` | Update role |
| DELETE | `/api/v1/users/{id}` | Deactivate + revoke tokens |

### Projects
| Method | Path | Roles |
|--------|------|-------|
| GET    | `/api/v1/projects` | All |
| GET    | `/api/v1/projects/{id}` | All |
| POST   | `/api/v1/projects` | All |
| PATCH  | `/api/v1/projects/{id}` | OWNER, ADMIN, MEMBER |
| DELETE | `/api/v1/projects/{id}` | OWNER, ADMIN |

### API Keys (OWNER, ADMIN only)
| Method | Path | Description |
|--------|------|-------------|
| GET    | `/api/v1/api-keys` | List active keys |
| POST   | `/api/v1/api-keys` | Create key (raw returned once) |
| DELETE | `/api/v1/api-keys/{id}` | Revoke |
| POST   | `/api/v1/api-keys/verify` | Verify key (public) |

## Response Format

```json
// Success
{ "success": true, "data": { ... } }

// Paginated
{ "success": true, "data": [...], "meta": { "current_page": 1, "per_page": 20, "total": 45, "last_page": 3 } }

// Error
{ "success": false, "error": { "code": "VALIDATION_ERROR", "message": "...", "details": [...] } }
```

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

## Security

- Passwords hashed with bcrypt (Laravel default cost 12)
- Refresh tokens stored as SHA-256 hash, rotated on every use
- API keys stored as SHA-256 hash, prefix-indexed for fast lookup, raw value returned once only
- Auth endpoints rate-limited separately from global limit
- Tenant isolation enforced at query scope level on every model
- FormRequest validation on all write endpoints

## Roles

| Role | Permissions |
|------|-------------|
| `owner` | Full access, cannot be deactivated by others |
| `admin` | User management, all resource write access |
| `member` | Create/update own projects, read users |
| `viewer` | Read-only access |
