<?php

namespace Database\Seeders;

use App\Models\ClassOffering;
use App\Models\Connection;
use App\Models\Report;
use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Models\TeacherProfile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private const FIRST_DEMO_USER_ID = 8001;
    private const PROFILE_COUNT = 40;
    private const PRACTITIONER_EMAIL = 'ana.praticante@esporteam.test';

    public function run(): void
    {
        DB::transaction(function (): void {
            $sports = $this->seedSports();

            $this->cleanupDemoDataset();

            $profiles = $this->seedProfiles($sports);
            $teachers = $this->seedTeachers($profiles);

            $this->seedClassOfferings($teachers, $sports);

            $sessions = $this->seedSportSessions($profiles, $sports);
            $this->seedSessionParticipants($sessions, $profiles);

            $this->seedConnections($profiles);
            $this->seedReports($profiles);
        });
    }

    private function seedSports(): array
    {
        $this->call(CommonSportsSeeder::class);

        return Sport::query()
            ->whereIn('slug', array_column($this->sports(), 'slug'))
            ->get()
            ->keyBy('slug')
            ->all();
    }

    private function cleanupDemoDataset(): void
    {
        $profileIds = SportProfile::query()
            ->whereBetween('user_id', [self::FIRST_DEMO_USER_ID, $this->lastDemoUserId()])
            ->pluck('id')
            ->all();

        if ($profileIds === []) {
            return;
        }

        $teacherProfileIds = TeacherProfile::query()
            ->whereIn('sport_profile_id', $profileIds)
            ->pluck('id')
            ->all();

        $classOfferingIds = ClassOffering::query()
            ->whereIn('teacher_profile_id', $teacherProfileIds)
            ->pluck('id')
            ->all();

        $sessionIds = SportSession::query()
            ->whereIn('creator_profile_id', $profileIds)
            ->pluck('id')
            ->all();

        DB::table('reports')
            ->whereIn('reporter_profile_id', $profileIds)
            ->orWhereIn('reported_profile_id', $profileIds)
            ->delete();

        DB::table('connections')
            ->whereIn('requester_profile_id', $profileIds)
            ->orWhereIn('target_profile_id', $profileIds)
            ->orWhereIn('profile_low_id', $profileIds)
            ->orWhereIn('profile_high_id', $profileIds)
            ->delete();

        DB::table('session_participants')
            ->whereIn('sport_profile_id', $profileIds)
            ->orWhereIn('sport_session_id', $sessionIds)
            ->delete();

        DB::table('sport_sessions')
            ->whereIn('id', $sessionIds)
            ->delete();

        DB::table('class_interests')
            ->whereIn('sport_profile_id', $profileIds)
            ->orWhereIn('class_offering_id', $classOfferingIds)
            ->delete();

        DB::table('class_offerings')
            ->whereIn('id', $classOfferingIds)
            ->delete();

        DB::table('teacher_students')
            ->whereIn('teacher_profile_id', $teacherProfileIds)
            ->orWhereIn('student_profile_id', $profileIds)
            ->delete();

        DB::table('teacher_profiles')
            ->whereIn('id', $teacherProfileIds)
            ->delete();

        DB::table('availability_windows')
            ->whereIn('sport_profile_id', $profileIds)
            ->delete();

        DB::table('profile_sports')
            ->whereIn('sport_profile_id', $profileIds)
            ->delete();
    }

    private function seedProfiles(array $sports): array
    {
        $profiles = [];
        $sportSlugs = array_column($this->sports(), 'slug');
        $levels = ['beginner', 'intermediate', 'advanced', 'competitive'];
        $goalSets = [
            ['jogar', 'fazer-amigos'],
            ['treinar', 'competir'],
            ['aprender', 'treinar'],
            ['jogar', 'treinar'],
            ['aprender', 'fazer-amigos'],
        ];
        $locations = $this->locations();

        foreach ($this->profileNames() as $index => $name) {
            $location = $locations[$index % count($locations)];
            $userId = self::FIRST_DEMO_USER_ID + $index;
            $primarySlug = $sportSlugs[$index % count($sportSlugs)];
            $secondarySlug = $sportSlugs[($index + 4) % count($sportSlugs)];

            $profile = SportProfile::query()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'display_name' => $name,
                    'bio' => $this->bioFor($name, $primarySlug, $userId),
                    'city' => $location['city'],
                    'region' => $location['region'],
                    'latitude_approx' => $location['latitude'] + (($index % 5) * 0.003),
                    'longitude_approx' => $location['longitude'] - (($index % 5) * 0.003),
                    'visibility' => 'public',
                    'avatar_url' => "https://example.com/demo/esporteam-avatar-{$userId}.jpg",
                ],
            );

            $profile->sports()->create([
                'sport_id' => $sports[$primarySlug]->id,
                'level' => $levels[$index % count($levels)],
                'goals' => $goalSets[$index % count($goalSets)],
                'preferred_positions' => $this->positionFor($primarySlug, $index),
                'is_primary' => true,
            ]);

            $profile->sports()->create([
                'sport_id' => $sports[$secondarySlug]->id,
                'level' => $levels[($index + 1) % count($levels)],
                'goals' => $goalSets[($index + 2) % count($goalSets)],
                'preferred_positions' => $this->positionFor($secondarySlug, $index + 1),
                'is_primary' => false,
            ]);

            $profile->availabilityWindows()->create([
                'weekday' => ($index % 5) + 1,
                'starts_at' => sprintf('%02d:00', 6 + ($index % 4) * 3),
                'ends_at' => sprintf('%02d:00', 8 + ($index % 4) * 3),
            ]);

            $profile->availabilityWindows()->create([
                'weekday' => (($index + 2) % 6) + 1,
                'starts_at' => sprintf('%02d:00', 17 + ($index % 3)),
                'ends_at' => sprintf('%02d:00', 19 + ($index % 3)),
            ]);

            if ($userId === self::FIRST_DEMO_USER_ID) {
                $profile->availabilityWindows()->create([
                    'weekday' => 6,
                    'starts_at' => '09:00',
                    'ends_at' => '12:00',
                ]);
            }

            $profiles[] = $profile;
        }

        return $profiles;
    }

    private function seedTeachers(array $profiles): array
    {
        $teacherSpecs = [
            ['headline' => 'Professora de corrida para iniciantes', 'credentials' => 'CREF ativo, grupos de rua e provas de 5 km.', 'hourly_price_cents' => 9000, 'service_radius_km' => 8],
            ['headline' => 'Professor de tenis para adultos', 'credentials' => 'Aulas individuais e duplas em quadras publicas.', 'hourly_price_cents' => 14000, 'service_radius_km' => 12],
            ['headline' => 'Personal de musculacao funcional', 'credentials' => 'Treinos de base, mobilidade e fortalecimento.', 'hourly_price_cents' => 12000, 'service_radius_km' => 10],
            ['headline' => 'Instrutora de yoga e mobilidade', 'credentials' => 'Yoga para iniciantes e recuperacao pos-treino.', 'hourly_price_cents' => 8500, 'service_radius_km' => 6],
            ['headline' => 'Professor de futebol society', 'credentials' => 'Treinos tecnicos para pequenos grupos.', 'hourly_price_cents' => 11000, 'service_radius_km' => 10],
            ['headline' => 'Tecnica de natacao', 'credentials' => 'Aperfeicoamento de crawl e respiracao.', 'hourly_price_cents' => 13000, 'service_radius_km' => 9],
            ['headline' => 'Professor de jiu-jitsu iniciante', 'credentials' => 'Defesa pessoal, fundamentos e treino seguro.', 'hourly_price_cents' => 10000, 'service_radius_km' => 7],
            ['headline' => 'Professora de beach tennis', 'credentials' => 'Aulas para duplas, saque e posicionamento.', 'hourly_price_cents' => 15000, 'service_radius_km' => 12],
        ];

        $teachers = [];
        foreach ($teacherSpecs as $index => $spec) {
            $teacherProfile = $profiles[$index + 24];

            $teachers[] = TeacherProfile::query()->create([
                'sport_profile_id' => $teacherProfile->id,
                'headline' => $spec['headline'],
                'credentials' => $spec['credentials'],
                'hourly_price_cents' => $spec['hourly_price_cents'],
                'service_radius_km' => $spec['service_radius_km'],
                'verified_at' => $index < 5 ? CarbonImmutable::now()->subDays(20 - $index) : null,
            ]);
        }

        return $teachers;
    }

    private function seedClassOfferings(array $teachers, array $sports): void
    {
        $classSpecs = [
            ['title' => 'Corrida leve no parque', 'sport' => 'corrida', 'location' => 'Parque Ibirapuera'],
            ['title' => 'Tenis para rally consistente', 'sport' => 'tenis', 'location' => 'Quadras do Centro Esportivo'],
            ['title' => 'Forca para voltar ao esporte', 'sport' => 'musculacao', 'location' => 'Studio Vila Mariana'],
            ['title' => 'Yoga para mobilidade de quadril', 'sport' => 'yoga', 'location' => 'Sala Pinheiros'],
            ['title' => 'Fundamentos de futebol society', 'sport' => 'futebol', 'location' => 'Arena Pompeia'],
            ['title' => 'Natacao crawl iniciante', 'sport' => 'natacao', 'location' => 'Piscina Sumare'],
            ['title' => 'Jiu-jitsu sem medo', 'sport' => 'jiu-jitsu', 'location' => 'Dojo Perdizes'],
            ['title' => 'Beach tennis em dupla', 'sport' => 'beach-tennis', 'location' => 'Arena Moema'],
            ['title' => 'Corrida para primeira prova', 'sport' => 'corrida', 'location' => 'Parque Villa-Lobos'],
            ['title' => 'Tenis saque e devolucao', 'sport' => 'tenis', 'location' => 'Clube Jardim Europa'],
            ['title' => 'Funcional para condicionamento', 'sport' => 'funcional', 'location' => 'Praca Roosevelt'],
            ['title' => 'Yoga restaurativa', 'sport' => 'yoga', 'location' => 'Casa Saude'],
            ['title' => 'Volei de fundamentos', 'sport' => 'volei', 'location' => 'Ginasio Lapa'],
            ['title' => 'Basquete arremesso e defesa', 'sport' => 'basquete', 'location' => 'Quadra Consolacao'],
            ['title' => 'Natacao tecnica de respiracao', 'sport' => 'natacao', 'location' => 'Clube Pinheiros'],
        ];
        $locations = $this->locations();

        foreach ($classSpecs as $index => $spec) {
            $location = $locations[$index % count($locations)];
            ClassOffering::query()->create([
                'teacher_profile_id' => $teachers[$index % count($teachers)]->id,
                'sport_id' => $sports[$spec['sport']]->id,
                'title' => $spec['title'],
                'description' => 'Aula demo do Cola Aí com foco em progressao segura e combinacao local.',
                'price_cents' => 7000 + (($index % 6) * 1500),
                'starts_at' => CarbonImmutable::now()->addDays($index + 2)->setTime(7 + (($index % 5) * 2), 0),
                'recurrence' => $index % 2 === 0 ? 'weekly' : 'single',
                'location_label' => $spec['location'],
                'city' => $location['city'],
                'region' => $location['region'],
                'latitude_approx' => $location['latitude'],
                'longitude_approx' => $location['longitude'],
                'capacity' => 4 + ($index % 5),
                'status' => 'open',
            ]);
        }
    }

    private function seedSportSessions(array $profiles, array $sports): array
    {
        $sessionSpecs = [
            ['title' => 'Pelada leve para voltar a jogar', 'sport' => 'futebol', 'type' => 'partida', 'location' => 'Arena Vila Madalena', 'entry_mode' => 'publica_direta', 'min_level' => 'beginner', 'max_level' => 'intermediate', 'weekday' => 1, 'hour' => 7],
            ['title' => 'Corrida 5 km conversada', 'sport' => 'corrida', 'type' => 'corrida', 'location' => 'Parque Ibirapuera'],
            ['title' => 'Racha de basquete 3x3', 'sport' => 'basquete', 'type' => 'partida', 'location' => 'Quadra Augusta'],
            ['title' => 'Volei misto com aprovacao do anfitriao', 'sport' => 'volei', 'type' => 'encontro', 'location' => 'Ginasio Lapa', 'entry_mode' => 'publica_aprovacao', 'min_level' => 'beginner', 'max_level' => 'advanced', 'weekday' => 3, 'hour' => 18],
            ['title' => 'Treino de saque no tenis', 'sport' => 'tenis', 'type' => 'treino', 'location' => 'Centro Esportivo Pinheiros'],
            ['title' => 'Beach tennis iniciantes', 'sport' => 'beach-tennis', 'type' => 'encontro', 'location' => 'Arena Moema'],
            ['title' => 'Pedal urbano curto', 'sport' => 'ciclismo', 'type' => 'encontro', 'location' => 'Praca Panamericana'],
            ['title' => 'Funcional ao ar livre', 'sport' => 'funcional', 'type' => 'aula_aberta', 'location' => 'Parque Agua Branca'],
            ['title' => 'Musculacao em dupla', 'sport' => 'musculacao', 'type' => 'treino', 'location' => 'Academia Saude'],
            ['title' => 'Roda de jiu-jitsu fundamentos', 'sport' => 'jiu-jitsu', 'type' => 'treino', 'location' => 'Dojo Perdizes'],
            ['title' => 'Natacao tecnica livre', 'sport' => 'natacao', 'type' => 'treino', 'location' => 'Piscina Sumare'],
            ['title' => 'Yoga no fim de tarde', 'sport' => 'yoga', 'type' => 'encontro', 'location' => 'Praca Por do Sol'],
            ['title' => 'Futebol society competitivo', 'sport' => 'futebol', 'type' => 'partida', 'location' => 'Arena Pompeia'],
            ['title' => 'Corrida de subida leve', 'sport' => 'corrida', 'type' => 'corrida', 'location' => 'Minhocao'],
            ['title' => 'Basquete feminino aberto', 'sport' => 'basquete', 'type' => 'partida', 'location' => 'Quadra Roosevelt'],
            ['title' => 'Volei de praia adaptado', 'sport' => 'volei', 'type' => 'encontro', 'location' => 'Arena Beach Center'],
            ['title' => 'Tenis duplas rotativas', 'sport' => 'tenis', 'type' => 'partida', 'location' => 'Clube Jardim Europa'],
            ['title' => 'Pedal ate o parque', 'sport' => 'ciclismo', 'type' => 'encontro', 'location' => 'Metro Vila Mariana'],
            ['title' => 'Funcional para corredores', 'sport' => 'funcional', 'type' => 'aula_aberta', 'location' => 'Parque Villa-Lobos'],
            ['title' => 'Beach tennis intermediario', 'sport' => 'beach-tennis', 'type' => 'partida', 'location' => 'Arena Pinheiros'],
        ];
        $locations = $this->locations();
        $sessions = [];

        foreach ($sessionSpecs as $index => $spec) {
            $location = $locations[$index % count($locations)];
            $entryMode = $spec['entry_mode'] ?? match ($index % 5) {
                1 => 'publica_aprovacao',
                4 => 'convite',
                default => 'publica_direta',
            };

            $sessions[] = SportSession::query()->create([
                'creator_profile_id' => $profiles[($index + 8) % count($profiles)]->id,
                'sport_id' => $sports[$spec['sport']]->id,
                'title' => $spec['title'],
                'description' => 'Sessao aberta e gratuita para perfis esportivos da regiao combinarem pratica local.',
                'type' => $spec['type'],
                'starts_at' => isset($spec['weekday'], $spec['hour'])
                    ? $this->nextWeekdayAt($spec['weekday'], $spec['hour'])
                    : CarbonImmutable::now()->addDays(intdiv($index, 2) + 1)->setTime(7 + (($index % 6) * 2), 0),
                'location_label' => $spec['location'],
                'city' => $location['city'],
                'region' => $location['region'],
                'latitude_approx' => $location['latitude'],
                'longitude_approx' => $location['longitude'],
                'capacity' => 6 + ($index % 8),
                'requires_approval' => $entryMode === 'publica_aprovacao',
                'entry_mode' => $entryMode,
                'min_level' => $spec['min_level'] ?? $this->minLevelForSession($index),
                'max_level' => $spec['max_level'] ?? $this->maxLevelForSession($index),
                'visibility' => 'public',
                'status' => 'open',
            ]);
        }

        return $sessions;
    }

    private function seedSessionParticipants(array $sessions, array $profiles): void
    {
        foreach ($sessions as $index => $session) {
            $participants = [
                $session->creator_profile_id => 'joined',
            ];
            $offset = 1;

            while (count($participants) < 2 + ($index % 3)) {
                $candidate = $profiles[($index + ($offset * 5)) % count($profiles)];
                $offset++;

                if ($candidate->id === $session->creator_profile_id || array_key_exists($candidate->id, $participants)) {
                    continue;
                }

                $participants[$candidate->id] = $this->participantStatusFor($session->entry_mode->value, count($participants), $index);
            }

            foreach ($participants as $profileId => $status) {
                DB::table('session_participants')->insert([
                    'sport_session_id' => $session->id,
                    'sport_profile_id' => $profileId,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->seedPractitionerJourney($sessions, $profiles);
    }

    private function seedPractitionerJourney(array $sessions, array $profiles): void
    {
        $practitioner = $profiles[0];

        foreach ([0 => 'joined', 3 => 'interested', 4 => 'invited', 12 => 'declined'] as $sessionIndex => $status) {
            if ($sessions[$sessionIndex]->creator_profile_id === $practitioner->id) {
                continue;
            }

            DB::table('session_participants')->updateOrInsert(
                [
                    'sport_session_id' => $sessions[$sessionIndex]->id,
                    'sport_profile_id' => $practitioner->id,
                ],
                [
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedConnections(array $profiles): void
    {
        $connections = [
            [0, 1, 'friendship', 'accepted'],
            [2, 3, 'friendship', 'pending'],
            [4, 5, 'friendship', 'accepted'],
            [10, 11, 'friendship', 'pending'],
            [6, 7, 'interest', 'interested'],
            [8, 9, 'interest', 'interested'],
            [12, 13, 'interest', 'interested'],
            [14, 15, 'block', 'blocked'],
            [16, 17, 'block', 'blocked'],
        ];

        foreach ($connections as [$requesterIndex, $targetIndex, $type, $status]) {
            [$lowId, $highId] = $this->profilePair($profiles[$requesterIndex]->id, $profiles[$targetIndex]->id);

            Connection::query()->create([
                'requester_profile_id' => $profiles[$requesterIndex]->id,
                'target_profile_id' => $profiles[$targetIndex]->id,
                'profile_low_id' => $lowId,
                'profile_high_id' => $highId,
                'type' => $type,
                'status' => $status,
            ]);
        }
    }

    private function seedReports(array $profiles): void
    {
        $reports = [
            [18, 19, 'no_show', 'Perfil confirmou uma sessao e nao apareceu duas vezes.'],
            [20, 21, 'harassment', 'Mensagem agressiva depois de convite recusado.'],
            [22, 23, 'spam', 'Enviou convite repetido para aula fora do interesse combinado.'],
        ];

        foreach ($reports as [$reporterIndex, $reportedIndex, $reason, $details]) {
            Report::query()->create([
                'reporter_profile_id' => $profiles[$reporterIndex]->id,
                'reported_profile_id' => $profiles[$reportedIndex]->id,
                'reason' => $reason,
                'details' => $details,
                'status' => 'open',
                'context' => [
                    'reporter' => $this->profileContext($profiles[$reporterIndex]),
                    'reported' => $this->profileContext($profiles[$reportedIndex]),
                ],
            ]);
        }
    }

    private function sports(): array
    {
        return [
            ['name' => 'Futebol', 'slug' => 'futebol', 'category' => 'coletivo', 'is_active' => true],
            ['name' => 'Corrida', 'slug' => 'corrida', 'category' => 'endurance', 'is_active' => true],
            ['name' => 'Tênis', 'slug' => 'tenis', 'category' => 'raquete', 'is_active' => true],
            ['name' => 'Beach Tennis', 'slug' => 'beach-tennis', 'category' => 'raquete', 'is_active' => true],
            ['name' => 'Vôlei', 'slug' => 'volei', 'category' => 'coletivo', 'is_active' => true],
            ['name' => 'Basquete', 'slug' => 'basquete', 'category' => 'coletivo', 'is_active' => true],
            ['name' => 'Ciclismo', 'slug' => 'ciclismo', 'category' => 'endurance', 'is_active' => true],
            ['name' => 'Musculação', 'slug' => 'musculacao', 'category' => 'fitness', 'is_active' => true],
            ['name' => 'Jiu-jitsu', 'slug' => 'jiu-jitsu', 'category' => 'luta', 'is_active' => true],
            ['name' => 'Natação', 'slug' => 'natacao', 'category' => 'aquatico', 'is_active' => true],
            ['name' => 'Yoga', 'slug' => 'yoga', 'category' => 'bem-estar', 'is_active' => true],
            ['name' => 'Treino funcional', 'slug' => 'funcional', 'category' => 'fitness', 'is_active' => true],
        ];
    }

    private function profileNames(): array
    {
        return [
            'Ana Martins', 'Bruno Rocha', 'Carla Nunes', 'Diego Lima', 'Eduarda Melo',
            'Fabio Torres', 'Gustavo Reis', 'Helena Prado', 'Igor Batista', 'Julia Campos',
            'Kaique Souza', 'Lais Freitas', 'Marcos Silva', 'Natalia Costa', 'Otavio Pereira',
            'Paula Ribeiro', 'Renata Alves', 'Samuel Dias', 'Marina Teixeira', 'Thiago Gomes',
            'Vitoria Ramos', 'Leandro Barros', 'Felipe Andrade', 'Bianca Farias', 'Caio Henrique',
            'Dani Moraes', 'Clara Duarte', 'Rafael Cardoso', 'Sofia Araujo', 'Pedro Nogueira',
            'Taina Lopes', 'Vinicius Martins', 'Aline Castro', 'Joao Batista', 'Luiza Fernandes',
            'Mauricio Pires', 'Patricia Moreira', 'Rodrigo Neves', 'Sara Monteiro', 'Wesley Almeida',
        ];
    }

    private function locations(): array
    {
        return [
            ['city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.55052, 'longitude' => -46.63331],
            ['city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.56139, 'longitude' => -46.65645],
            ['city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.58742, 'longitude' => -46.65763],
            ['city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.54318, 'longitude' => -46.72054],
            ['city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.52719, 'longitude' => -46.67809],
            ['city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.59891, 'longitude' => -46.67678],
            ['city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.56620, 'longitude' => -46.70186],
            ['city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.52272, 'longitude' => -46.62529],
        ];
    }

    private function bioFor(string $name, string $primarySportSlug, int $userId): string
    {
        if ($userId === self::FIRST_DEMO_USER_ID) {
            return sprintf(
                '%s e o perfil demo de praticante (%s) para testar descoberta, convites e participacao em sessoes reais.',
                $name,
                self::PRACTITIONER_EMAIL,
            );
        }

        return "{$name} usa o Cola Aí para encontrar companhia local em {$primarySportSlug}, combinar treinos e descobrir sessoes abertas.";
    }

    private function positionFor(string $sportSlug, int $index): ?string
    {
        $positions = [
            'futebol' => ['goleiro', 'linha', 'meia'],
            'volei' => ['levantamento', 'ponta', 'defesa'],
            'basquete' => ['armador', 'ala', 'pivo'],
            'tenis' => ['simples', 'duplas'],
            'beach-tennis' => ['duplas', 'rede'],
        ];

        if (! isset($positions[$sportSlug])) {
            return null;
        }

        return $positions[$sportSlug][$index % count($positions[$sportSlug])];
    }

    private function profilePair(int $firstProfileId, int $secondProfileId): array
    {
        return [
            min($firstProfileId, $secondProfileId),
            max($firstProfileId, $secondProfileId),
        ];
    }

    private function profileContext(SportProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'display_name' => $profile->display_name,
            'city' => $profile->city,
            'region' => $profile->region,
            'visibility' => $profile->visibility?->value,
        ];
    }

    private function lastDemoUserId(): int
    {
        return self::FIRST_DEMO_USER_ID + self::PROFILE_COUNT - 1;
    }

    private function nextWeekdayAt(int $weekday, int $hour): CarbonImmutable
    {
        $candidate = CarbonImmutable::now()->next($weekday)->setTime($hour, 0);

        return $candidate->isPast()
            ? $candidate->addWeek()
            : $candidate;
    }

    private function minLevelForSession(int $index): ?string
    {
        return match ($index % 4) {
            0 => 'beginner',
            1 => 'intermediate',
            default => null,
        };
    }

    private function maxLevelForSession(int $index): ?string
    {
        return match ($index % 4) {
            0 => 'intermediate',
            1 => 'competitive',
            default => null,
        };
    }

    private function participantStatusFor(string $entryMode, int $participantIndex, int $sessionIndex): string
    {
        if ($entryMode === 'publica_aprovacao') {
            return $participantIndex % 2 === 0 ? 'approved' : 'interested';
        }

        if ($entryMode === 'convite') {
            return $sessionIndex % 2 === 0 ? 'invited' : 'approved';
        }

        return $participantIndex % 4 === 0 ? 'left' : 'joined';
    }
}
