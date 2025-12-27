# لیست حذف کامل فیچرها

## 🗑️ فیچرهای مشخص شده برای حذف کامل

### **1. Stories System**
#### **فایلهای مربوطه برای حذف:**
- [ ] `app/Http/Controllers/Api/StoryController.php`
- [ ] `app/Models/Story.php`
- [ ] `app/Models/StoryView.php`
- [ ] `app/Http/Resources/StoryResource.php`
- [ ] `app/Http/Requests/StoryRequest.php`
- [ ] `database/migrations/*_create_stories_table.php`
- [ ] `database/migrations/*_create_story_views_table.php`

#### **Routes برای حذف:**
```php
// از routes/api.php
Route::get('/stories', [StoryController::class, 'index']);
Route::post('/stories', [StoryController::class, 'store']);
Route::delete('/stories/{story}', [StoryController::class, 'destroy']);
Route::post('/stories/{story}/view', [StoryController::class, 'view']);
```

#### **Database Tables برای حذف:**
```sql
DROP TABLE IF EXISTS story_views;
DROP TABLE IF EXISTS stories;
```

---

### **2. Group Chat System**
#### **فایلهای مربوطه برای حذف:**
- [ ] `app/Http/Controllers/Api/GroupChatController.php`
- [ ] `app/Models/GroupConversation.php`
- [ ] `app/Models/GroupMessage.php`
- [ ] `app/Models/GroupMember.php`
- [ ] `app/Http/Resources/GroupChatResource.php`
- [ ] `app/Http/Requests/GroupChatRequest.php`
- [ ] `database/migrations/*_create_group_conversations_table.php`
- [ ] `database/migrations/*_create_group_messages_table.php`
- [ ] `database/migrations/*_create_group_members_table.php`

#### **Routes برای حذف:**
```php
// از routes/api.php
Route::prefix('groups')->group(function () {
    Route::post('/', [GroupChatController::class, 'create']);
    Route::get('/my-groups', [GroupChatController::class, 'myGroups']);
    Route::post('/{group}/members', [GroupChatController::class, 'addMember']);
    Route::delete('/{group}/members/{userId}', [GroupChatController::class, 'removeMember']);
    Route::put('/{group}', [GroupChatController::class, 'update']);
    Route::post('/{group}/messages', [GroupChatController::class, 'sendMessage']);
    Route::get('/{group}/messages', [GroupChatController::class, 'messages']);
});
```

#### **Database Tables برای حذف:**
```sql
DROP TABLE IF EXISTS group_messages;
DROP TABLE IF EXISTS group_members;
DROP TABLE IF EXISTS group_conversations;
```

### **3. Video Streaming System**
#### **فایلهای مربوطه برای حذف:**
- [ ] `app/Http/Controllers/Api/StreamingController.php`
- [ ] `app/Models/Stream.php`
- [ ] `app/Models/StreamViewer.php`
- [ ] `app/Models/StreamChat.php`
- [ ] `app/Http/Resources/StreamResource.php`
- [ ] `app/Http/Requests/StreamRequest.php`
- [ ] `app/Services/StreamingService.php`
- [ ] `database/migrations/*_create_streams_table.php`
- [ ] `database/migrations/*_create_stream_viewers_table.php`
- [ ] `database/migrations/*_create_stream_chats_table.php`

#### **Routes برای حذف:**
```php
// از routes/api.php
Route::prefix('streaming')->group(function () {
    Route::post('/create', [StreamingController::class, 'create']);
    Route::post('/start', [StreamingController::class, 'start']);
    Route::post('/end', [StreamingController::class, 'end']);
    Route::get('/live', [StreamingController::class, 'live']);
    Route::get('/my-streams', [StreamingController::class, 'myStreams']);
    Route::get('/{stream}', [StreamingController::class, 'show']);
    Route::delete('/{stream}', [StreamingController::class, 'delete']);
    Route::post('/{streamKey}/join', [StreamingController::class, 'join']);
    Route::post('/{streamKey}/leave', [StreamingController::class, 'leave']);
    Route::get('/{streamKey}/stats', [StreamingController::class, 'stats']);
});

// Streaming Webhooks
Route::prefix('streaming')->group(function () {
    Route::post('/auth', [StreamingController::class, 'auth']);
    Route::post('/publish-done', [StreamingController::class, 'publishDone']);
    Route::post('/play', [StreamingController::class, 'play']);
    Route::post('/play-done', [StreamingController::class, 'playDone']);
});
```

#### **Database Tables برای حذف:**
```sql
DROP TABLE IF EXISTS stream_chats;
DROP TABLE IF EXISTS stream_viewers;
DROP TABLE IF EXISTS streams;
```

---

## 📋 مراحل حذف کامل

### **مرحله 1: Backup (ضروری)**
```bash
# Backup دیتابیس
mysqldump -u username -p database_name > backup_before_removal.sql

# Backup کد
git commit -am "Backup before removing Stories and Group Chat"
git tag -a "v1.0-before-removal" -m "Backup before feature removal"
```

