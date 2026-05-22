<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Neil Widdowson',
            'email' => 'neil@roberttodds.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $aw25 = Season::create([
            'name' => 'Autumn/Winter',
            'year' => 2025,
            'description' => 'Discover the key trends shaping knitwear and textiles for Autumn/Winter 2025. From heritage textures to modern blends.',
            'subscription_price' => 149.00,
            'status' => 'published',
        ]);

        $ss26 = Season::create([
            'name' => 'Spring/Summer',
            'year' => 2026,
            'description' => 'Fresh perspectives on yarn innovation, colour direction, and fabric development for the Spring/Summer 2026 season.',
            'subscription_price' => 149.00,
            'status' => 'published',
        ]);

        Publication::create([
            'season_id' => $aw25->id,
            'title' => 'Colour Directions AW25',
            'description' => 'Comprehensive colour palette forecasting for the Autumn/Winter 2025 season across menswear, womenswear, and accessories.',
            'price' => 49.00,
            'sort_order' => 1,
            'status' => 'published',
            'is_featured' => true,
        ]);

        Publication::create([
            'season_id' => $aw25->id,
            'title' => 'Yarn Trends AW25',
            'description' => 'Exploration of key yarn developments, fibre innovations, and textural directions for knitwear.',
            'price' => 49.00,
            'sort_order' => 2,
            'status' => 'published',
        ]);

        Publication::create([
            'season_id' => $aw25->id,
            'title' => 'Stitch & Pattern AW25',
            'description' => 'Key stitch structures, pattern developments, and surface techniques for the coming season.',
            'price' => 39.00,
            'sort_order' => 3,
            'status' => 'published',
        ]);

        Publication::create([
            'season_id' => $aw25->id,
            'title' => 'Market Intelligence AW25',
            'description' => 'Industry analysis, market positioning, and commercial trend guidance.',
            'price' => 59.00,
            'sort_order' => 4,
            'status' => 'published',
        ]);

        Publication::create([
            'season_id' => $ss26->id,
            'title' => 'Colour Directions SS26',
            'description' => 'Spring/Summer colour forecasting — fresh palettes for the season ahead.',
            'price' => 49.00,
            'sort_order' => 1,
            'status' => 'published',
        ]);

        Publication::create([
            'season_id' => $ss26->id,
            'title' => 'Lightweight Yarns SS26',
            'description' => 'Technical and natural fibre developments for warm-weather knitwear.',
            'price' => 49.00,
            'sort_order' => 2,
            'status' => 'published',
        ]);

        Publication::create([
            'season_id' => $ss26->id,
            'title' => 'Resort & Cruise SS26',
            'description' => 'Trend directions for resort and cruise collections.',
            'price' => 39.00,
            'sort_order' => 3,
            'status' => 'published',
            'is_digital_only' => true,
        ]);

        DiscountCode::create([
            'code' => 'WELCOME20',
            'type' => 'percentage',
            'value' => 20,
            'active' => true,
        ]);

        DiscountCode::create([
            'code' => 'FIVER',
            'type' => 'fixed',
            'value' => 5.00,
            'active' => true,
        ]);
    }
}
