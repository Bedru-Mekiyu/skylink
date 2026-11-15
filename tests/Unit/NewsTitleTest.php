<?php

namespace Tests\Unit;

use App\Support\NewsTitle;
use PHPUnit\Framework\TestCase;

class NewsTitleTest extends TestCase
{
    public function test_normalize_ignores_case_spacing_punctuation_and_emoji(): void
    {
        $this->assertSame('sky link wins award', NewsTitle::normalize("  Sky-Link   WINS award!! 🎉 "));
        $this->assertSame('ስካይ ሊንክ ዜና', NewsTitle::normalize('ስካይ ሊንክ፣ ዜና።'));
    }

    public function test_similarity_for_english_and_amharic(): void
    {
        $this->assertTrue(NewsTitle::isSimilar('Windows 10 support ends on October 14 2025', 'Windows 10 support ends October 14, 2025!'));
        $this->assertTrue(NewsTitle::isSimilar('እንኳን ደስ አላችሁ!', 'እንኳን ደስ አላችሁ'));
        $this->assertTrue(NewsTitle::isSimilar('Minber TV', '5 Minber TV'));
        $this->assertTrue(NewsTitle::isSimilar('Support for Windows 10 will end', 'Support for Windows 10 will come to end today October 14, 2025'));
    }

    public function test_different_titles_are_not_similar(): void
    {
        $this->assertFalse(NewsTitle::isSimilar('Opening of the Dire Dawa office', 'New cyber security training'));
        $this->assertFalse(NewsTitle::isSimilar('', 'Anything'));
        $this->assertFalse(NewsTitle::isSimilar('New training for our staff', 'New office opening for our clients in Adama'));
    }
}
