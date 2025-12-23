# ⚠️ تصحیح: فایلهای امن برای حذف

## ❌ **خطر! فایلهای زیر را حذف نکنید:**

### 🎮 **Controllers** (همه در routes استفاده میشوند):
- ❌ `PerformanceController.php` - در routes موجود
- ❌ `PerformanceDashboardController.php` - در routes موجود  
- ❌ `PerformanceOptimizationController.php` - در routes موجود

### ⚙️ **Services** (همه استفاده میشوند):
- ❌ `CacheManagementService.php` - 1 بار استفاده
- ❌ `CacheOptimizationService.php` - 2 بار استفاده
- ❌ `DatabaseOptimizationService.php` - 2 بار استفاده

### 📁 **Directories** (همه در کد ارجاع دارند):
- ❌ `storage/app/secrets` - در کد استفاده میشود
- ❌ `storage/recordings` - در کد استفاده میشود
- ❌ `storage/streams` - در کد استفاده میشود
- ❌ `-p/` - در کد ارجاع دارد

### ⚙️ **Production Files** (برای deployment نگه دارید):
- ⚠️ `.env.production` - برای تولید
- ⚠️ `php-production.ini` - کانفیگ تولید
- ⚠️ `deploy-production.sh` - اسکریپت استقرار

## ✅ **فقط این فایلها امن هستند:**

### 🗑️ **Cache Files** (قابل بازسازی):
```bash
.php-cs-fixer.cache           # PHP CS Fixer cache
.phpunit.result.cache         # PHPUnit cache
storage/framework/cache/data/* # Laravel cache
storage/framework/sessions/*   # Session files
storage/framework/views/*      # Compiled views (72 files)
bootstrap/cache/*.php          # Bootstrap cache (4 files)
```

### 📄 **Temporary Analysis Files** (گزارشهای موقت):
```bash
ARCHITECTURE_ANALYSIS.md      # گزارش معماری
BACKEND_QUALITY_REPORT.md     # گزارش کیفیت
FINAL_ASSESSMENT.md           # ارزیابی نهایی
PRODUCTION_READINESS.md       # آمادگی تولید
performance-checklist.md      # چکلیست عملکرد
CLEANUP_RECOMMENDATIONS.md    # این فایل!
```

### 🧪 **Test Files** (محلی):
```bash
performance_test.bat          # تست ویندوز
load_test.sh                 # تست لینوکس
```

## 🚀 **دستورات امن پاکسازی:**

### 1. **Cache Cleanup** (کاملاً امن):
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear
```

### 2. **Manual File Cleanup** (فقط این فایلها):
```bash
# Remove cache files
del .php-cs-fixer.cache
del .phpunit.result.cache

# Remove analysis reports
del ARCHITECTURE_ANALYSIS.md
del BACKEND_QUALITY_REPORT.md  
del FINAL_ASSESSMENT.md
del PRODUCTION_READINESS.md
del performance-checklist.md
del CLEANUP_RECOMMENDATIONS.md

# Remove test scripts
del performance_test.bat
del load_test.sh
```

## 📊 **خلاصه امن:**

### ✅ **قابل حذف امن**:
- Cache files: ~76 files
- Analysis reports: 6 files
- Test scripts: 2 files
- **مجموع**: ~84 files

### ❌ **نگه دارید**:
- همه Controllers
- همه Services  
- همه Directories
- Production configs

### 💾 **صرفهجویی امن**: ~20MB
### 🛡️ **خطر**: صفر

**نتیجه**: فقط cache و فایلهای تحلیل موقت را حذف کنید!**