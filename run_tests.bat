@echo off
setlocal enabledelayedexpansion
set PASSED=0
set FAILED=0
set TOTAL=24

echo ========================================
echo Test 22 Systems
echo ========================================
echo.

echo [1/24] Authentication...
php test_authentication.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [2/24] Posts...
php test_posts_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [3/24] Profile Core...
php test_users_profile_01_core.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [4/24] Profile Security...
php test_users_profile_02_security.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [5/24] Profile Standards...
php test_users_profile_03_standards.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [6/24] Comments...
php test_comments.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [7/24] Follow...
php test_follow_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [8/24] Search...
php test_search_discovery_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [9/24] Messaging...
php test_messaging_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [10/24] Notifications...
php test_notifications_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [11/24] Bookmarks...
php test_bookmarks_reposts_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [12/24] Hashtags...
php test_hashtags_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [13/24] Moderation...
php test_moderation_reporting_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [14/24] Communities...
php test_communities_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [15/24] Spaces...
php test_spaces_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [16/24] Lists...
php test_lists_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [17/24] Polls...
php test_polls.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [18/24] Mentions...
php test_mentions.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [19/24] Moments...
php test_moments.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [20/24] Real-time...
php test_realtime_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [21/24] A/B Testing...
php test_abtest_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [22/24] Performance...
php test_performance_monitoring_system.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [23/24] Device Management...
php test_device_management.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo [24/24] Integration...
php test_integration_systems.php >nul 2>&1
if !errorlevel! equ 0 (echo OK & set /a PASSED+=1) else (echo FAIL & set /a FAILED+=1)

echo.
echo ========================================
set /a PERCENT=(!PASSED! * 100) / !TOTAL!
echo Result: !PASSED!/!TOTAL! passed (!PERCENT!%%)
if !FAILED! gtr 0 echo Failed: !FAILED!
echo ========================================
