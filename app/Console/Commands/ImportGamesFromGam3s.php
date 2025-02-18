<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Game;
use App\Models\Platform;
use App\Models\Cryptocurrency;
use App\Models\Genre;
use App\Models\User;
use App\Models\Review;
use Database\Factories\ReviewFactory;
class ImportGamesFromGam3s extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:import-from-gam3s {--limit=20 : Limit the number of games to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import games from the Gam3s.gg GraphQL API';

    // GraphQL Query 
    protected $graphqlQuery = '
        query AllGames($start: Int, $limit: Int, $sort: [String]) {
            games(pagination: {start: $start, limit: $limit}, sort: $sort) {
                data {
                    id
                    attributes {
                        name
                        slug
                        shortDescription
                        publishedAt
                        status {
                            data {
                                attributes {
                                    label
                                }
                            }
                        }
                        platforms {
                            platform {
                                data {
                                    attributes {
                                        label
                                    }
                                }
                            }
                        }
                        tokens {
                            symbol
                        }
                    }
                }
            }
        }
    ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $baseUrl = 'https://cms.gam3s.gg/graphql';
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:104.0) Gecko/20100101 Firefox/104.0',
            'Mozilla/5.0 (Linux; Android 11; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        ]; // I put many user agents to avoid being blocked by the API

        $start = 0;
        $limit = (int) $this->option('limit');
        $totalImported = 0;
        

        $this->info("Starting Gam3s.gg import...");

        do {
            
            $response = Http::withHeaders([
                'User-Agent' => $userAgents[array_rand($userAgents)],
                'Content-Type' => 'application/json',
            ])->post($baseUrl, [
                'query' => $this->graphqlQuery,
                'variables' => [
                    'start' => $start,
                    'limit' => $limit,
                    'sort' => 'id:asc',
                ],
            ]);

            if (!$response->successful()) {
                $this->error("Failed to fetch games. Status: " . $response->status());
                break;
            }

            $gamesData = $response->json('data.games.data');

            if (empty($gamesData)) {
                $this->info("No more games to fetch.");
                break;
            }

            foreach ($gamesData as $gameData) {
                $attributes = $gameData['attributes'];
                $platforms = $this->parsePlatforms($attributes['platforms']);
                $cryptos = $this->parseCryptos($attributes['tokens']);

                $game = Game::updateOrCreate(
                    [
                        'name' => $attributes['name'],
                        'manager' => User::factory()->create([
                            'role' => 'manager'
                        ])->id,
                        'price' => 59.99,
                        'description' => $attributes['shortDescription'],
                        'created_at' => $attributes['publishedAt'],
                    ]
                );

                $game->platforms()->sync($platforms);
                $game->cryptos()->sync($cryptos);
                $game->genres()->sync(Genre::firstOrCreate(['name' => 'RPG']));

                Review::factory()->create([
                    'game_id' => $game->id,
                    'user_id' => User::factory(),
                ]);

                $totalImported++;
                $this->info("Imported: {$attributes['name']}");

                if ($totalImported >= $limit) {
                    $this->info("Reached the limit of {$limit} games.");
                    break 2; 
                }
            }

            $start += $limit;
            sleep(3); // Rate limiting because if the API catches us, we'll be banned for a while so gotta keep it low
        } while (!empty($gamesData));

        $this->info("Import completed! Total games imported: $totalImported");
    }

    private function parsePlatforms(array $platforms): array
    {
        return collect($platforms)->map(function ($platform) {
            $name = $platform['platform']['data']['attributes']['label'];
            return Platform::firstOrCreate(['name' => $name])->id;
        })->filter()->toArray();
    }

    private function parseCryptos(array $tokens): array
    {
        return collect($tokens)->map(function ($token) {
            $symbol = $token['symbol'];
            return Cryptocurrency::firstOrCreate(['name' => $symbol,'symbol' => $symbol])->id;
        })->filter()->toArray();
    }
}
