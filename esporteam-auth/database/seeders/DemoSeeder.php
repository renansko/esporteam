<?php

namespace Database\Seeders;

use App\Enums\UserProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    private const FIRST_SPORT_DEMO_USER_ID = 8001;

    public function run(): void
    {
        User::unguarded(function (): void {
            User::updateOrCreate(
                ['email' => 'eduardo@mesa.app'],
                [
                    'id' => 1,
                    'name' => 'Eduardo Bassan',
                    'profile' => UserProfile::Admin->value,
                    'password' => Hash::make('demo1234'),
                    'permissions' => 1,
                    'email_verified_at' => now(),
                ],
            );

            foreach ($this->sportDemoUsers() as $index => $user) {
                User::updateOrCreate(
                    ['email' => $user['email']],
                    [
                        'id' => self::FIRST_SPORT_DEMO_USER_ID + $index,
                        'name' => $user['name'],
                        'profile' => $user['profile'],
                        'password' => Hash::make('demo1234'),
                        'permissions' => 0,
                        'email_verified_at' => now(),
                    ],
                );
            }
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1))");
        }
    }

    private function sportDemoUsers(): array
    {
        $names = [
            'Ana Martins', 'Bruno Rocha', 'Carla Nunes', 'Diego Lima', 'Eduarda Melo',
            'Fabio Torres', 'Gustavo Reis', 'Helena Prado', 'Igor Batista', 'Julia Campos',
            'Kaique Souza', 'Lais Freitas', 'Marcos Silva', 'Natalia Costa', 'Otavio Pereira',
            'Paula Ribeiro', 'Renata Alves', 'Samuel Dias', 'Marina Teixeira', 'Thiago Gomes',
            'Vitoria Ramos', 'Leandro Barros', 'Felipe Andrade', 'Bianca Farias', 'Caio Henrique',
            'Dani Moraes', 'Clara Duarte', 'Rafael Cardoso', 'Sofia Araujo', 'Pedro Nogueira',
            'Taina Lopes', 'Vinicius Martins', 'Aline Castro', 'Joao Batista', 'Luiza Fernandes',
            'Mauricio Pires', 'Patricia Moreira', 'Rodrigo Neves', 'Sara Monteiro', 'Wesley Almeida',
        ];

        return array_map(
            fn (string $name, int $index): array => [
                'name' => $name,
                'email' => $index === 0
                    ? 'ana.praticante@esporteam.test'
                    : sprintf('demo%02d@esporteam.test', $index + 1),
                'profile' => $index >= 24 && $index <= 31
                    ? UserProfile::Teacher->value
                    : UserProfile::User->value,
            ],
            $names,
            array_keys($names),
        );
    }
}
