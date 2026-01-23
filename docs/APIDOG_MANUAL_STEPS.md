# دليل خطوات استخدام Apidog يدوياً

## 📱 الخطوة 1: فتح Apidog

1. افتح تطبيق **Apidog** على جهازك
2. إذا لم يكن لديك حساب، قم بإنشاء حساب جديد أو سجل الدخول

---

## 📥 الخطوة 2: إنشاء Request جديد

### الطريقة الأولى: إنشاء Request جديد يدوياً

1. اضغط على زر **"+"** أو **"New Request"** في Apidog
2. اختر **"HTTP Request"** أو **"API Request"**

---

## 🔧 الخطوة 3: إعداد Request الأساسي

### 3.1 اختيار Method
- في حقل **Method**، اختر **POST**

### 3.2 إدخال URL
- في حقل **URL**، اكتب:
```
http://localhost:8000/api/v1/tickets
```
أو إذا كان لديك domain مختلف:
```
https://yourdomain.com/api/v1/tickets
```

---

## 📋 الخطوة 4: إعداد Headers

1. اضغط على تبويب **Headers**
2. أضف الـ Headers التالية:

**Header 1:**
- **Key**: `Accept`
- **Value**: `application/json`

**Header 2:**
- **Key**: `Content-Type`
- **Value**: `application/json`

---

## 📝 الخطوة 5: إعداد Body

1. اضغط على تبويب **Body**
2. اختر **raw**
3. من القائمة المنسدلة بجانب **raw**، اختر **JSON**
4. في حقل النص، اكتب:

```json
{
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "phone": "0123456789",
    "message": "أريد الاستفسار عن منتج معين وأحتاج إلى معلومات إضافية حول المواصفات والأسعار."
}
```

---

## 🚀 الخطوة 6: إرسال الطلب

1. اضغط على زر **Send** (إرسال) أو **▶️**
2. انتظر الاستجابة

---

## ✅ الخطوة 7: قراءة الاستجابة

### إذا نجح الطلب (Status: 200 أو 201):

ستظهر استجابة مثل:

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

**احفظ رقم التذكرة (`ticket_number`) للمتابعة**

---

## ❌ الخطوة 8: التعامل مع الأخطاء

### خطأ 422 (Validation Error):

إذا ظهر خطأ مثل:

```json
{
    "message": "The name field is required.",
    "errors": {
        "name": ["The name field is required."]
    }
}
```

**الحل:**
- تأكد من إرسال جميع الحقول المطلوبة
- تأكد من صحة تنسيق JSON

### خطأ 500 (Server Error):

**الحل:**
1. تأكد من أن الـ server يعمل: `php artisan serve`
2. تأكد من تشغيل الـ migration: `php artisan migrate`
3. تحقق من الـ logs: `storage/logs/laravel.log`

### خطأ 404 (Not Found):

**الحل:**
- تأكد من صحة الـ URL
- تأكد من أن الـ route موجود في `routes/api.php`

---

## 🔄 الخطوة 9: اختبار حالات مختلفة

### اختبار 1: تذكرة شكوى

```json
{
    "name": "سارة أحمد",
    "email": "sara@example.com",
    "phone": "0501234567",
    "message": "لدي شكوى بخصوص المنتج الذي اشتريته، لم يصل بعد رغم مرور أسبوعين على الطلب."
}
```

### اختبار 2: استفسار تقني

```json
{
    "name": "محمد علي",
    "email": "mohammed@example.com",
    "phone": "0551234567",
    "message": "أحتاج إلى مساعدة في إعداد المنتج، هل يمكنكم تزويدي بدليل الاستخدام؟"
}
```

### اختبار 3: اختبار الأخطاء (حقل مفقود)

```json
{
    "name": "أحمد محمد",
    "email": "ahmed@example.com"
}
```
(ملاحظة: هذا سيفشل لأن `phone` و `message` مفقودان)

---

## 📸 الخطوة 10: حفظ Request للمستقبل

1. اضغط على زر **Save** أو **💾**
2. أدخل اسم للـ Request، مثلاً: "Create Guest Ticket"
3. اختر مكان الحفظ (Collection أو Folder)
4. اضغط **Save**

---

## 🔍 الخطوة 11: التحقق من التذكرة في قاعدة البيانات

بعد إنشاء التذكرة بنجاح، يمكنك التحقق منها:

### من لوحة التحكم (Admin Panel):
1. افتح: `http://localhost:8000/admin`
2. اذهب إلى **التذاكر**
3. ستجد التذكرة الجديدة

### من قاعدة البيانات:
```sql
SELECT * FROM tickets ORDER BY created_at DESC LIMIT 1;
```

---

## 📋 قائمة التحقق (Checklist)

قبل إرسال الطلب، تأكد من:

- [ ] الـ server يعمل (`php artisan serve`)
- [ ] الـ migration تم تشغيله (`php artisan migrate`)
- [ ] Method = **POST**
- [ ] URL صحيح: `http://localhost:8000/api/v1/tickets`
- [ ] Headers موجودة: `Accept` و `Content-Type`
- [ ] Body format = **JSON**
- [ ] جميع الحقول المطلوبة موجودة:
  - [ ] `name`
  - [ ] `email`
  - [ ] `phone`
  - [ ] `message`

---

## 🎯 نصائح إضافية

1. **استخدم Environment Variables:**
   - أنشئ Environment في Apidog
   - أضف متغير `base_url` = `http://localhost:8000`
   - استخدم `{{base_url}}/api/v1/tickets` في URL

2. **احفظ أمثلة الاستجابة:**
   - احفظ الاستجابة الناجحة كـ Example
   - احفظ الأخطاء الشائعة كـ Examples

3. **استخدم Tests:**
   - أضف اختبارات تلقائية للتحقق من الاستجابة
   - مثال: `pm.test("Status code is 201", function () { pm.response.to.have.status(201); });`

---

## 🆘 حل المشاكل الشائعة

### المشكلة: "Connection refused"
**الحل:** تأكد من أن الـ server يعمل على المنفذ الصحيح

### المشكلة: "JSON Parse Error"
**الحل:** تأكد من صحة تنسيق JSON (استخدم JSON validator)

### المشكلة: "CORS Error"
**الحل:** تأكد من إعدادات CORS في Laravel

---

## 📞 الدعم

إذا استمرت المشاكل:
1. تحقق من `storage/logs/laravel.log`
2. تحقق من `routes/api.php` للتأكد من وجود الـ route
3. تحقق من `app/Http/Controllers/Api/V1/TicketController.php`

