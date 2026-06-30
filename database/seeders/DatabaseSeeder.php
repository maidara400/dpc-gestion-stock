<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@padel.club',
            'password' => Hash::make('Admin@2024!'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Caissier Equipe',
            'email' => 'caissier@padel.club',
            'password' => Hash::make('Caissier@2024!'),
            'role' => 'caissier',
        ]);

        // Fournisseurs
        $suppliers = [];
        foreach (['SOBOA', 'KIRENE', 'FANTA', 'SPRITE', 'IBS', 'BULLPADEL', 'ILLY', 'OFFRUTTI', '3 X ENERGY', 'PRESSEA'] as $name) {
            $suppliers[$name] = Supplier::create(['name' => $name, 'active' => true]);
        }

        // Catégories
        $cats = [];
        foreach ([
            ['name' => 'Boisson Bouteille', 'color' => '#3b82f6'],
            ['name' => 'Boisson Canette',   'color' => '#06b6d4'],
            ['name' => 'Eau',               'color' => '#0ea5e9'],
            ['name' => 'Balle',             'color' => '#10b981'],
            ['name' => 'Café',              'color' => '#92400e'],
            ['name' => 'Snacking',          'color' => '#f59e0b'],
        ] as $cat) {
            $cats[$cat['name']] = Category::create($cat);
        }

        // Produits (inventaire dépôt du 29/06/2026)
        $products = [
            // BOISSON BOUTEILLE
            ['name' => 'La Gazelle Ananas 30cl',         'sku' => 'BB-001', 'category' => 'Boisson Bouteille', 'supplier' => 'SOBOA',      'stock_depot' => 60,   'taille' => '30 CL',  'saveur' => 'Ananas'],
            ['name' => 'La Gazelle Pomme 30cl',          'sku' => 'BB-002', 'category' => 'Boisson Bouteille', 'supplier' => 'SOBOA',      'stock_depot' => 36,   'taille' => '30 CL',  'saveur' => 'Pomme'],
            ['name' => 'B-Sport Blue Flavor 350ml',      'sku' => 'BB-003', 'category' => 'Boisson Bouteille', 'supplier' => null,         'stock_depot' => 30,   'taille' => '350 ML', 'saveur' => 'Blue Flavor'],
            ['name' => 'B-Sport Fruits Rouges 350ml',    'sku' => 'BB-004', 'category' => 'Boisson Bouteille', 'supplier' => null,         'stock_depot' => 15,   'taille' => '350 ML', 'saveur' => 'Fruits Rouges'],
            ['name' => 'Sprite Citron Vert 30cl',        'sku' => 'BB-005', 'category' => 'Boisson Bouteille', 'supplier' => 'SPRITE',     'stock_depot' => 36,   'taille' => '30 CL',  'saveur' => 'Citron Vert'],
            ['name' => 'Schweppes Agrumes 300ml',        'sku' => 'BB-006', 'category' => 'Boisson Bouteille', 'supplier' => 'IBS',        'stock_depot' => 24,   'taille' => '300 ML', 'saveur' => 'Agrumes'],
            ['name' => 'Fanta Orange 30cl',              'sku' => 'BB-007', 'category' => 'Boisson Bouteille', 'supplier' => 'FANTA',      'stock_depot' => 84,   'taille' => '30 CL',  'saveur' => 'Orange'],
            ['name' => 'Coca-Cola Original 30cl',        'sku' => 'BB-008', 'category' => 'Boisson Bouteille', 'supplier' => 'IBS',  'stock_depot' => 24,   'taille' => '30 CL',  'saveur' => 'Original'],
            ['name' => 'Fanta Fruits Rouges 30cl',       'sku' => 'BB-009', 'category' => 'Boisson Bouteille', 'supplier' => 'FANTA',      'stock_depot' => 60,   'taille' => '30 CL',  'saveur' => 'Fruits Rouges'],
            ['name' => 'Fanta Ananas 30cl',              'sku' => 'BB-010', 'category' => 'Boisson Bouteille', 'supplier' => 'FANTA',      'stock_depot' => 60,   'taille' => '30 CL',  'saveur' => 'Ananas'],
            ['name' => 'B-Sport Citron 350ml',           'sku' => 'BB-011', 'category' => 'Boisson Bouteille', 'supplier' => null,         'stock_depot' => 60,   'taille' => '350 ML', 'saveur' => 'Citron'],
            ['name' => 'Sprite Citron Vert 30cl (2)',    'sku' => 'BB-012', 'category' => 'Boisson Bouteille', 'supplier' => 'SPRITE',     'stock_depot' => 72,   'taille' => '30 CL',  'saveur' => 'Citron Vert'],
            ['name' => 'Fanta Orange 30cl (2)',          'sku' => 'BB-013', 'category' => 'Boisson Bouteille', 'supplier' => 'FANTA',      'stock_depot' => 72,   'taille' => '30 CL',  'saveur' => 'Orange'],
            // BOISSON CANETTE
            ['name' => 'Pressea Tropical 250ml',         'sku' => 'BC-001', 'category' => 'Boisson Canette',  'supplier' => null,         'stock_depot' => 144,  'taille' => '250 ML', 'saveur' => 'Tropical'],
            ['name' => 'Schweppes Citron 250ml',         'sku' => 'BC-002', 'category' => 'Boisson Canette',  'supplier' => 'IBS',        'stock_depot' => 24,   'taille' => '250 ML', 'saveur' => 'Citron'],
            ['name' => 'Schweppes Agrumes 250ml',        'sku' => 'BC-003', 'category' => 'Boisson Canette',  'supplier' => 'IBS',        'stock_depot' => 24,   'taille' => '250 ML', 'saveur' => 'Agrumes'],
            ['name' => 'Nova Fruits Rouges 250ml',       'sku' => 'BC-004', 'category' => 'Boisson Canette',  'supplier' => null,         'stock_depot' => 96,   'taille' => '250 ML', 'saveur' => 'Fruits Rouges'],
            ['name' => 'Fanta Fruits Rouges 330ml',      'sku' => 'BC-005', 'category' => 'Boisson Canette',  'supplier' => 'FANTA',      'stock_depot' => 96,   'taille' => '330 ML', 'saveur' => 'Fruits Rouges'],
            ['name' => 'Sprite Citron Vert 330ml',       'sku' => 'BC-006', 'category' => 'Boisson Canette',  'supplier' => 'SPRITE',     'stock_depot' => 96,   'taille' => '330 ML', 'saveur' => 'Citron Vert'],
            ['name' => 'Fanta Ananas 330ml',             'sku' => 'BC-007', 'category' => 'Boisson Canette',  'supplier' => 'FANTA',      'stock_depot' => 72,   'taille' => '330 ML', 'saveur' => 'Ananas'],
            ['name' => 'Miranda Fruits Rouges 250ml',    'sku' => 'BC-008', 'category' => 'Boisson Canette',  'supplier' => null,         'stock_depot' => 24,   'taille' => '250 ML', 'saveur' => 'Fruits Rouges'],
            ['name' => 'Coca-Cola Original 330ml',       'sku' => 'BC-009', 'category' => 'Boisson Canette',  'supplier' => 'IBS',  'stock_depot' => 168,  'taille' => '330 ML', 'saveur' => 'Original'],
            ['name' => 'Miranda Orange 250ml',           'sku' => 'BC-010', 'category' => 'Boisson Canette',  'supplier' => null,         'stock_depot' => 48,   'taille' => '250 ML', 'saveur' => 'Orange'],
            ['name' => 'Seven Up Citron Vert 250ml',     'sku' => 'BC-011', 'category' => 'Boisson Canette',  'supplier' => null,         'stock_depot' => 24,   'taille' => '250 ML', 'saveur' => 'Citron Vert'],
            ['name' => '3X Energy 25cl',                 'sku' => 'BC-012', 'category' => 'Boisson Canette',  'supplier' => '3 X ENERGY', 'stock_depot' => 336,  'taille' => '25 CL',  'saveur' => 'Unique'],
            ['name' => 'Pressea Orange 250ml',           'sku' => 'BC-013', 'category' => 'Boisson Canette',  'supplier' => 'PRESSEA',    'stock_depot' => 96,   'taille' => '250 ML', 'saveur' => 'Orange'],
            ['name' => 'Pressea Ananas Coco 250ml',      'sku' => 'BC-014', 'category' => 'Boisson Canette',  'supplier' => 'PRESSEA',    'stock_depot' => 96,   'taille' => '250 ML', 'saveur' => 'Ananas Coco'],
            ['name' => 'Pressea Goyave 250ml',           'sku' => 'BC-015', 'category' => 'Boisson Canette',  'supplier' => 'PRESSEA',    'stock_depot' => 24,   'taille' => '250 ML', 'saveur' => 'Goyave'],
            // EAU
            ['name' => 'Kirene 500ml',                   'sku' => 'EAU-001','category' => 'Eau',              'supplier' => 'KIRENE',     'stock_depot' => 2316, 'taille' => '500 ML', 'saveur' => 'Unique'],
            // BALLE
            ['name' => 'Balles Padel Next Pro (tube 3)', 'sku' => 'BAL-001','category' => 'Balle',            'supplier' => 'BULLPADEL',  'stock_depot' => 456,  'taille' => 'Tube 3', 'saveur' => 'Unique'],
            // CAFÉ
            ['name' => 'Illy Café Classico',             'sku' => 'CAF-001','category' => 'Café',             'supplier' => 'ILLY',       'stock_depot' => 60,   'taille' => null,     'saveur' => 'Classico'],
            // SNACKING
            ['name' => 'Ofrutti Mangues Séchées Pimentées 100g', 'sku' => 'SNA-001', 'category' => 'Snacking', 'supplier' => 'OFFRUTTI', 'stock_depot' => 24,  'taille' => '100 G',  'saveur' => 'Mangues Séchées Pimentées'],
            ['name' => 'Ofrutti Bananes Séchées 100g',   'sku' => 'SNA-002','category' => 'Snacking',         'supplier' => 'OFFRUTTI',   'stock_depot' => 24,   'taille' => '100 G',  'saveur' => 'Bananes Séchées'],
            ['name' => 'Ofrutti Ananas Séchés 100g',     'sku' => 'SNA-003','category' => 'Snacking',         'supplier' => 'OFFRUTTI',   'stock_depot' => 24,   'taille' => '100 G',  'saveur' => 'Ananas Séchés'],
        ];

        foreach ($products as $p) {
            Product::create([
                'name'                    => $p['name'],
                'sku'                     => $p['sku'],
                'category_id'             => $cats[$p['category']]->id,
                'supplier_id'             => $p['supplier'] ? $suppliers[$p['supplier']]->id : null,
                'stock_depot'             => $p['stock_depot'],
                'stock_boutique'          => 0,
                'alert_threshold_depot'   => 10,
                'alert_threshold_boutique'=> 3,
                'purchase_price'          => 0,
                'sale_price'              => 0,
                'active'                  => true,
            ]);
        }
    }
}
