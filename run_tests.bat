@echo off
setlocal enabledelayedexpansion
set PASSED=0
set FAILED=0
set TOTAL=28

echo ========================================
echo Test 28 Systems
echo ========================================
echo.

echo [1/25] Authorization...
php test_authorization_final.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [2/25] Authentication...
php test_authentication.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [3/25] Posts...
php test_posts_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [4/25] Profile Core...
php test_users_profile_01_core.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [5/25] Profile Security...
php test_users_profile_02_security.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [6/25] Profile Standards...
php test_users_profile_03_standards.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [7/25] Comments...
php test_comments.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [8/25] Follow...
php test_follow_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [9/25] Search...
php test_search_discovery_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [10/25] Messaging...
php test_messaging_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [11/25] Notifications...
php test_notifications_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [12/25] Bookmarks...
php test_bookmarks_reposts_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [13/25] Hashtags...
php test_hashtags_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [14/25] Moderation...
php test_moderation_reporting_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [15/25] Communities...
php test_communities_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [16/25] Spaces...
php test_spaces_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [17/25] Lists...
php test_lists_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [18/25] Polls...
php test_polls.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [19/25] Mentions...
php test_mentions.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [20/25] Moments...
php test_moments.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [21/25] Real-time...
php test_realtime_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [22/25] A/B Testing...
php test_abtest_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [23/25] Performance...
php test_performance_monitoring_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [24/25] Device Management...
php test_device_management.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [25/28] Integration...
php test_integration_systems.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [26/28] Analytics...
php test_analytics_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [27/28] Media...
php test_media.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [28/28] Monetization...
php test_monetization_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo.
echo ========================================
set /a PERCENT=(!PASSED! * 100) / !TOTAL!
echo Result: !PASSED!/!TOTAL! passed (!PERCENT!%%)
if !FAILED! gtr 0 echo Failed: !FAILED!
echo ========================================
