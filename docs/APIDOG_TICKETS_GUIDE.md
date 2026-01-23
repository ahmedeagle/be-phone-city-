# دليل استخدام Apidog لاختبار API التذاكر

## 📋 الخطوات الأساسية

### 1. استيراد Collection إلى Apidog

1. افتح **Apidog**
2. اضغط على **Import** (استيراد)
3. اختر **Import from File**
4. اختر الملف: `postman/Tickets_Collection.json`
5. اضغط **Import**

---

## 🔧 إعداد المتغيرات (Variables)

### بعد الاستيراد، قم بتعديل المتغيرات:

1. اذهب إلى **Environment** أو **Variables**
2. اضبط المتغيرات التالية:

| المتغير | القيمة | الوصف |
|---------|--------|-------|
| `base_url` | `http://localhost:8000` | رابط الـ API الأساسي |
| `auth_token` | (اتركه فارغاً للتذاكر العامة) | Token للمصادقة (للمسارات المحمية فقط) |

---

## 🎯 اختبار إنشاء تذكرة جديدة (Guest Ticket)

### الخطوات:

1. افتح **Apidog**
2. ابحث عن **"Create Guest Ticket"** في **Public Endpoints**
3. تأكد من:
   - **Method**: `POST`
   - **URL**: `{{base_url}}/api/v1/tickets`
   - **Headers**: 
     - `Accept: application/json`
     - `Content-Type: application/json`
   - **Body** (JSON):

```json
{
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "phone": "0123456789",
    "message": "أريد الاستفسار عن منتج معين وأحتاج إلى معلومات إضافية حول المواصفات والأسعار."
}
```

4. اضغط **Send**

---

## ✅ الاستجابة المتوقعة (Success Response)

```json
{
    "success": true,
    "message": "Ticket created successfully",
    "data": {
        "id": 1,
        "ticket_number": "TKT-20260103-ABC123",
        "name": "أحمد محمد",
        "email": "ahmed@example.com",
        "phone": "0123456789",
        "subject": "أريد الاستفسار عن منتج معين وأحتاج إلى معلومات إضافية...",
        "message": "أريد الاستفسار عن منتج معين وأحتاج إلى معلومات إضافية حول المواصفات والأسعار.",
        "status": "pending",
        "status_label": "قيد الانتظار",
        "priority": "medium",
        "priority_label": "متوسط",
        "type": "support",
        "type_label": "دعم فني",
        "user": null,
        "admin": null,
        "resolution_notes": null,
        "resolved_at": null,
        "images": [],
        "is_open": true,
        "is_resolved": false,
        "is_closed": false,
        "created_at": "2026-01-03T20:30:00.000000Z",
        "updated_at": "2026-01-03T20:30:00.000000Z"
    }
}
```

---

## ❌ الأخطاء المحتملة

### 1. خطأ التحقق من البيانات (Validation Error)

```json
{
    "message": "The name field is required.",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email field is required."],
        "phone": ["The phone field is required."],
        "message": ["The message field is required."]
    }
}
```

**الحل**: تأكد من إرسال جميع الحقول المطلوبة

---

### 2. خطأ في تنسيق البريد الإلكتروني

```json
{
    "message": "The email must be a valid email address.",
    "errors": {
        "email": ["The email must be a valid email address."]
    }
}
```

**الحل**: تأكد من صحة تنسيق البريد الإلكتروني

---

### 3. خطأ في طول الحقول

```json
{
    "message": "The name may not be greater than 255 characters.",
    "errors": {
        "name": ["The name may not be greater than 255 characters."]
    }
}
```

**الحل**: تأكد من أن الحقول لا تتجاوز الحد الأقصى المسموح

---

## 📝 أمثلة إضافية

### مثال 1: تذكرة شكوى

```json
{
    "name": "سارة أحمد",
    "email": "sara@example.com",
    "phone": "0501234567",
    "message": "لدي شكوى بخصوص المنتج الذي اشتريته، لم يصل بعد رغم مرور أسبوعين على الطلب."
}
```

### مثال 2: استفسار تقني

```json
{
    "name": "محمد علي",
    "email": "mohammed@example.com",
    "phone": "0551234567",
    "message": "أحتاج إلى مساعدة في إعداد المنتج، هل يمكنكم تزويدي بدليل الاستخدام؟"
}
```

---

## 🔍 نصائح للاختبار

1. **اختبر الحقول المطلوبة**: حاول إرسال طلب بدون أحد الحقول المطلوبة
2. **اختبر التنسيق**: جرب بريد إلكتروني غير صحيح
3. **اختبر الطول**: جرب رسالة طويلة جداً (أكثر من 5000 حرف)
4. **اختبر الحروف العربية**: تأكد من أن النظام يدعم الحروف العربية بشكل صحيح

---

## 📚 المسارات الأخرى (Protected Endpoints)

لاختبار المسارات المحمية (التي تحتاج مصادقة):

1. سجل الدخول أولاً للحصول على `auth_token`
2. أضف `auth_token` في متغيرات البيئة
3. استخدم المسارات في **Protected Endpoints**

---

## 🚀 بعد الاختبار

بعد إنشاء التذكرة بنجاح، يمكنك:
- عرض التذكرة من لوحة التحكم (Admin Panel)
- متابعة حالة التذكرة
- تعيين موظف دعم للتذكرة
- إضافة ملاحظات الحل

---

## 📞 الدعم

إذا واجهت أي مشاكل:
1. تأكد من أن الـ server يعمل: `php artisan serve`
2. تأكد من تشغيل الـ migration: `php artisan migrate`
3. تحقق من الـ logs: `storage/logs/laravel.log`

