<?php
// Mock Database Implementation for Offline/No-DB Fallback

class MockMySQLi {
    public $connect_error = null;
    public $error = '';
    public $errno = 0;
    public $insert_id = 1001;

    public function __construct() {
        // Mock connection is always successful
    }

    public function query($sql) {
        return new MockMySQLiResult($sql);
    }

    public function prepare($sql) {
        return new MockMySQLiStmt($sql);
    }

    public function real_escape_string($str) {
        return addslashes($str);
    }

    public function begin_transaction() {
        return true;
    }

    public function commit() {
        return true;
    }

    public function rollback() {
        return true;
    }

    public function close() {
        return true;
    }
}

class MockMySQLiStmt {
    private $sql;
    private $params = [];
    private $result;
    public $error = '';
    public $errno = 0;

    public function __construct($sql) {
        $this->sql = $sql;
    }

    public function bind_param($types, &...$vars) {
        $this->params = $vars;
        return true;
    }

    public function execute() {
        $this->result = new MockMySQLiResult($this->sql, $this->params);
        return true;
    }

    public function get_result() {
        return $this->result;
    }

    public function close() {
        return true;
    }
}

class MockMySQLiResult {
    private $data = [];
    private $index = 0;
    public $num_rows = 0;

    public function __construct($sql, $params = []) {
        $this->data = $this->generateMockData($sql, $params);
        $this->num_rows = count($this->data);
    }

