<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Default boilerplate roles that should always exist.
     *
     * @var array<string, array{description: string, permissions: array<int, string>}>
     */
    private const DEFAULT_ROLES = [
        'Admin' => [
            'description' => 'Full-access recovery role for managing the entire boilerplate.',
            'permissions' => [
                'dashboard.view',
                'search.view',
                'handbook.view',
                'exports.view',
                'settings.view',
                'settings.update',
                'media.view',
                'media.create',
                'media.delete',
                'notes.view',
                'notes.create',
                'notes.delete',
                'reports.view',
                'applicants.view',
                'applicants.create',
                'applicants.update',
                'applicants.delete',
                'organizations.view',
                'organizations.create',
                'organizations.update',
                'organizations.delete',
                'seasons.view',
                'seasons.create',
                'seasons.update',
                'seasons.delete',
                'stages.view',
                'stages.create',
                'stages.update',
                'stages.delete',
                'industries.view',
                'industries.create',
                'industries.update',
                'industries.delete',
                'reviewers.view',
                'reviewers.create',
                'reviewers.update',
                'reviewer-assignments.view',
                'reviewer-assignments.create',
                'reviewer-assignments.update',
                'reviewer-queue.view',
                'screening-queue.view',
                'screening-reviews.view',
                'screening-reviews.create',
                'screening-reviews.update',
                'submissions.view',
                'submissions.create',
                'submissions.update',
                'submissions.delete',
                'pages.view',
                'pages.create',
                'pages.update',
                'pages.delete',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',
                'notifications.view',
                'activity-logs.view',
            ],
        ],
        'Manager' => [
            'description' => 'Operational role with visibility into shared activity and inbox-style features.',
            'permissions' => [
                'dashboard.view',
                'search.view',
                'handbook.view',
                'exports.view',
                'settings.view',
                'media.view',
                'media.create',
                'notes.view',
                'notes.create',
                'notes.delete',
                'reports.view',
                'applicants.view',
                'applicants.update',
                'organizations.view',
                'organizations.create',
                'organizations.update',
                'seasons.view',
                'seasons.create',
                'seasons.update',
                'stages.view',
                'stages.create',
                'stages.update',
                'industries.view',
                'industries.create',
                'industries.update',
                'reviewers.view',
                'reviewers.create',
                'reviewers.update',
                'reviewer-assignments.view',
                'reviewer-assignments.create',
                'reviewer-assignments.update',
                'screening-queue.view',
                'screening-reviews.view',
                'screening-reviews.update',
                'submissions.view',
                'submissions.update',
                'pages.view',
                'pages.create',
                'pages.update',
                'notifications.view',
                'activity-logs.view',
            ],
        ],
        'Secretary' => [
            'description' => 'Intake operations role focused on submission review, return-for-correction, and coordination support.',
            'permissions' => [
                'dashboard.view',
                'search.view',
                'handbook.view',
                'reports.view',
                'applicants.view',
                'organizations.view',
                'seasons.view',
                'stages.view',
                'industries.view',
                'submissions.view',
                'submissions.update',
                'notifications.view',
                'activity-logs.view',
            ],
        ],
        'Reviewer' => [
            'description' => 'Assigned reviewer who can access only reviewer queue work and screening review actions.',
            'permissions' => [
                'dashboard.view',
                'handbook.view',
                'notifications.view',
                'reviewer-queue.view',
                'screening-reviews.view',
                'screening-reviews.create',
            ],
        ],
        'Member' => [
            'description' => 'Standard internal user with dashboard and notification access.',
            'permissions' => [
                'dashboard.view',
                'search.view',
                'handbook.view',
                'notifications.view',
            ],
        ],
        'ReadOnly' => [
            'description' => 'Signed-in user with read-only access to the shared workspace.',
            'permissions' => [
                'dashboard.view',
                'search.view',
                'handbook.view',
            ],
        ],
        'External' => [
            'description' => 'Restricted external account with only the base workspace available.',
            'permissions' => [
                'dashboard.view',
                'search.view',
                'handbook.view',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'search.view',
            'handbook.view',
            'exports.view',
            'settings.view',
            'settings.update',
            'media.view',
            'media.create',
            'media.delete',
            'notes.view',
            'notes.create',
            'notes.delete',
            'reports.view',
            'applicants.view',
            'applicants.create',
            'applicants.update',
            'applicants.delete',
            'organizations.view',
            'organizations.create',
            'organizations.update',
            'organizations.delete',
            'seasons.view',
            'seasons.create',
            'seasons.update',
            'seasons.delete',
            'stages.view',
            'stages.create',
            'stages.update',
            'stages.delete',
            'industries.view',
            'industries.create',
            'industries.update',
            'industries.delete',
            'reviewers.view',
            'reviewers.create',
            'reviewers.update',
            'reviewer-assignments.view',
            'reviewer-assignments.create',
            'reviewer-assignments.update',
            'reviewer-queue.view',
            'screening-queue.view',
            'screening-reviews.view',
            'screening-reviews.create',
            'screening-reviews.update',
            'submissions.view',
            'submissions.create',
            'submissions.update',
            'submissions.delete',
            'pages.view',
            'pages.create',
            'pages.update',
            'pages.delete',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'notifications.view',
            'activity-logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::DEFAULT_ROLES as $name => $definition) {
            $role = Role::query()->updateOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],
                [
                    'description' => $definition['description'],
                ],
            );

            $role->syncPermissions($definition['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
