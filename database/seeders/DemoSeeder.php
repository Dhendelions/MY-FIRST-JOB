<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetSheet;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetCell;
use App\Models\Friendship;
use App\Models\Notification;

class DemoSeeder extends Seeder
{
    /**
     * Seed realistic general-purpose collaborative workspace demo data.
     * Idempotent: checks for existing demo accounts by email before creating.
     * Uses roles: leader, admin, karyawan.
     */
    public function run(): void
    {
        $this->command->info('📊 Memulai seeding data demo Collaborative Workspace...');

        // ─────────────────────────────────────────────
        // 1. USERS — Demo accounts with general-purpose roles
        // ─────────────────────────────────────────────
        $usersData = [
            [
                'name'       => 'Budi Santoso',
                'email'      => 'budi@dadhenmandiri.id',
                'password'   => 'password123',
                'whatsapp'   => '+6281234567890',
                'role_title' => 'leader',
            ],
            [
                'name'       => 'Siti Rahayu',
                'email'      => 'siti@dadhenmandiri.id',
                'password'   => 'password123',
                'whatsapp'   => '+6285678901234',
                'role_title' => 'admin',
            ],
            [
                'name'       => 'Ahmad Wijaya',
                'email'      => 'ahmad@dadhenmandiri.id',
                'password'   => 'password123',
                'whatsapp'   => '+6287654321098',
                'role_title' => 'admin',
            ],
            [
                'name'       => 'Dewi Lestari',
                'email'      => 'dewi@dadhenmandiri.id',
                'password'   => 'password123',
                'whatsapp'   => null, // Optional phone test
                'role_title' => 'karyawan',
            ],
            [
                'name'       => 'Hendra Pratama',
                'email'      => 'hendra@dadhenmandiri.id',
                'password'   => 'password123',
                'whatsapp'   => '+6289012345678',
                'role_title' => 'leader',
            ],
        ];

        $users = [];
        foreach ($usersData as $userData) {
            $existing = User::where('email', $userData['email'])->first();
            if ($existing) {
                $this->command->warn("  ⏭  User '{$userData['email']}' sudah ada, dilewati.");
                $users[] = $existing;
                continue;
            }

            $users[] = User::create([
                'name'       => $userData['name'],
                'email'      => $userData['email'],
                'password'   => Hash::make($userData['password']),
                'whatsapp'   => $userData['whatsapp'],
                'role_title' => $userData['role_title'],
            ]);
            $this->command->info("  ✅ User '{$userData['name']}' dibuat.");
        }

        [$budi, $siti, $ahmad, $dewi, $hendra] = $users;

        // ─────────────────────────────────────────────
        // 2. WORKSPACES — General-purpose collaborative teams
        // ─────────────────────────────────────────────
        $workspacesData = [
            [
                'name'        => 'Operasional & Manajemen Proyek',
                'description' => 'Workspace untuk pelacakan backlog proyek, alokasi sumber daya, dan timeline kerja tim.',
                'category'    => 'Operasional',
                'location'    => 'Jakarta Pusat',
                'pond_count'  => 5,
                'creator'     => $budi,
            ],
            [
                'name'        => 'Perencanaan Finansial & Anggaran',
                'description' => 'Pengelolaan laporan kas masuk, anggaran operasional kuartal, dan tracking pengeluaran tim.',
                'category'    => 'Keuangan',
                'location'    => 'Surabaya',
                'pond_count'  => 3,
                'creator'     => $hendra,
            ],
            [
                'name'        => 'Logistik & Inventaris Barang',
                'description' => 'Monitoring stok perlengkapan, pencatatan vendor supplier, dan jadwal pengadaan material.',
                'category'    => 'Logistik',
                'location'    => 'Bandung',
                'pond_count'  => 4,
                'creator'     => $budi,
            ],
        ];

        $workspaces = [];
        foreach ($workspacesData as $wData) {
            $existing = FisheriesGroup::where('name', $wData['name'])
                ->where('created_by', $wData['creator']->id)
                ->first();

            if ($existing) {
                $this->command->warn("  ⏭  Workspace '{$wData['name']}' sudah ada, dilewati.");
                $workspaces[] = $existing;
                continue;
            }

            $inviteCode = FisheriesGroup::generateInviteCode($wData['category']);

            $workspace = FisheriesGroup::create([
                'name'        => $wData['name'],
                'description' => $wData['description'],
                'category'    => $wData['category'],
                'invite_code' => $inviteCode,
                'location'    => $wData['location'],
                'pond_count'  => $wData['pond_count'],
                'created_by'  => $wData['creator']->id,
            ]);

            // Creator becomes Leader
            GroupMember::firstOrCreate(
                ['group_id' => $workspace->id, 'user_id' => $wData['creator']->id],
                ['role' => 'leader', 'status' => 'Aktif', 'joined_at' => now()->subDays(30)]
            );

            // Initialize standard 5 generic sheets
            $workspace->initializeStandardSheets();

            $workspaces[] = $workspace;
            $this->command->info("  ✅ Workspace '{$wData['name']}' dibuat (Kode: {$inviteCode}).");
        }

        [$wsProyek, $wsFinansial, $wsLogistik] = $workspaces;

        // ─────────────────────────────────────────────
        // 3. WORKSPACE MEMBERS — Roles: leader, admin, karyawan
        // ─────────────────────────────────────────────
        $memberships = [
            ['group' => $wsProyek, 'user' => $siti,  'role' => 'admin',    'days_ago' => 25],
            ['group' => $wsProyek, 'user' => $ahmad, 'role' => 'admin',    'days_ago' => 20],
            ['group' => $wsProyek, 'user' => $dewi,  'role' => 'karyawan', 'days_ago' => 15],

            ['group' => $wsFinansial, 'user' => $siti,  'role' => 'admin',    'days_ago' => 18],
            ['group' => $wsFinansial, 'user' => $ahmad, 'role' => 'karyawan', 'days_ago' => 12],

            ['group' => $wsLogistik, 'user' => $hendra, 'role' => 'karyawan', 'days_ago' => 10],
            ['group' => $wsLogistik, 'user' => $dewi,   'role' => 'karyawan', 'days_ago' => 7],
        ];

        foreach ($memberships as $m) {
            GroupMember::firstOrCreate(
                ['group_id' => $m['group']->id, 'user_id' => $m['user']->id],
                ['role' => $m['role'], 'status' => 'Aktif', 'joined_at' => now()->subDays($m['days_ago'])]
            );
        }
        $this->command->info('  ✅ Keanggotaan peran leader/admin/karyawan ditetapkan.');

        // ─────────────────────────────────────────────
        // 4. SPREADSHEET DATA — Populate with general-purpose data & formulas
        // ─────────────────────────────────────────────
        $sheetAnggaran = $wsFinansial->sheets()->where('name', 'Anggaran & Biaya')->first();

        if ($sheetAnggaran && $sheetAnggaran->columns()->count() > 0 && SpreadsheetCell::whereHas('row', fn($q) => $q->where('sheet_id', $sheetAnggaran->id))->count() === 0) {
            $columns = $sheetAnggaran->columns()->orderBy('sort_order')->get();
            $rows    = $sheetAnggaran->rows()->orderBy('sort_order')->get();

            $colTitles = ['Item Pengeluaran', 'Kategori', 'Qty', 'Harga Satuan', 'Subtotal', 'Keterangan'];
            foreach ($columns as $i => $col) {
                if (isset($colTitles[$i])) {
                    $col->update(['title' => $colTitles[$i]]);
                }
            }

            $cellData = [
                ['Server Cloud AWS Hosting', 'Infrastruktur', '12', '1500000', '=C1*D1', 'Paket tahunan'],
                ['Lisensi Software Produktivitas', 'Software', '25', '350000', '=C2*D2', 'Per user per bulan'],
                ['Pengadaan Perangkat Laptop M3', 'Hardware', '5', '18500000', '=C3*D3', 'Tim engineering baru'],
                ['Internet Dedicated Fiber 1Gbps', 'Fasilitas', '12', '2500000', '=C4*D4', 'Kantor operasional'],
                ['Biaya Pelatihan & Sertifikasi', 'HR & People', '4', '4200000', '=C5*D5', 'AWS Certified Solutions'],
            ];

            foreach ($rows as $rIdx => $row) {
                if (!isset($cellData[$rIdx])) break;
                foreach ($columns as $cIdx => $col) {
                    if (isset($cellData[$rIdx][$cIdx])) {
                        $val = $cellData[$rIdx][$cIdx];
                        $formula = str_starts_with($val, '=') ? $val : null;
                        
                        SpreadsheetCell::firstOrCreate(
                            ['row_id' => $row->id, 'column_id' => $col->id],
                            [
                                'value' => $val,
                                'formula' => $formula,
                                'format' => [
                                    'bold' => ($rIdx === 0 && $cIdx === 0),
                                    'numFormat' => (in_array($cIdx, [2, 3, 4]) ? 'number' : 'general'),
                                    'align' => (in_array($cIdx, [2, 3, 4]) ? 'right' : 'left'),
                                ]
                            ]
                        );
                    }
                }
            }
            $this->command->info('  ✅ Data spreadsheet Anggaran & Biaya dengan formula dan format terisi.');
        }

        // ─────────────────────────────────────────────
        // 5. FRIENDSHIPS — Colleague connections
        // ─────────────────────────────────────────────
        $friendPairs = [
            [$budi->id, $siti->id, 'accepted'],
            [$budi->id, $ahmad->id, 'accepted'],
            [$budi->id, $hendra->id, 'accepted'],
            [$siti->id, $dewi->id, 'accepted'],
            [$ahmad->id, $dewi->id, 'pending'],
        ];

        foreach ($friendPairs as [$userId, $friendId, $status]) {
            $exists = Friendship::where(function ($q) use ($userId, $friendId) {
                $q->where('user_id', $userId)->where('friend_id', $friendId);
            })->orWhere(function ($q) use ($userId, $friendId) {
                $q->where('user_id', $friendId)->where('friend_id', $userId);
            })->exists();

            if (!$exists) {
                Friendship::create([
                    'user_id'   => $userId,
                    'friend_id' => $friendId,
                    'status'    => $status,
                ]);
            }
        }
        $this->command->info('  ✅ Koneksi antar rekan tim ditetapkan.');

        // ─────────────────────────────────────────────
        // 6. NOTIFICATIONS — General workspace notifications
        // ─────────────────────────────────────────────
        $notificationsData = [
            [
                'user_id'     => $budi->id,
                'type'        => 'success',
                'title'       => 'Selamat Datang di Dadhen Mandiri',
                'description' => 'Akun collaborative workspace Anda siap digunakan. Buat workspace baru atau undang rekan tim Anda.',
            ],
            [
                'user_id'     => $budi->id,
                'type'        => 'success',
                'title'       => "Workspace 'Operasional & Manajemen Proyek' Siap",
                'description' => "Kode undangan workspace: {$wsProyek->invite_code}. Lembar spreadsheet kolaboratif telah disiapkan.",
            ],
            [
                'user_id'     => $budi->id,
                'type'        => 'group_invite',
                'title'       => 'Rekan Tim Bergabung',
                'description' => "Siti Rahayu (admin) telah bergabung ke workspace 'Operasional & Manajemen Proyek'.",
            ],
            [
                'user_id'     => $siti->id,
                'type'        => 'success',
                'title'       => 'Selamat Datang di Dadhen Mandiri',
                'description' => 'Akun collaborative workspace Anda berhasil dibuat. Mulai kelola spreadsheet bersama tim.',
            ],
            [
                'user_id'     => $siti->id,
                'type'        => 'success',
                'title'       => 'Bergabung ke Workspace',
                'description' => "Anda kini dapat melihat dan mengedit lembar kerja di workspace 'Operasional & Manajemen Proyek'.",
            ],
        ];

        foreach ($notificationsData as $nData) {
            $existingCount = Notification::where('user_id', $nData['user_id'])
                ->where('title', $nData['title'])
                ->count();

            if ($existingCount === 0) {
                Notification::create(array_merge($nData, ['is_read' => false]));
            }
        }
        $this->command->info('  ✅ Notifikasi demo ditetapkan.');

        // ─────────────────────────────────────────────
        // Summary
        // ─────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('═════════════════════════════════════════════════════════════');
        $this->command->info('  🎉 Seeding Data Demo Collaborative Workspace Selesai!');
        $this->command->info('═════════════════════════════════════════════════════════════');
        $this->command->info('  Akun Demo (password: password123):');
        foreach ($usersData as $u) {
            $this->command->info("    • {$u['email']} — {$u['role_title']}");
        }
        $this->command->info('  Workspace:');
        foreach ($workspaces as $w) {
            $this->command->info("    • {$w->name} (Kode: {$w->invite_code})");
        }
        $this->command->newLine();
    }
}
