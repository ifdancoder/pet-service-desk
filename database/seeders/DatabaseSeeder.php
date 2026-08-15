<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\SlaPolicy;
use App\Models\Tag;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // WithoutModelEvents (above) wraps this entire run() — including the
        // nested seeder call below — in Model::withoutEvents(). spatie/laravel-permission
        // relies on the Eloquent `saved`/`deleted` events to invalidate its permission
        // cache, so with events suppressed, roles created here can never see the
        // permissions RolePermissionSeeder just created. Restore real events for
        // just this call, then resume suppressing them for the bulk factory calls below.
        $suppressedDispatcher = Model::getEventDispatcher();
        Model::setEventDispatcher(app('events'));
        $this->call(RolePermissionSeeder::class);
        Model::setEventDispatcher($suppressedDispatcher);

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $testUser->assignRole(UserRole::Administrator->value);

        $departments = Department::factory()
            ->count(3)
            ->has(Team::factory()->count(2))
            ->create();

        $agents = User::factory()
            ->count(15)
            ->create()
            ->each(function (User $agent) use ($departments) {
                $department = $departments->random();
                $agent->update(['department_id' => $department->id]);
                $agent->teams()->attach($department->teams->random(random_int(1, 2))->pluck('id'));
            });

        $agents->slice(0, 10)->each(
            fn (User $agent) => $agent->assignRole(UserRole::SupportAgent->value)
        );
        $agents->slice(10, 3)->each(
            fn (User $agent) => $agent->assignRole([UserRole::SupportAgent->value, UserRole::TeamLead->value])
        );
        $agents->slice(13, 2)->each(
            fn (User $agent) => $agent->assignRole([UserRole::SupportAgent->value, UserRole::SupportManager->value])
        );

        $testUser->update(['department_id' => $departments->first()->id]);

        $agents = $agents->push($testUser);

        $customers = User::factory()
            ->count(20)
            ->create()
            ->each(fn (User $customer) => $customer->assignRole(UserRole::Customer->value));

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

        foreach (TicketPriority::cases() as $priority) {
            SlaPolicy::factory()->create([
                'category_id' => null,
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

        $tags = Tag::factory()->count(8)->create();

        Ticket::factory()
            ->count(40)
            ->state(function () use ($agents, $customers, $categories, $departments) {
                $department = $departments->random();

                return [
                    'status' => fake()->randomElement(TicketStatus::cases()),
                    'priority' => fake()->randomElement(TicketPriority::cases()),
                    'requester_id' => $customers->random()->id,
                    'assignee_id' => fake()->boolean(70) ? $agents->random()->id : null,
                    'category_id' => $categories->random()->id,
                    'department_id' => $department->id,
                    'team_id' => fake()->boolean(80) ? $department->teams->random()->id : null,
                ];
            })
            ->create()
            ->each(function (Ticket $ticket) use ($agents, $tags) {
                TicketComment::factory()
                    ->count(random_int(0, 4))
                    ->for($ticket)
                    ->create(['author_id' => $agents->random()->id]);

                TicketAttachment::factory()
                    ->count(random_int(0, 2))
                    ->for($ticket)
                    ->create(['uploader_id' => $agents->random()->id]);

                $ticket->watchers()->attach($agents->random(random_int(0, 3))->pluck('id'));
                $ticket->tags()->attach($tags->random(random_int(0, 3))->pluck('id'));
            });
    }
}
