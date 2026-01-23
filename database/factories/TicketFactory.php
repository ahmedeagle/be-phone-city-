<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Mix of English and Arabic subjects (users submit in their preferred language)
        $subjects = [
            'Product not working after purchase',
            'المنتج لا يعمل بعد الشراء',
            'Order delivery delay issue',
            'مشكلة تأخير توصيل الطلب',
            'Payment not processed correctly',
            'لم يتم معالجة الدفع بشكل صحيح',
            'Need help with product setup',
            'أحتاج مساعدة في إعداد المنتج',
            'Product damaged during shipping',
            'المنتج تالف أثناء الشحن',
            'Wrong product received',
            'تم استلام منتج خاطئ',
            'Refund request for cancelled order',
            'طلب استرداد للطلب الملغي',
            'Account login problems',
            'مشاكل تسجيل الدخول للحساب',
            'Product warranty inquiry',
            'استفسار عن ضمان المنتج',
            'Billing statement question',
            'سؤال حول كشف الفواتير',
            'Technical support needed',
            'حاجة لدعم فني',
            'Product compatibility question',
            'سؤال حول توافق المنتج',
            'Order tracking information',
            'معلومات تتبع الطلب',
            'Return and exchange request',
            'طلب إرجاع واستبدال',
            'Product feature inquiry',
            'استفسار عن ميزات المنتج',
            'Website navigation issue',
            'مشكلة في التنقل بالموقع',
            'Mobile app not working',
            'تطبيق الهاتف لا يعمل',
            'Points balance incorrect',
            'رصيد النقاط غير صحيح',
            'Discount code not applying',
            'كود الخصم لا يعمل',
            'Shipping address change request',
            'طلب تغيير عنوان الشحن',
        ];

        $descriptions = [
            'I purchased this product last week and it stopped working after only 3 days of use. The screen is not responding to touch and the device keeps restarting.',
            'اشتريت هذا المنتج الأسبوع الماضي وتوقف عن العمل بعد 3 أيام فقط من الاستخدام. الشاشة لا تستجيب للمس والجهاز يعيد التشغيل باستمرار.',
            'My order was placed 10 days ago but I still haven\'t received it. The tracking shows it\'s been stuck at the shipping facility for 5 days.',
            'تم تقديم طلبي منذ 10 أيام لكنني لم أستلمه بعد. التتبع يظهر أنه عالق في منشأة الشحن لمدة 5 أيام.',
            'I was charged twice for the same order. I only placed one order but my credit card shows two charges. Please help resolve this issue.',
            'تم خصم المبلغ مرتين لنفس الطلب. قمت بتقديم طلب واحد فقط لكن بطاقتي الائتمانية تظهر شحنتين. يرجى المساعدة في حل هذه المشكلة.',
            'I received the product but I\'m having trouble setting it up. The instructions are not clear and I need assistance with the initial configuration.',
            'استلمت المنتج لكنني أواجه صعوبة في إعداده. التعليمات غير واضحة وأحتاج مساعدة في الإعداد الأولي.',
            'The product arrived damaged. The packaging was torn and the product inside has visible scratches and dents. I need a replacement.',
            'المنتج وصل تالفاً. التغليف ممزق والمنتج بداخله به خدوش ونتوءات واضحة. أحتاج استبدال.',
            'I ordered a different product but received something else. The order number matches but the product is completely different from what I ordered.',
            'طلبت منتجاً مختلفاً لكنني استلمت شيئاً آخر. رقم الطلب متطابق لكن المنتج مختلف تماماً عما طلبته.',
            'I cancelled my order within the cancellation period but haven\'t received my refund yet. It\'s been 7 business days since cancellation.',
            'ألغيت طلبي خلال فترة الإلغاء لكنني لم أستلم استردادي بعد. مرت 7 أيام عمل منذ الإلغاء.',
            'I cannot log into my account. I\'m using the correct email and password but keep getting an error message. I also tried password reset but didn\'t receive the email.',
            'لا أستطيع تسجيل الدخول إلى حسابي. أستخدم البريد الإلكتروني وكلمة المرور الصحيحة لكنني أحصل على رسالة خطأ. جربت أيضاً إعادة تعيين كلمة المرور لكنني لم أستلم البريد.',
            'I want to know if my product is still under warranty. I purchased it 8 months ago and it\'s starting to show some issues.',
            'أريد معرفة ما إذا كان منتجي لا يزال تحت الضمان. اشتريته منذ 8 أشهر وبدأ يظهر بعض المشاكل.',
            'I have a question about my recent billing statement. There are some charges I don\'t recognize and I need clarification.',
            'لدي سؤال حول كشف الفواتير الأخير. هناك بعض الرسوم التي لا أتعرف عليها وأحتاج توضيح.',
            'I need technical support with my product. It\'s not connecting to the network properly and I\'ve tried all the troubleshooting steps.',
            'أحتاج دعم فني مع منتجي. لا يتصل بالشبكة بشكل صحيح وجربت جميع خطوات استكشاف الأخطاء.',
            'I want to know if this product is compatible with my existing devices. I have specific requirements and need to confirm before purchase.',
            'أريد معرفة ما إذا كان هذا المنتج متوافقاً مع أجهزتي الموجودة. لدي متطلبات محددة وأحتاج تأكيد قبل الشراء.',
            'I placed an order 5 days ago and haven\'t received any tracking information. I need to know the status of my order and expected delivery date.',
            'قدمت طلباً منذ 5 أيام ولم أتلق أي معلومات تتبع. أحتاج معرفة حالة طلبي وتاريخ التسليم المتوقع.',
            'I received the wrong size/color. I need to return this item and exchange it for the correct one. What is the return process?',
            'استلمت المقاس/اللون الخاطئ. أحتاج إرجاع هذا العنصر واستبداله بالصحيح. ما هي عملية الإرجاع؟',
            'I\'m interested in purchasing this product but have questions about its features. Can you provide more details about specific functionalities?',
            'أنا مهتم بشراء هذا المنتج لكن لدي أسئلة حول ميزاته. هل يمكنك تقديم المزيد من التفاصيل حول الوظائف المحددة؟',
            'I\'m having trouble navigating the website. Some pages are not loading properly and I cannot complete my purchase.',
            'أواجه مشكلة في التنقل بالموقع. بعض الصفحات لا تحمل بشكل صحيح ولا أستطيع إكمال شرائي.',
            'The mobile app crashes every time I try to view my orders. I\'ve tried reinstalling it but the problem persists.',
            'تطبيق الهاتف يتعطل في كل مرة أحاول فيها عرض طلباتي. جربت إعادة تثبيته لكن المشكلة مستمرة.',
            'My points balance shows incorrectly. I should have more points based on my recent purchases but the balance hasn\'t updated.',
            'رصيد نقاطي يظهر بشكل غير صحيح. يجب أن يكون لدي المزيد من النقاط بناءً على مشترياتي الأخيرة لكن الرصيد لم يتحدث.',
            'I have a discount code but it\'s not applying at checkout. The code is valid and I\'m entering it correctly but getting an error.',
            'لدي كود خصم لكنه لا يعمل عند الدفع. الكود صالح وأدخله بشكل صحيح لكنني أحصل على خطأ.',
            'I need to change my shipping address for an order that hasn\'t shipped yet. How can I update the delivery address?',
            'أحتاج تغيير عنوان الشحن لطلب لم يتم شحنه بعد. كيف يمكنني تحديث عنوان التسليم؟',
        ];

        $subject = fake()->randomElement($subjects);
        $description = fake()->randomElement($descriptions);

        $status = fake()->randomElement([
            Ticket::STATUS_PENDING,
            Ticket::STATUS_PENDING,
            Ticket::STATUS_IN_PROGRESS,
            Ticket::STATUS_IN_PROGRESS,
            Ticket::STATUS_RESOLVED,
            Ticket::STATUS_CLOSED,
        ]);

        $priority = fake()->randomElement([
            Ticket::PRIORITY_LOW,
            Ticket::PRIORITY_MEDIUM,
            Ticket::PRIORITY_MEDIUM,
            Ticket::PRIORITY_HIGH,
            Ticket::PRIORITY_URGENT,
        ]);

        $type = fake()->randomElement([
            Ticket::TYPE_SUPPORT,
            Ticket::TYPE_COMPLAINT,
            Ticket::TYPE_INQUIRY,
            Ticket::TYPE_TECHNICAL,
            Ticket::TYPE_BILLING,
            Ticket::TYPE_OTHER,
        ]);

        // Assign admin for in_progress and resolved tickets
        $adminId = null;
        if (in_array($status, [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED])) {
            $adminId = Admin::inRandomOrder()->first()?->id;
        }

        // Set resolved_at for resolved tickets
        $resolvedAt = null;
        $resolutionNotes = null;
        if ($status === Ticket::STATUS_RESOLVED) {
            $resolvedAt = fake()->dateTimeBetween('-30 days', 'now');
            $resolutionNotes = fake()->randomElement([
                'تم حل المشكلة من خلال استبدال المنتج.',
                'تم حل المشكلة عن طريق تحديث البرنامج.',
                'تم حل المشكلة من خلال إعادة تعيين الإعدادات.',
                'تم حل المشكلة. العميل راضٍ عن الحل.',
                'تم حل المشكلة من خلال استرداد المبلغ.',
            ]);
        }

        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'admin_id' => $adminId,
            'subject' => $subject,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'type' => $type,
            'resolved_at' => $resolvedAt,
            'resolution_notes' => $resolutionNotes,
            'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
        ];
    }
}
