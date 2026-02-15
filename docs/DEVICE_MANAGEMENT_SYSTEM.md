# Device Management System Documentation

## Overview
Complete device management system with registration, trust management, security monitoring, and multi-device support.

## API Endpoints

### 1. Register Device (Simple)
```http
POST /api/auth/register-device
Authorization: Bearer {token}
Content-Type: application/json

{
  "token": "fcm_token_here",
  "platform": "ios|android|web",
  "device_name": "iPhone 14 Pro"
}

Response: 200 OK
{
  "device": {
    "id": 1,
    "device_name": "iPhone 14 Pro",
    "device_type": "ios",
    "is_trusted": false,
    "last_used_at": "2024-01-15T10:30:00.000000Z",
    "created_at": "2024-01-15T10:30:00.000000Z"
  },
  "requires_verification": true,
  "message": "Device registered successfully"
}
```

### 2. Register Device (Advanced)
```http
POST /api/devices/register
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "MacBook Pro",
  "type": "desktop",
  "browser": "Chrome",
  "os": "macOS",
  "push_token": "push_token_here"
}

Response: 200 OK
{
  "device": {
    "id": 2,
    "device_name": "MacBook Pro",
    "device_type": "desktop",
    "browser": "Chrome",
    "os": "macOS",
    "is_trusted": false,
    "created_at": "2024-01-15T10:30:00.000000Z"
  },
  "requires_verification": true
}
```

### 3. List Devices
```http
GET /api/devices
Authorization: Bearer {token}

Response: 200 OK
[
  {
    "id": 1,
    "device_name": "iPhone 14 Pro",
    "device_type": "ios",
    "browser": "Safari",
    "os": "iOS",
    "ip_address": "192.168.1.1",
    "is_trusted": true,
    "is_current": true,
    "last_used_at": "2024-01-15T10:30:00.000000Z",
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
]
```

### 4. Trust Device
```http
POST /api/devices/{id}/trust
Authorization: Bearer {token}
Content-Type: application/json

{
  "password": "user_password"
}

Response: 200 OK
{
  "message": "Device trusted successfully"
}
```

### 5. Revoke Device
```http
DELETE /api/devices/{id}
Authorization: Bearer {token}

Response: 200 OK
{
  "message": "Device revoked successfully",
  "warning": "Other sessions terminated for security"
}
```

### 6. Revoke All Devices
```http
POST /api/devices/revoke-all
Authorization: Bearer {token}
Content-Type: application/json

{
  "password": "user_password"
}

Response: 200 OK
{
  "message": "All other devices revoked successfully"
}
```

### 7. Get Device Activity
```http
GET /api/devices/{id}/activity
Authorization: Bearer {token}

Response: 200 OK
{
  "device": {
    "id": 1,
    "device_name": "iPhone 14 Pro",
    "last_used_at": "2024-01-15T10:30:00.000000Z"
  },
  "activity": [],
  "last_login": "2024-01-15T10:30:00.000000Z",
  "total_logins": 0,
  "security_score": 100
}
```

### 8. Check Suspicious Activity
```http
GET /api/devices/suspicious-activity
Authorization: Bearer {token}

Response: 200 OK
{
  "has_suspicious_activity": false,
  "risk_level": "low",
  "recommendations": [],
  "alerts": []
}
```

### 9. Verify Device
```http
POST /api/auth/verify-device
Content-Type: application/json

{
  "code": "123456",
  "fingerprint": "device_fingerprint"
}

Response: 200 OK
{
  "user": {...},
  "token": "access_token",
  "message": "Device verified and login successful"
}
```

### 10. Resend Verification Code
```http
POST /api/auth/resend-device-code
Content-Type: application/json

{
  "fingerprint": "device_fingerprint",
  "user_id": 1
}

Response: 200 OK
{
  "message": "New verification code sent to your email",
  "code_expires_at": 1705318200,
  "resend_available_at": 1705318260,
  "expires_in": "15 minutes",
  "resend_cooldown": 60
}
```

### 11. Unregister Device (Legacy)
```http
DELETE /api/auth/unregister-device/{token}
Authorization: Bearer {token}

Response: 200 OK
{
  "message": "Device unregistered successfully"
}
```

## Permissions

