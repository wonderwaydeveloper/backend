<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizationController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('organization')) {
            return response()->json(['message' => 'Organization role required'], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'organization' => [
                'name' => $user->name,
                'verification_type' => $user->verification_type,
                'features' => [
                    'advertisements' => true,
                    'analytics' => true,
                    'team_management' => true,
                ],
            ],
        ]);
    }
}
