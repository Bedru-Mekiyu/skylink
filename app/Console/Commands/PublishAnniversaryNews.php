<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Models\NewsCategory;
use App\Support\NewsBody;
use App\Support\NewsImageProcessor;
use App\Support\NewsSlugs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Publishes the five-year anniversary announcement (English and Amharic) as the
 * newest news articles. Safe to run more than once: an article whose slug already
 * exists (even soft-deleted) is skipped. Not tied to the campaign dates; the
 * articles stay as normal news afterwards.
 *
 * The cover (resources/news/anniversary-five-years-cover.png) goes through the
 * same image pipeline as admin uploads (web/thumb WebP + JPEG share copy), so the
 * server needs PHP's GD extension, like the admin news form.
 */
class PublishAnniversaryNews extends Command
{
    protected $signature = 'news:publish-anniversary
                            {--feature : Also make the English article the featured story (un-features the current one, as the admin form does)}';

    protected $description = 'Publish the Sky Link five-year anniversary news article (English + Amharic) if not already published';

    public const COVER = 'resources/news/anniversary-five-years-cover.png';

    public const CATEGORY = 'News';

    /** Amharic first, so the English article is the newest one. */
    public const ARTICLES = [
        'am' => [
            'url_title' => 'Sky Link ICT Solutions celebrates five years Amharic',
            'title' => 'ስካይ ሊንክ አይሲቲ ሶሉሽንስ አምስተኛ ዓመቱን ያከብራል',
            'excerpt' => 'ከአምስት ዓመታት በፊት ስካይ ሊንክ አይሲቲ ሶሉሽንስ አስተማማኝ ቴክኖሎጂን በጥንቃቄ ለማቅረብ በሚል ቀላል ቁርጠኝነት ጉዞውን ጀመረ። ዛሬ ይህንን ምዕራፍ በምስጋና እያከበርን ወደፊትም በዓላማ እንመለከታለን።',
            'body' => "በዚህ ወቅት ስካይ ሊንክ አይሲቲ ሶሉሽንስ አምስተኛ ዓመቱን ያከብራል፤ ይህም የአምስት ዓመታት ቁርጠኝነትን፣ ትምህርትንና የማያቋርጥ ዕድገትን የሚያንጸባርቅ ምዕራፍ ነው።\n\n"
                .'ያከናወንናቸው ፕሮጀክቶች ሁሉ በዙሪያችን ባሉ ሰዎች የተቀረጹ ናቸው፤ ሙያንና ትጋትን ይዞ የሚሠራ ቁርጠኛ ቡድናችን፣ እምነታቸውን የጣሉብን ደንበኞቻችን፣ '
                ."ከጎናችን የሠሩ አጋሮቻችን እንዲሁም ዕድገታችንን የደገፈ ማህበረሰባችን።\n\n"
                .'ይህ የምሥረታ በዓል ከሁሉም በላይ የምስጋና ጊዜ ነው። በጋራ በተጓዝንበት መንገድ እንኮራለን፤ ከዚሁ ጋር አብሮ የሚመጣውን ኃላፊነት፣ ማለትም '
                ."አስተማማኝና በጥንቃቄ የታሰቡ የቴክኖሎጂ መፍትሄዎችን ማቅረባችንን መቀጠል፣ ሁሌም እናስታውሳለን።\n\n"
                .'ቀጣዩን ምዕራፋችንን ስንጀምር ቡድናችንን፣ ደንበኞቻችንን፣ አጋሮቻችንንና ማህበረሰባችንን ይህንን ምዕራፍ ከእኛ ጋር እንዲያከብሩ እንጋብዛለን። '
                ."ስለ ምሥረታ በዓል ዝግጅታችን ተጨማሪ መረጃ በይፋዊ የመገናኛ መንገዶቻችን ይገለጻል።\n\n"
                .'የስካይ ሊንክ ታሪክ አካል ስለሆኑ እናመሰግናለን።',
        ],
        'en' => [
            'url_title' => 'Sky Link ICT Solutions celebrates five years',
            'title' => 'Sky Link ICT Solutions Celebrates Five Years',
            'excerpt' => 'Five years ago, Sky Link ICT Solutions began with a simple commitment: to deliver reliable technology with care. Today, we mark this milestone with gratitude and look ahead with purpose.',
            'body' => "This season, Sky Link ICT Solutions celebrates its fifth anniversary, a milestone that reflects five years of dedication, learning and steady progress.\n\n"
                .'Every project we have delivered has been shaped by the people around us: a committed team that brings skill and care to its work, '
                ."clients who have placed their trust in us, partners who have worked alongside us, and a community that has supported our growth.\n\n"
                .'This anniversary is, above all, a moment of gratitude. We are proud of the path we have travelled together and remain focused on the '
                ."responsibility that comes with it: to keep delivering dependable, thoughtful technology solutions.\n\n"
                .'As we begin our next chapter, we invite our team, clients, partners and community to celebrate this milestone with us. '
                ."Further details about our anniversary celebration will be shared through our official channels.\n\n"
                .'Thank you for being part of the Sky Link story.',
        ],
    ];

