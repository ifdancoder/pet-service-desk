<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\SlaPolicy;
use App\Models\Tag;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $departments = Department::factory()
            ->count(3)
            ->has(Team::factory()->count(2))
            ->create();

        $teams = $departments->flatMap->teams;

        $agents = User::factory()
            ->count(15)
            ->create()
            ->each(function (User $agent) use ($departments, $teams) {
                $agent->update(['department_id' => $departments->random()->id]);
                $agent->teams()->attach($teams->random(random_int(1, 2))->pluck('id'));
            });

        $testUser->update(['department_id' => $departments->first()->id]);

        $categories = TicketCategory::factory()
            ->count(5)
            ->create()
            ->each(function (TicketCategory $category) {
                foreach (TicketPriority::cases() as $priority) {
                    SlaPolicy::factory()->create([
                        'category_id' => $category->id,
                        'priority' => $priority,
                        'response_time_minutes' => match ($priority) {
                            TicketPriority::Critical => 30,
                            TicketPriority::High => 120,
                            TicketPriority::Normal => 480,
                            TicketPriority::Low => 1_440,
                        },
                        'resolution_time_minutes' => match ($priority) {
                            TicketPriority::Critical => 120,
                            TicketPriority::High => 480,
                            TicketPriority::Normal => 1_440,
                            TicketPriority::Low => 4_320,
                        },
                    ]);
                }
            });

        $tags = Tag::factory()->count(8)->create();

        Ticket::factory()
            ->count(40)
            ->state(fn () => [
                'status' => fake()->randomElement(TicketStatus::cases()),
                'priority' => fake()->randomElement(TicketPriority::cases()),
                'requester_id' => $agents->random()->id,
                'assignee_id' => fake()->boolean(70) ? $agents->random()->id : null,
                'category_id' => $categories->random()->id,
                'department_id' => $departments->random()->id,
                'team_id' => fake()->boolean(80) ? $teams->random()->id : null,
            ])
            ->create()
            ->each(function (Ticket $ticket) use ($agents, $tags) {
                TicketComment::factory()
                    ->count(random_int(0, 4))
                    ->for($ticket)
                    ->create(['author_id' => $agents->random()->id]);

                $ticket->watchers()->attach($agents->random(random_int(0, 3))->pluck('id'));
                $ticket->tags()->attach($tags->random(random_int(0, 3))->pluck('id'));
            });
    }
}
