<?php

namespace App\Policies;

use App\Models\{AuditLog, User};

class AuditLogPolicy
{
    /**
     * Determine if the user can view any audit logs
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'security_admin']);
    }

    /**
     * Determine if the user can view the audit log
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        // Users can view their own audit logs
        if ($user->id === $auditLog->user_id) {
            return true;
        }

        // Admins can view all audit logs
        return $user->hasRole(['admin', 'security_admin']);
    }

    /**
     * Determine if the user can create audit logs
     */
    public function create(User $user): bool
    {
        // Only system can create audit logs
        return false;
    }

    /**
     * Determine if the user can update audit logs
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        // Audit logs should never be updated
        return false;
    }

    /**
     * Determine if the user can delete audit logs
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        // Only super admins can delete audit logs (for cleanup)
        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can export audit data
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['admin', 'security_admin']);
    }
}