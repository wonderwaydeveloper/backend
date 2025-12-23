# 📚 WonderWay API Documentation

این پوشه شامل مستندات کامل API پلتفرم WonderWay است.

## 📁 فایلها

### **api-spec.yaml**
- مشخصات اصلی API با OpenAPI 3.0.3
- شامل endpointهای اصلی (Authentication, Posts, Streaming)
- مثالهای فارسی و واقعی
- Schemas کامل برای Request/Response

### **api-documentation.yaml**
- مستندات جامع و تفصیلی
- شامل ویژگیهای پیشرفته (Monetization, Analytics)
- مثالهای کاربردی
- Error handling مفصل

## 🔧 استفاده

### **Swagger UI**
```bash
# مشاهده مستندات در مرورگر
http://localhost:8000/api/documentation
```

### **Code Generation**
```bash
# تولید کد کلاینت JavaScript
npx @openapitools/openapi-generator-cli generate \
  -i docs/api-spec.yaml \
  -g javascript \
  -o client/js

# تولید کد کلاینت PHP
npx @openapitools/openapi-generator-cli generate \
  -i docs/api-spec.yaml \
  -g php \
  -o client/php
```

### **Validation**
```bash
# بررسی صحت فایلهای OpenAPI
npx swagger-parser validate docs/api-spec.yaml
npx swagger-parser validate docs/api-documentation.yaml
```

## 🌍 Base URLs

- **Production**: `https://api.wonderway.com`
- **Staging**: `https://staging-api.wonderway.com`
- **Development**: `http://localhost:8000`

## 🔐 Authentication

تمام endpointهای محافظت شده نیاز به Bearer Token دارند:

```http
Authorization: Bearer YOUR_TOKEN_HERE
```

## 📊 ویژگیهای مستندات

- ✅ **OpenAPI 3.0.3** استاندارد
- ✅ **Multi-language** support
- ✅ **Real examples** با دیتای فارسی
- ✅ **Complete schemas** برای تمام models
- ✅ **Error responses** مفصل
- ✅ **Security definitions** کامل

## ⚠️ حقوق و مجوز

این مستندات تحت لایسنس Proprietary محافظت میشوند.
استفاده غیرمجاز ممنوع است.

**© 2025 WonderWay. All Rights Reserved.**