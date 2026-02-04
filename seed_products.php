<?php
require_once 'config/database.php';

echo "Seeding sample watches...\n";

try {
    $db = get_db_connection();

    // Ensure Admin exists (ID 1)
    $stmt = $db->query("SELECT id FROM users WHERE id = 1");
    if (!$stmt->fetch()) {
        // Create fallback seller if not exists
        $db->exec("INSERT INTO users (id, name, email, password, role) VALUES (1, 'Admin Seller', 'admin@timepiece.com', 'hash', 'seller')");
    }

    $products = [
        [
            'seller_id' => 1,
            'category_id' => 1, // Divers
            'name' => 'Rolex Submariner Date',
            'description' => 'The archetypal diving watch. Oystersteel case with a Cerachrom bezel insert in black ceramic.',
            'price' => 10250.00,
            'tier' => 'luxury',
            'image_url' => 'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?auto=format&fit=crop&w=400&q=80'
        ],
        [
            'seller_id' => 1,
            'category_id' => 1, // Divers
            'name' => 'Seiko Prospex "Turtle"',
            'description' => 'A cult classic. Reliable automatic diver with 200m water resistance. Great value for everyday wear.',
            'price' => 475.00,
            'tier' => 'budget',
            'image_url' => 'https://images.unsplash.com/photo-1623998021446-4bec0c5d83f1?auto=format&fit=crop&w=400&q=80'
        ],
        [
            'seller_id' => 1,
            'category_id' => 2, // Dress
            'name' => 'Patek Philippe Calatrava',
            'description' => 'The essence of the round wristwatch and one of the finest symbols of the Patek Philippe style.',
            'price' => 32000.00,
            'tier' => 'luxury',
            'image_url' => 'https://images.unsplash.com/photo-1509048191080-d2984bad6ae5?auto=format&fit=crop&w=400&q=80'
        ],
        [
            'seller_id' => 1,
            'category_id' => 2, // Dress
            'name' => 'Orient Bambino V4',
            'description' => 'Minimalist design with a domed crystal. The best entry-level dress watch on the market.',
            'price' => 150.00,
            'tier' => 'budget',
            'image_url' => 'https://images.unsplash.com/photo-1616353329119-03e33b660a92?auto=format&fit=crop&w=400&q=80'
        ],
        [
            'seller_id' => 1,
            'category_id' => 3, // Sports
            'name' => 'Audemars Piguet Royal Oak',
            'description' => 'The first luxury sports watch in stainless steel. Iconic octagonal bezel and tapisserie dial.',
            'price' => 55000.00,
            'tier' => 'luxury',
            'image_url' => 'https://images.unsplash.com/photo-1619134778706-c433158fb79f?auto=format&fit=crop&w=400&q=80'
        ],
        [
            'seller_id' => 1,
            'category_id' => 3, // Sports
            'name' => 'Casio G-Shock GA2100',
            'description' => 'The "CasiOak". Carbon Core Guard structure. Unbeatable durability and style for the price.',
            'price' => 99.00,
            'tier' => 'budget',
            'image_url' => 'https://images.unsplash.com/photo-1596558299292-06972e3895e6?auto=format&fit=crop&w=400&q=80'
        ],
        [
            'seller_id' => 1,
            'category_id' => 4, // Smart
            'name' => 'TAG Heuer Connected',
            'description' => 'Luxury meets technology. All the savoir-faire of TAG Heuer in an advanced smartwatch.',
            'price' => 2100.00,
            'tier' => 'luxury',
            'image_url' => 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?auto=format&fit=crop&w=400&q=80'
        ],
        [
            'seller_id' => 1,
            'category_id' => 3, // Sports
            'name' => 'Omega Speedmaster Moonwatch',
            'description' => 'The watch that went to the moon. A manual-winding chronograph with a legendary history.',
            'price' => 7500.00,
            'tier' => 'luxury',
            'image_url' => 'https://images.unsplash.com/photo-1622396387869-d4c39f074d28?auto=format&fit=crop&w=400&q=80'
        ]
    ];

    $stmt = $db->prepare("INSERT INTO products (seller_id, category_id, name, description, price, tier, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($products as $p) {
        $stmt->execute([
            $p['seller_id'],
            $p['category_id'],
            $p['name'],
            $p['description'],
            $p['price'],
            $p['tier'],
            $p['image_url']
        ]);
        echo "Added: {$p['name']}\n";
    }

    echo "\n✔ Successfully added " . count($products) . " watches!\n";
    echo "Refresh your browser at http://localhost:8000 to see them.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
