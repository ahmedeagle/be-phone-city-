<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'terms-and-conditions',
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms & Conditions',
                'description_ar' => '<h2>الشروط والأحكام</h2><p>مرحباً بكم في سيتي فون. باستخدامك لموقعنا فإنك توافق على الشروط والأحكام التالية:</p><ul><li>جميع المنتجات المعروضة أصلية ومكفولة.</li><li>الأسعار قابلة للتغيير دون إشعار مسبق.</li><li>يحق للمتجر رفض أي طلب لأسباب تتعلق بالتوفر أو التسعير.</li><li>يتحمل العميل مسؤولية صحة بيانات الشحن المدخلة.</li><li>لا يحق للعميل استخدام الموقع لأغراض غير مشروعة.</li></ul>',
                'description_en' => '<h2>Terms & Conditions</h2><p>Welcome to CityPhone. By using our website, you agree to the following terms:</p><ul><li>All products displayed are original and guaranteed.</li><li>Prices are subject to change without prior notice.</li><li>The store reserves the right to reject any order for availability or pricing reasons.</li><li>The customer is responsible for the accuracy of shipping information.</li><li>The website must not be used for unlawful purposes.</li></ul>',
                'is_active' => true,
                'can_delete' => false,
            ],
            [
                'slug' => 'warranty-policy',
                'title_ar' => 'سياسة الضمان',
                'title_en' => 'Warranty Policy',
                'description_ar' => '<h2>سياسة الضمان</h2><p>نقدم ضمان على جميع منتجاتنا وفقاً للشروط التالية:</p><ul><li>ضمان لمدة سنة على جميع الأجهزة الجديدة ضد عيوب التصنيع.</li><li>لا يشمل الضمان الأضرار الناتجة عن سوء الاستخدام أو السقوط أو الماء.</li><li>يجب تقديم فاتورة الشراء عند طلب خدمة الضمان.</li><li>الإكسسوارات مكفولة لمدة 6 أشهر.</li><li>يتم فحص الجهاز خلال 3-5 أيام عمل لتحديد ما إذا كان العطل مشمولاً بالضمان.</li></ul>',
                'description_en' => '<h2>Warranty Policy</h2><p>We provide warranty on all our products according to the following terms:</p><ul><li>One-year warranty on all new devices against manufacturing defects.</li><li>Warranty does not cover damage from misuse, drops, or water.</li><li>Purchase invoice must be presented when requesting warranty service.</li><li>Accessories are warranted for 6 months.</li><li>Device inspection takes 3-5 business days to determine warranty coverage.</li></ul>',
                'is_active' => true,
                'can_delete' => false,
            ],
            [
                'slug' => 'about-quwara',
                'title_ar' => 'المزيد عن كوارا',
                'title_en' => 'More About Qwara',
                'description_ar' => '<h2>عن كوارا</h2><p>كوارا هي علامتنا التجارية المتخصصة في تقديم أفضل الإكسسوارات والحماية لأجهزتكم الذكية. نحرص على توفير منتجات عالية الجودة بأسعار منافسة مع ضمان شامل.</p>',
                'description_en' => '<h2>About Qwara</h2><p>Qwara is our brand specializing in providing the best accessories and protection for your smart devices. We ensure high-quality products at competitive prices with comprehensive warranty.</p>',
                'is_active' => true,
                'can_delete' => false,
            ],
            [
                'slug' => 'about-mowara',
                'title_ar' => 'المزيد عن موارا',
                'title_en' => 'More About Moara',
                'description_ar' => '<h2>عن موارا</h2><p>موارا هي علامتنا التجارية الرائدة في مجال الهواتف الذكية المُجددة بمعايير احترافية. جميع الأجهزة تمر بفحص شامل ومعايرة دقيقة لضمان أعلى جودة.</p>',
                'description_en' => '<h2>About Moara</h2><p>Moara is our leading brand in professionally refurbished smartphones. All devices undergo comprehensive inspection and precise calibration to ensure the highest quality.</p>',
                'is_active' => true,
                'can_delete' => false,
            ],
            [
                'slug' => 'return-policy',
                'title_ar' => 'سياسة الإستبدال والإرجاع',
                'title_en' => 'Return & Exchange Policy',
                'description_ar' => '<h2>سياسة الإستبدال والإرجاع</h2><p>نسعى لرضاكم التام، ولذلك نوفر سياسة استبدال وإرجاع عادلة:</p><ul><li>يمكن استبدال أو إرجاع المنتج خلال 7 أيام من تاريخ الاستلام.</li><li>يجب أن يكون المنتج في حالته الأصلية مع جميع الملحقات والتغليف.</li><li>لا يتم قبول الإرجاع للمنتجات التي تم استخدامها أو فتح غلافها (للإكسسوارات).</li><li>يتم رد المبلغ خلال 5-10 أيام عمل بعد استلام المنتج المرتجع وفحصه.</li><li>تكاليف الشحن للإرجاع يتحملها العميل إلا في حالة وجود عيب في المنتج.</li></ul>',
                'description_en' => '<h2>Return & Exchange Policy</h2><p>We strive for your complete satisfaction and provide a fair return and exchange policy:</p><ul><li>Products can be returned or exchanged within 7 days of receipt.</li><li>Product must be in original condition with all accessories and packaging.</li><li>Returns are not accepted for used or opened products (for accessories).</li><li>Refunds are processed within 5-10 business days after receiving and inspecting the returned product.</li><li>Return shipping costs are borne by the customer unless the product is defective.</li></ul>',
                'is_active' => true,
                'can_delete' => false,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