### **مرحله 2: حذف Routes**
```php
// در routes/api.php
// حذف کامل بخشهای مربوط به Stories و Group Chat
```

### **مرحله 3: حذف Controllers**
```bash
rm app/Http/Controllers/Api/StoryController.php
rm app/Http/Controllers/Api/GroupChatController.php
```

### **مرحله 4: حذف Models**
```bash
rm app/Models/Story.php
rm app/Models/StoryView.php
rm app/Models/GroupConversation.php
rm app/Models/GroupMessage.php
rm app/Models/GroupMember.php
```

### **مرحله 5: حذف Resources و Requests**
```bash
rm app/Http/Resources/StoryResource.php
rm app/Http/Resources/GroupChatResource.php
rm app/Http/Requests/StoryRequest.php
rm app/Http/Requests/GroupChatRequest.php
```

### **مرحله 6: حذف Migrations**
```bash
# حذف فایلهای migration
rm database/migrations/*_create_stories_table.php
rm database/migrations/*_create_story_views_table.php
rm database/migrations/*_create_group_conversations_table.php
rm database/migrations/*_create_group_messages_table.php
rm database/migrations/*_create_group_members_table.php
```

### **مرحله 7: حذف جداول از دیتابیس**
```sql
-- اجرای دستورات SQL
DROP TABLE IF EXISTS story_views;
DROP TABLE IF EXISTS stories;
DROP TABLE IF EXISTS group_messages;
DROP TABLE IF EXISTS group_members;
DROP TABLE IF EXISTS group_conversations;
```

### **مرحله 8: پاکسازی References**
```php
// حذف تمام ارجاعات در سایر فایلها
// جستجو برای:
// - GroupConversation
// - GroupMessage
// - Story
// - StoryView
// در تمام فایلهای پروژه
```

### **مرحله 9: حذف Tests**
```bash
# حذف تستهای مربوطه
rm tests/Feature/StoryTest.php
rm tests/Feature/GroupChatTest.php
rm tests/Unit/StoryModelTest.php
rm tests/Unit/GroupChatModelTest.php
```

### **مرحله 10: بروزرسانی Documentation**
- [ ] حذف از API Documentation
- [ ] حذف از README.md
- [ ] حذف از مقایسه با Twitter
- [ ] بروزرسانی فهرست فیچرها

---

## ⚠️ نکات مهم حذف

### **قبل از حذف:**
1. **Backup کامل** از دیتابیس و کد
2. **اطلاع به تیم** توسعه
3. **بررسی Dependencies** در سایر بخشها
4. **تست در محیط Development**

### **بعد از حذف:**
1. **تست کامل** سیستم
2. **بررسی عدم خطا** در logs
3. **تست API endpoints** باقیمانده
4. **بروزرسانی مستندات**

### **فایلهای احتمالی دیگر:**
- [ ] `app/Events/StoryCreated.php`
- [ ] `app/Events/GroupMessageSent.php`
- [ ] `app/Jobs/ProcessStoryJob.php`
- [ ] `app/Jobs/ProcessGroupMessageJob.php`
- [ ] `app/Notifications/StoryMention.php`
- [ ] `app/Notifications/GroupMessageNotification.php`

---

## 📊 تأثیر حذف بر سیستم

### **مزایای حذف:**
- ✅ **کاهش پیچیدگی** کد
- ✅ **کاهش حجم** دیتابیس
- ✅ **بهبود عملکرد** کلی
- ✅ **کاهش نیاز** به منابع سرور
- ✅ **تمرکز بیشتر** بر فیچرهای اصلی

### **ریسکهای حذف:**
- ⚠️ **از دست رفتن** مزیت رقابتی
- ⚠️ **نارضایتی کاربران** موجود
- ⚠️ **کاهش تنوع** فیچرها
- ⚠️ **شباهت بیشتر** به Twitter ساده

### **جایگزینهای پیشنهادی:**
- 🔄 **تقویت Direct Messages** به جای Group Chat
- 🔄 **تقویت Posts** به جای Stories
- 🔄 **تمرکز بر** Video Streaming و Audio Spaces

---

## 🎯 تصمیم نهایی

### **آیا واقعاً حذف شوند؟**
- **Group Chat:** مزیت رقابتی بزرگ در برابر Twitter
- **Stories:** فیچر منحصر به فرد که Twitter ندارد

### **پیشنهاد جایگزین:**
به جای حذف کامل، **غیرفعال کردن موقت** تا تصمیم نهایی:
```php
// در config/features.php
'features' => [
    'stories' => false,
    'group_chat' => false,
]
```

---

**تاریخ تهیه:** دسامبر 2024  
**وضعیت:** آماده اجرا  
**هشدار:** حذف غیرقابل بازگشت است  
**توصیه:** ابتدا غیرفعال کنید، سپس تصمیم بگیرید