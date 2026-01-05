#!/bin/bash

# Replace all Persian text with English equivalents
find app/ -name "*.php" -type f -exec sed -i 's/نمودار کاربران/Users Chart/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/7 روز گذشته/Last 7 days/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/30 روز گذشته/Last 30 days/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/3 ماه گذشته/Last 3 months/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/6 ماه گذشته/Last 6 months/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/1 سال گذشته/Last 1 year/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/کاربران جدید/New Users/g' {} \;

# Replace error messages
find app/ -name "*.php" -type f -exec sed -i 's/فایل با موفقیت آپلود شد/File uploaded successfully/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/خطا در آپلود فایل/File upload error/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/فایل یافت نشد/File not found/g' {} \;
find app/ -name "*.php" -type f -exec sed -i 's/خطا در حذف فایل/File deletion error/g' {} \;

echo "Persian text replacement completed"