<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Product;
use App\Models\RfidCard;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createRolesAndPermissions();
        $this->createAdmin();
        $this->createKasir();
        $this->createDevice();
        $this->createProducts();
        $this->createRfidCards();
    }

    private function createRolesAndPermissions(): void
    {
        $permissions = [
            'manage devices',
            'manage products',
            'manage rfid cards',
            'view transactions',
            'view reports',
            'manage users',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        $kasirRole = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
        $kasirRole->givePermissionTo([
            'manage products',
            'view transactions',
        ]);
    }

    private function createAdmin(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@skanilan.local'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');
    }

    private function createKasir(): void
    {
        $kasir = User::firstOrCreate(
            ['email' => 'kasir@skanilan.local'],
            [
                'name' => 'Kasir Demo',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );
        $kasir->assignRole('kasir');
    }

    private function createDevice(): void
    {
        $kasir = User::where('email', 'kasir@skanilan.local')->first();

        Device::firstOrCreate(
            ['device_id' => 'ESP8266_DEMO_001'],
            [
                'name' => 'Console Kasir 1',
                'status' => 'active',
                'api_token' => 'dev_'.bin2hex(random_bytes(32)),
                'firmware_version' => '1.0.0',
                'kasir_id' => $kasir?->id,
            ]
        );
    }

    private function createProducts(): void
    {
        $kasir = User::where('email', 'kasir@skanilan.local')->first();
        if (! $kasir) {
            return;
        }

        $products = [
            ['name' => 'Nasi Goreng', 'price' => 15000, 'stock' => 50, 'category' => 'Makanan'],
            ['name' => 'Mie Goreng', 'price' => 13000, 'stock' => 40, 'category' => 'Makanan'],
            ['name' => 'Es Teh Manis', 'price' => 5000, 'stock' => 100, 'category' => 'Minuman'],
            ['name' => 'Es Jeruk', 'price' => 7000, 'stock' => 80, 'category' => 'Minuman'],
            ['name' => 'Ayam Goreng', 'price' => 18000, 'stock' => 30, 'category' => 'Makanan'],
            ['name' => 'Tempe Goreng', 'price' => 3000, 'stock' => 100, 'category' => 'Lauk'],
            ['name' => 'Tahu Goreng', 'price' => 2000, 'stock' => 100, 'category' => 'Lauk'],
            ['name' => 'Air Mineral', 'price' => 3000, 'stock' => 200, 'category' => 'Minuman'],
        ];

        foreach ($products as $p) {
            Product::firstOrCreate(
                ['name' => $p['name'], 'kasir_id' => $kasir->id],
                array_merge($p, ['kasir_id' => $kasir->id, 'is_active' => true])
            );
        }
    }

    private function createRfidCards(): void
    {
        $admin = User::where('email', 'admin@skanilan.local')->first();
        if (! $admin) {
            return;
        }

        $cards = [
            ['uid' => 'A1B2C3D4', 'user_name' => 'Budi Santoso', 'balance' => 50000],
            ['uid' => 'E5F6G7H8', 'user_name' => 'Siti Rahayu', 'balance' => 75000],
            ['uid' => 'I9J0K1L2', 'user_name' => 'Ahmad Fauzi', 'balance' => 30000],
            ['uid' => 'M3N4O5P6', 'user_name' => 'Dewi Lestari', 'balance' => 100000],
        ];

        foreach ($cards as $c) {
            RfidCard::firstOrCreate(
                ['uid' => $c['uid']],
                array_merge($c, ['is_active' => true, 'registered_by' => $admin->id])
            );
        }
    }
}
