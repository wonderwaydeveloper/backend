# Architecture Decision: Service Responsibilities

## Final Service Architecture

### SecurityMonitoringService
**Responsibilities:**
- Rate Limiting & Account Lockout
- Threat Detection (SQL Injection, XSS, Bot Detection)
- IP Blocking
- Real-time Security Monitoring

**Does NOT handle:**
- Audit Logging (delegates to AuditTrailService)
- User Activity Tracking (delegates to AuditTrailService)
- Security Event Storage (delegates to AuditTrailService)

### AuditTrailService  
**Responsibilities:**
- All Audit Logging (Auth, Security, User, Data)
- User Activity Tracking
- Anomaly Detection
- Security Event Storage
- Audit Trail Generation

**Does NOT handle:**
- Rate Limiting (handled by SecurityMonitoringService)
- IP Blocking (handled by SecurityMonitoringService)
- Threat Scoring (handled by SecurityMonitoringService)

### Integration Pattern
```
Controller -> SecurityMonitoringService (for rate limiting)
           -> AuditTrailService (for logging)

SecurityMonitoringService -> AuditTrailService (for logging detected threats)
```

## Current Status
✅ SecurityMonitoringService delegates logging to AuditTrailService
✅ AuditTrailService handles all audit operations
❌ Controller still uses both services (acceptable for different purposes)

## Conclusion
The current architecture is CORRECT and NOT duplicated:
- SecurityMonitoringService: Rate limiting + threat detection
- AuditTrailService: Logging + audit trails
- Controller: Uses both for their specific purposes

This is proper separation of concerns, NOT duplication.