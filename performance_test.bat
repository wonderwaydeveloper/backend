@echo off
echo 🚀 WonderWay Performance Test
echo.

echo 📊 Testing Health Endpoint...
for /L %%i in (1,1,10) do (
    curl -s -w "Response time: %%{time_total}s\n" http://localhost:8000/api/health > nul
)

echo.
echo 📊 Testing Posts Endpoint...
for /L %%i in (1,1,5) do (
    curl -s -w "Response time: %%{time_total}s\n" http://localhost:8000/api/posts > nul
)

echo.
echo ✅ Performance test completed!