    public function handle(NewsImageProcessor $images): int
    {
        $category = NewsCategory::where('name', self::CATEGORY)->first();
        if (! $category) {
            $this->error('News category "'.self::CATEGORY.'" not found; nothing was published.');

            return self::FAILURE;
        }

        $cover = base_path(self::COVER);
        if (! is_readable($cover)) {
            $this->error('Cover image missing: '.self::COVER);

            return self::FAILURE;
        }

        $publishedAt = now();
        $created = 0;
        foreach (self::ARTICLES as $language => $article) {
            $slug = NewsSlugs::fromUrlTitle($article['url_title']);
            if (News::withTrashed()->where('slug', $slug)->exists()) {
                $this->line("Skipped ({$language}): an article with slug \"{$slug}\" already exists.");

                continue;
            }

            try {
                $paths = $images->makeCopies((string) file_get_contents($cover));
            } catch (Throwable $e) {
                $this->error("Cover image could not be processed ({$e->getMessage()}); nothing was published for {$language}.");

                return self::FAILURE;
            }

            try {
                $news = DB::transaction(function () use ($article, $language, $category, $paths, $publishedAt) {
                    $news = new News();
                    $body = NewsBody::sanitize(NewsBody::fromPlainText($article['body']));
                    $news->fill([
                        'title' => $article['title'],
                        'language' => $language,
                        'url_title' => $article['url_title'],
                        'news_category_id' => $category->id,
                        'content' => $body,
                        'excerpt' => $article['excerpt'],
                        'is_featured' => false,
                    ]);
                    $news->content_format = 'html';
                    $news->status = News::STATUS_PUBLISHED;
                    // One second apart so the English article is the newest.
                    $news->published_at = $language === 'en' ? $publishedAt : $publishedAt->copy()->subSecond();
                    foreach ($paths as $column => $path) {
                        $news->{$column} = $path;
                    }
                    $news->slug = 'pending-'.bin2hex(random_bytes(12)); // replaced below, once the id is known
                    $news->save();
                    NewsSlugs::apply($news, NewsSlugs::desiredFor($news), false);

                    return $news;
                });
            } catch (Throwable $e) {
                Storage::disk('public')->delete(array_values($paths));
                throw $e;
            }

            $created++;
            $this->info("Published ({$language}): /news/{$news->slug}");
        }

        if ($this->option('feature')) {
            $english = News::where('slug', NewsSlugs::fromUrlTitle(self::ARTICLES['en']['url_title']))->first();
            if ($english) {
                DB::transaction(function () use ($english) {
                    News::whereKeyNot($english->id)->where('is_featured', true)->update(['is_featured' => false]);
                    $english->forceFill(['is_featured' => true])->save();
                });
                $this->info('The English article is now the featured story.');
            }
        } elseif ($other = News::visible()->where('is_featured', true)->first()) {
            $this->warn("Note: \"{$other->title}\" is the featured story on /news. Re-run with --feature to show the anniversary article there instead.");
        }

        $this->info("{$created} article(s) published.");

        return self::SUCCESS;
    }
}
