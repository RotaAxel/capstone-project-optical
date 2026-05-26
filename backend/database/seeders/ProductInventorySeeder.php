<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductInventorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $cats = ProductCategory::orderBy('id')->pluck('id', 'name');
        $cFrames = $cats['Eyeglass Frames']    ?? null;
        $cSV     = $cats['Single Vision Lens'] ?? null;
        $cProg   = $cats['Progressive Lens']   ?? null;
        $cCL     = $cats['Contact Lens']       ?? null;
        $cSun    = $cats['Sunglasses']         ?? null;

        $sups = Supplier::orderBy('id')->pluck('id')->toArray();
        $sup1 = $sups[0] ?? null;
        $sup2 = $sups[1] ?? null;

        $products = [];

        // ── LENSES (200 total) ─────────────────────────────────────────────────

        // Single Vision — 80 items
        $svBrands    = ['Essilor','Hoya','Zeiss','LensWorld','Nikon','Rodenstock','Shamir','Seiko','Kodak','Indo'];
        $svIndices   = ['1.50','1.56','1.60','1.67','1.74'];
        $svCoatings  = ['Clear','Anti-Radiation','Blue Cut','Photochromic','UV400','Transitions','HardCoat','Anti-Fog','Mirror','DriveWear'];
        $svColors    = ['Clear','Tinted Gray','Tinted Brown','Rose','Yellow','Blue'];

        for ($i = 1; $i <= 80; $i++) {
            $brand   = $svBrands[($i - 1) % count($svBrands)];
            $index   = $svIndices[($i - 1) % count($svIndices)];
            $coating = $svCoatings[($i - 1) % count($svCoatings)];
            $color   = $svColors[($i - 1) % count($svColors)];
            $cost    = round((150 + ($i * 18)) / 10) * 10;
            $sell    = round($cost * (2.2 + ($i % 5) * 0.1), -1);

            $products[] = [
                'sku'              => 'SV-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name'             => "Single Vision {$index} {$coating} — {$brand}",
                'category_id'      => $cSV,
                'supplier_id'      => $sup2,
                'brand'            => $brand,
                'model'            => "SV{$index}-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'color'            => $color,
                'size'             => null,
                'description'      => "Single vision lens index {$index} with {$coating} coating by {$brand}.",
                'cost_price'       => $cost,
                'selling_price'    => $sell,
                'stock_quantity'   => rand(10, 80),
                'reorder_point'    => 10,
                'reorder_quantity' => 30,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        // Progressive — 70 items
        $progBrands  = ['Essilor Varilux','Hoya ID','Zeiss Progressive','Rodenstock Multigressiv','Shamir Autograph','Seiko Supercede','Nikon Presio','Kodak Unique','Indo Digressive','BBGr Newvision'];
        $progTypes   = ['Freeform','Standard','Short Corridor','Hard Design','Soft Design','Digital','Premium','Economy','Executive','Sport'];

        for ($i = 1; $i <= 70; $i++) {
            $brand = $progBrands[($i - 1) % count($progBrands)];
            $type  = $progTypes[($i - 1) % count($progTypes)];
            $idx   = ['1.50','1.56','1.60','1.67','1.74'][($i - 1) % 5];
            $cost  = round((800 + ($i * 28)) / 10) * 10;
            $sell  = round($cost * (2.5 + ($i % 4) * 0.15), -2);

            $products[] = [
                'sku'              => 'PG-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name'             => "Progressive {$type} {$idx} — {$brand}",
                'category_id'      => $cProg,
                'supplier_id'      => $sup2,
                'brand'            => $brand,
                'model'            => "PG{$idx}-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'color'            => 'Clear',
                'size'             => null,
                'description'      => "{$type} progressive lens, index {$idx}, by {$brand}.",
                'cost_price'       => $cost,
                'selling_price'    => $sell,
                'stock_quantity'   => rand(5, 40),
                'reorder_point'    => 5,
                'reorder_quantity' => 15,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        // Contact Lenses — 50 items
        $clBrands    = ['Acuvue','FreshLook','Bausch & Lomb','CooperVision','Air Optix','Biofinity','Dailies','MyDay','Clariti','SofLens'];
        $clTypes     = ['Daily Disposable','Monthly','Bi-Weekly','Yearly','Toric','Multifocal','Colored','Extended Wear','Silicone Hydrogel','Aspheric'];
        $clPowers    = ['-1.00','-1.50','-2.00','-2.50','-3.00','-3.50','-4.00','-4.50','-5.00','-6.00'];

        for ($i = 1; $i <= 50; $i++) {
            $brand  = $clBrands[($i - 1) % count($clBrands)];
            $type   = $clTypes[($i - 1) % count($clTypes)];
            $power  = $clPowers[($i - 1) % count($clPowers)];
            $pack   = ($i % 3 === 0) ? '30pk' : (($i % 3 === 1) ? '6pk' : '2pk');
            $cost   = round((200 + ($i * 15)) / 10) * 10;
            $sell   = round($cost * (2.0 + ($i % 5) * 0.1), -1);

            $products[] = [
                'sku'              => 'CL-' . str_pad($i + 2, 3, '0', STR_PAD_LEFT),
                'name'             => "{$brand} {$type} {$power} {$pack}",
                'category_id'      => $cCL,
                'supplier_id'      => $sup2,
                'brand'            => $brand,
                'model'            => strtoupper(substr($type, 0, 4)) . str_pad($i, 3, '0', STR_PAD_LEFT),
                'color'            => ($i % 4 === 0) ? 'Hazel' : (($i % 4 === 1) ? 'Blue' : (($i % 4 === 2) ? 'Green' : 'Clear')),
                'size'             => $power,
                'description'      => "{$type} contact lens {$power} by {$brand}, pack of {$pack}.",
                'cost_price'       => $cost,
                'selling_price'    => $sell,
                'stock_quantity'   => rand(5, 50),
                'reorder_point'    => 8,
                'reorder_quantity' => 20,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        // ── FRAMES (300 total) ─────────────────────────────────────────────────

        // Eyeglass Frames — 200 items
        $frameBrands = ['Ray-Ban','Oakley','Gucci','Prada','Tom Ford','Persol','Warby Parker','Silhouette','Marchon','Luxottica',
                        'OptiStyle','TitanVision','AceFrame','ProVision','EyeMax','VisionPlus','FrameCraft','ClearView','PureOptics','NuFrame'];
        $frameMats   = ['Metal','Acetate','Titanium','TR90','Stainless Steel','Alloy','Carbon Fiber','Nylon','Ultem','Mixed'];
        $frameStyles = ['Full Rim','Half Rim','Rimless','Semi-Rimless'];
        $frameShapes = ['Rectangle','Square','Round','Oval','Cat-Eye','Aviator','Wayfarer','Geometric','Browline','Butterfly'];
        $frameColors = ['Black','Silver','Gold','Gunmetal','Rose Gold','Bronze','Tortoise','Brown','Blue','Navy',
                        'Red','Clear','White','Champagne','Matte Black','Shiny Black','Dark Brown','Beige','Teal','Purple'];
        $frameSizes  = ['Small (125mm)','Medium (135mm)','Large (140mm)','Extra Small (120mm)','Extra Large (145mm)'];
        $frameGender = ['Unisex','Men','Women','Kids'];

        for ($i = 1; $i <= 200; $i++) {
            $brand  = $frameBrands[($i - 1) % count($frameBrands)];
            $mat    = $frameMats[($i - 1) % count($frameMats)];
            $style  = $frameStyles[($i - 1) % count($frameStyles)];
            $shape  = $frameShapes[($i - 1) % count($frameShapes)];
            $color  = $frameColors[($i - 1) % count($frameColors)];
            $size   = $frameSizes[($i - 1) % count($frameSizes)];
            $gender = $frameGender[($i - 1) % count($frameGender)];
            $cost   = round((300 + ($i * 12)) / 10) * 10;
            $sell   = round($cost * (2.3 + ($i % 6) * 0.1), -2);

            $products[] = [
                'sku'              => 'EF-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name'             => "{$brand} {$shape} {$style} — {$color} {$mat}",
                'category_id'      => $cFrames,
                'supplier_id'      => $sup1,
                'brand'            => $brand,
                'model'            => strtoupper(substr($brand, 0, 3)) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'color'            => $color,
                'size'             => $size,
                'description'      => "{$gender} {$shape} {$style} eyeglass frame in {$color} {$mat} by {$brand}.",
                'cost_price'       => $cost,
                'selling_price'    => $sell,
                'stock_quantity'   => rand(5, 35),
                'reorder_point'    => 5,
                'reorder_quantity' => 15,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        // Sunglasses — 100 items
        $sgBrands  = ['Ray-Ban','Oakley','Maui Jim','Costa Del Mar','Polaroid','Carrera','Arnette','Spy Optic','Serengeti','Smith Optics'];
        $sgStyles  = ['Aviator','Wayfarer','Sport Wrap','Shield','Butterfly','Rectangular','Round','Oversized','Semi-Rimless','Browline'];
        $sgLens    = ['Polarized','UV400','Mirrored','Gradient','Photochromic','Anti-Glare','Blue Light','Smoke','Green Mirror','Brown Gradient'];
        $sgColors  = ['Black','Tortoise','Brown','Clear','Blue','Red','White','Gold','Gunmetal','Silver'];

        for ($i = 1; $i <= 100; $i++) {
            $brand = $sgBrands[($i - 1) % count($sgBrands)];
            $style = $sgStyles[($i - 1) % count($sgStyles)];
            $lens  = $sgLens[($i - 1) % count($sgLens)];
            $color = $sgColors[($i - 1) % count($sgColors)];
            $cost  = round((400 + ($i * 20)) / 10) * 10;
            $sell  = round($cost * (2.4 + ($i % 5) * 0.1), -2);

            $products[] = [
                'sku'              => 'SG-' . str_pad($i + 2, 3, '0', STR_PAD_LEFT),
                'name'             => "{$brand} {$style} {$lens} — {$color}",
                'category_id'      => $cSun,
                'supplier_id'      => $sup1,
                'brand'            => $brand,
                'model'            => strtoupper(substr($brand, 0, 3)) . 'SG-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'color'            => $color,
                'size'             => null,
                'description'      => "{$style} sunglasses with {$lens} lens in {$color} by {$brand}.",
                'cost_price'       => $cost,
                'selling_price'    => $sell,
                'stock_quantity'   => rand(3, 25),
                'reorder_point'    => 4,
                'reorder_quantity' => 10,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        // Insert in chunks of 100 to avoid memory issues
        foreach (array_chunk($products, 100) as $chunk) {
            Product::insertOrIgnore($chunk);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $lenses = 80 + 70 + 50;
        $frames = 200 + 100;
        $this->command->info("✓ Inserted {$lenses} lenses and {$frames} frames ({$lenses} + {$frames} = " . ($lenses + $frames) . " total products).");
    }
}