    private function generateMockData($sql, $params) {
        $sql = strtolower($sql);

        // 1. Users / Login check
        if (strpos($sql, 'from users') !== false) {
            $mockUsers = [
                [
                    'user_id' => 1,
                    'username' => 'admin',
                    'password' => 'admin', // supports both plain and hashed check
                    'role' => 'admin',
                    'full_name' => 'Administrator Toko'
                ],
                [
                    'user_id' => 2,
                    'username' => 'kasir',
                    'password' => 'kasir',
                    'role' => 'cashier',
                    'full_name' => 'Kasir Bellen Beans'
                ]
            ];

            // If a specific username is filtered
            if (strpos($sql, 'where username = ?') !== false && !empty($params)) {
                $usernameParam = $params[0];
                foreach ($mockUsers as $user) {
                    if ($user['username'] === $usernameParam) {
                        return [$user];
                    }
                }
                return [];
            }
            return $mockUsers;
        }

        // 2. Categories
        if (strpos($sql, 'from categories') !== false) {
            return [
                ['category_id' => 1, 'category_name' => 'Coffee & Espresso'],
                ['category_id' => 2, 'category_name' => 'Non-Coffee Latte'],
                ['category_id' => 3, 'category_name' => 'Bakery & Pastry'],
                ['category_id' => 4, 'category_name' => 'Main Course & Snacks']
            ];
        }

        // 3. Products
        if (strpos($sql, 'from products') !== false) {
            return [
                [
                    'product_id' => 10,
                    'product_name' => 'Kopi Susu Gula Aren',
                    'price' => 18000,
                    'stock' => 45,
                    'category_id' => 1,
                    'category_name' => 'Coffee & Espresso',
                    'image_url' => 'assets/images/default.jpg'
                ],
                [
                    'product_id' => 9,
                    'product_name' => 'Espresso Double Shot',
                    'price' => 15000,
                    'stock' => 99,
                    'category_id' => 1,
                    'category_name' => 'Coffee & Espresso',
                    'image_url' => 'assets/images/default.jpg'
                ],
                [
                    'product_id' => 8,
                    'product_name' => 'Matcha Latte',
                    'price' => 20000,
                    'stock' => 30,
                    'category_id' => 2,
                    'category_name' => 'Non-Coffee Latte',
                    'image_url' => 'assets/images/default.jpg'
                ],
                [
                    'product_id' => 7,
                    'product_name' => 'Red Velvet Latte',
                    'price' => 20000,
                    'stock' => 25,
                    'category_id' => 2,
                    'category_name' => 'Non-Coffee Latte',
                    'image_url' => 'assets/images/default.jpg'
                ],
                [
                    'product_id' => 6,
                    'product_name' => 'Croissant Almond',
                    'price' => 25000,
                    'stock' => 12,
                    'category_id' => 3,
                    'category_name' => 'Bakery & Pastry',
                    'image_url' => 'assets/images/default.jpg'
                ],
                [
                    'product_id' => 5,
                    'product_name' => 'Chocolate Muffin',
                    'price' => 18000,
                    'stock' => 15,
                    'category_id' => 3,
                    'category_name' => 'Bakery & Pastry',
                    'image_url' => 'assets/images/default.jpg'
                ],
                [
                    'product_id' => 4,
                    'product_name' => 'French Fries',
                    'price' => 17000,
                    'stock' => 20,
                    'category_id' => 4,
                    'category_name' => 'Main Course & Snacks',
                    'image_url' => 'assets/images/default.jpg'
                ],
                [
                    'product_id' => 3,
                    'product_name' => 'Nasi Goreng Spesial',
                    'price' => 28000,
                    'stock' => 15,
                    'category_id' => 4,
                    'category_name' => 'Main Course & Snacks',
                    'image_url' => 'assets/images/default.jpg'
                ]
            ];
        }

        // 4. Customers
        if (strpos($sql, 'from customers') !== false) {
            return [
                ['customer_id' => 1, 'customer_name' => 'Budi Santoso', 'phone_number' => '081234567890', 'loyalty_points' => 120],
                ['customer_id' => 2, 'customer_name' => 'Siti Aminah', 'phone_number' => '089876543210', 'loyalty_points' => 45],
                ['customer_id' => 3, 'customer_name' => 'Rian Wijaya', 'phone_number' => '085712345678', 'loyalty_points' => 300]
            ];
        }

        // 5. Customer Insights (Dashboard Wawasan Pengunjung)
        if (strpos($sql, 'count(') !== false || strpos($sql, 'sum(') !== false) {
            // Return mock totals for insights metrics
            return [
                [
                    'total_sales' => 1485000,
                    'total_transactions' => 64,
                    'total_visitors' => 95,
                    'dine_in_count' => 58,
                    'take_away_count' => 37,
                    'peak_hour' => '14:00 - 16:00'
                ]
            ];
        }

        // 6. Transactions list (History & Reports)
        if (strpos($sql, 'from transactions') !== false) {
            return [
                [
                    'transaction_id' => 101,
                    'invoice_code' => 'TRX-20260525-001',
                    'total_amount' => 54000,
                    'payment_method' => 'cash',
                    'payment_status' => 'paid',
                    'customer_name' => 'Budi Santoso',
                    'cash_received' => 60000,
                    'change_amount' => 6000,
                    'order_type' => 'dine-in',
                    'discount_amount' => 0,
                    'points_used' => 0,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
                    'full_name' => 'Kasir Bellen Beans'
                ],
                [
                    'transaction_id' => 102,
                    'invoice_code' => 'TRX-20260525-002',
                    'total_amount' => 36000,
                    'payment_method' => 'qris',
                    'payment_status' => 'paid',
                    'customer_name' => 'Siti Aminah',
                    'cash_received' => 36000,
                    'change_amount' => 0,
                    'order_type' => 'take-away',
                    'discount_amount' => 0,
                    'points_used' => 0,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'full_name' => 'Kasir Bellen Beans'
                ],
                [
                    'transaction_id' => 103,
                    'invoice_code' => 'TRX-20260525-003',
                    'total_amount' => 75000,
                    'payment_method' => 'cash',
                    'payment_status' => 'paid',
                    'customer_name' => 'Rian Wijaya',
                    'cash_received' => 100000,
                    'change_amount' => 25000,
                    'order_type' => 'dine-in',
                    'discount_amount' => 5000,
                    'points_used' => 50,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
                    'full_name' => 'Kasir Bellen Beans'
                ]
            ];
        }

        // 7. Expenses
        if (strpos($sql, 'from expenses') !== false) {
            return [
                ['expense_id' => 1, 'title' => 'Beli Es Batu Kristal', 'amount' => 15000, 'created_at' => date('Y-m-d')],
                ['expense_id' => 2, 'title' => 'Isi Ulang Gas LPG 3Kg', 'amount' => 22000, 'created_at' => date('Y-m-d')]
            ];
        }

        return [];
    }

    public function fetch_assoc() {
        if ($this->index < count($this->data)) {
            return $this->data[$this->index++];
        }
        return null;
    }

    public function fetch_row() {
        if ($this->index < count($this->data)) {
            return array_values($this->data[$this->index++]);
        }
        return null;
    }

    public function fetch_all($mode = null) {
        return $this->data;
    }

    public function fetch_fields() {
        if (empty($this->data)) {
            return [];
        }
        $fields = [];
        foreach (array_keys($this->data[0]) as $key) {
            $field = new stdClass();
            $field->name = $key;
            $fields[] = $field;
        }
        return $fields;
    }
}
