<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Blog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Blog>
 */
class BlogFactory extends Factory
{
    protected $model = Blog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleEn = fake()->randomElement([
            'Best Smartphones of 2024: Complete Buying Guide',
            'iPhone 15 Pro Max Review: Is It Worth the Price?',
            'Samsung Galaxy S24 Ultra vs iPhone 15 Pro: Detailed Comparison',
            'Top 10 Budget Smartphones Under $500',
            'How to Choose the Right Smartphone for Your Needs',
            'Android vs iOS: Which Operating System is Better?',
            'Camera Comparison: Latest Flagship Phones',
            '5G Smartphones: Everything You Need to Know',
            'Gaming Smartphones: Best Options for Mobile Gamers',
            'Smartphone Battery Life: Tips and Tricks',
            'Foldable Phones: Are They Worth It?',
            'Smartphone Security: How to Protect Your Device',
            'Best Smartphones for Photography in 2024',
            'Mid-Range Smartphones: Best Value for Money',
            'Smartphone Accessories: Must-Have Items',
            'How to Extend Your Smartphone\'s Lifespan',
            'Wireless Charging Explained: Pros and Cons',
            'Smartphone Display Technologies: AMOLED vs LCD',
            'Best Smartphones for Business Professionals',
            'Smartphone Storage: How Much Do You Really Need?'
        ]);

        $titleAr = fake()->randomElement([
            'أفضل الهواتف الذكية لعام 2024: دليل الشراء الكامل',
            'مراجعة iPhone 15 Pro Max: هل يستحق السعر؟',
            'Samsung Galaxy S24 Ultra مقابل iPhone 15 Pro: مقارنة مفصلة',
            'أفضل 10 هواتف ذكية بأسعار معقولة أقل من 500 دولار',
            'كيف تختار الهاتف الذكي المناسب لاحتياجاتك',
            'Android مقابل iOS: أي نظام تشغيل أفضل؟',
            'مقارنة الكاميرات: أحدث الهواتف الرائدة',
            'هواتف 5G: كل ما تحتاج معرفته',
            'هواتف الألعاب: أفضل الخيارات للاعبين المحمولين',
            'عمر بطارية الهاتف الذكي: نصائح وحيل',
            'الهواتف القابلة للطي: هل تستحق ذلك؟',
            'أمان الهاتف الذكي: كيفية حماية جهازك',
            'أفضل الهواتف الذكية للتصوير في عام 2024',
            'الهواتف الذكية متوسطة المدى: أفضل قيمة مقابل المال',
            'إكسسوارات الهاتف الذكي: العناصر الأساسية',
            'كيف تمدد عمر هاتفك الذكي',
            'الشحن اللاسلكي: الإيجابيات والسلبيات',
            'تقنيات شاشات الهواتف الذكية: AMOLED مقابل LCD',
            'أفضل الهواتف الذكية للمهنيين',
            'تخزين الهاتف الذكي: كم تحتاج حقاً؟'
        ]);

        // Generate matching short descriptions
        $shortDescEn = fake()->paragraph(2);
        $shortDescAr = 'اكتشف كل ما تحتاج معرفته عن ' . mb_substr($titleAr, 0, 30) . '... دليل شامل مع نصائح عملية ومقارنات مفصلة لمساعدتك في اتخاذ القرار الصحيح.';

        // Generate realistic blog content
        $contentEn = $this->generateBlogContent($titleEn);
        $contentAr = $this->generateBlogContentAr($titleAr);

        $isPublished = fake()->boolean(80); // 80% published, 20% drafts
        $publishedAt = $isPublished ? fake()->dateTimeBetween('-6 months', 'now') : null;

        return [
            'admin_id' => Admin::inRandomOrder()->first()?->id ?? Admin::first()?->id,
            'title_en' => $titleEn,
            'title_ar' => $titleAr,
            'short_description_en' => $shortDescEn,
            'short_description_ar' => $shortDescAr,
            'content_en' => $contentEn,
            'content_ar' => $contentAr,
            'featured_image' => null, // Can be set manually or via image seeder
            'meta_description_en' => fake()->sentence(15),
            'meta_description_ar' => 'وصف SEO بالعربية ' . fake()->sentence(10),
            'meta_keywords_en' => implode(', ', fake()->words(5)),
            'meta_keywords_ar' => implode(', ', ['هاتف ذكي', 'مراجعة', 'مقارنة', 'دليل شراء']),
            'is_published' => $isPublished,
            'published_at' => $publishedAt,
            'views_count' => $isPublished ? fake()->numberBetween(0, 5000) : 0,
            'allow_comments' => fake()->boolean(90), // 90% allow comments
        ];
    }

    /**
     * Generate realistic English blog content
     */
    private function generateBlogContent(string $title): string
    {
        $intro = fake()->paragraph(3);
        $body = fake()->paragraphs(5, true);
        $conclusion = fake()->paragraph(2);
        
        return $intro . "\n\n" . $body . "\n\n" . $conclusion;
    }

    /**
     * Generate realistic Arabic blog content
     */
    private function generateBlogContentAr(string $title): string
    {
        $intro = 'في هذا المقال الشامل، سنتناول بالتفصيل ' . mb_substr($title, 0, 50) . '. ' . fake()->paragraph(2);
        $body = fake()->paragraphs(5, true);
        $conclusion = 'في الختام، نأمل أن يكون هذا المقال قد ساعدك في فهم ' . mb_substr($title, 0, 30) . ' بشكل أفضل. ' . fake()->paragraph(2);
        
        return $intro . "\n\n" . $body . "\n\n" . $conclusion;
    }
}