### Device Management Permissions
1. **device.view** - View device list and details
2. **device.register** - Register new devices
3. **device.trust** - Trust devices
4. **device.revoke** - Revoke devices
5. **device.manage** - Full device management
6. **device.security** - Security monitoring and suspicious activity checks

### Role Assignments
- **user**: device.view, device.register, device.trust, device.revoke
- **verified**: device.view, device.register, device.trust, device.revoke
- **premium**: All device permissions
- **admin**: All device permissions

## Business Logic

### Device Registration
1. Generate device fingerprint from request
2. Create or update device record
3. Set is_trusted to false for new devices
4. Store device metadata (IP, user agent, browser, OS)
5. Return device info with verification requirement

### Device Trust
1. Validate user password
2. Mark device as trusted
3. Skip verification on future logins

### Device Revocation
1. Prevent revoking current device
2. Delete device record
3. Revoke associated tokens
4. Clear cached verification data
5. Use DB transactions for atomicity

### Security Monitoring
1. Calculate security score based on:
   - Trust status (20 points)
   - Last activity (10 points)
   - IP reputation (30 points)
2. Track suspicious activity patterns
3. Generate security recommendations

### Device Verification
1. Rate limit verification attempts (5 per hour)
2. Validate 6-digit code
3. Check code expiration (15 minutes)
4. Create trusted device on success
5. Generate access token with expiry

## Integration Examples

### Register and Trust Device
```php
// 1. Register device
$response = Http::withToken($token)->post('/api/devices/register', [
    'name' => 'MacBook Pro',
    'type' => 'desktop',
    'browser' => 'Chrome',
    'os' => 'macOS'
]);

$deviceId = $response->json('device.id');

// 2. Trust device
Http::withToken($token)->post("/api/devices/{$deviceId}/trust", [
    'password' => 'user_password'
]);
```

### Security Audit
```php
// Check suspicious activity
$response = Http::withToken($token)->get('/api/devices/suspicious-activity');

if ($response->json('has_suspicious_activity')) {
    $riskLevel = $response->json('risk_level');
    $recommendations = $response->json('recommendations');
    
    // Take action based on risk level
    if ($riskLevel === 'high') {
        // Force password reset or revoke all devices
    }
}
```

### Multi-Device Management
```php
// List all devices
$devices = Http::withToken($token)->get('/api/devices')->json();

// Revoke untrusted devices
foreach ($devices as $device) {
    if (!$device['is_trusted'] && !$device['is_current']) {
        Http::withToken($token)->delete("/api/devices/{$device['id']}");
    }
}
```

## Configuration

### Rate Limiting
- Device verification: 5 attempts per hour
- Code resend: 1 per minute
- Device registration: 10 per hour

### Timeouts
- Verification code: 15 minutes
- Device inactivity: 90 days
- Session timeout: Configurable via SessionTimeoutService

### Security
- Password required for trust and revoke-all operations
- Current device cannot be revoked
- Fingerprint-based device identification
- Automatic token revocation on device removal

## Database Schema

### device_tokens Table
```sql
- id: bigint (primary key)
- user_id: bigint (foreign key)
- token: string
- device_name: string
- device_type: enum(ios, android, web)
- browser: string (nullable)
- os: string (nullable)
- push_token: string (nullable)
- fingerprint: string (unique)
- ip_address: string
- user_agent: text
- is_trusted: boolean (default: false)
- last_used_at: timestamp
- created_at: timestamp
- updated_at: timestamp
```

## Error Handling

### Common Errors
- **401 Unauthorized**: Missing or invalid token
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Device not found
- **422 Validation Error**: Invalid input data
- **429 Too Many Requests**: Rate limit exceeded

### Error Response Format
```json
{
  "error": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## Testing

Run device management tests:
```bash
php test_device_management.php
```

Expected output: 456/456 points (114 tests)

**Test Categories:**
- ROADMAP Compliance (25 tests)
- Twitter Standards (25 tests)
- Operational Features (25 tests)
- Integration Tests (25 tests)
- Authentication Integration (2 tests)
- Permission Integration (4 tests)
- Route Integration (4 tests)
- Policy Integration (4 tests)

## Version
System 28 - Device Management (Enhanced)
Last Updated: 2026-02-15
Test Coverage: 100% (114 tests)
