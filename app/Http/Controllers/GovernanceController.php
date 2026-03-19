<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ExportJob;
use App\Models\NotificationLog;
use App\Models\OverrideEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class GovernanceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('governance/Index', [
            'summary' => [
                'overrideEvents' => OverrideEvent::query()->count(),
                'exportJobs' => ExportJob::query()->count(),
                'notificationLogs' => NotificationLog::query()->count(),
                'activityLogs' => ActivityLog::query()->count(),
            ],
            'overrideEvents' => OverrideEvent::query()
                ->with(['actor:id,name', 'submission:id,title', 'competitionSession:id,name'])
                ->latest()
                ->limit(12)
                ->get()
                ->map(fn (OverrideEvent $event): array => [
                    'id' => $event->id,
                    'eventType' => $event->event_type->value,
                    'eventLabel' => $event->event_type->label(),
                    'actor' => $event->actor?->name,
                    'submissionTitle' => $event->submission?->title,
                    'sessionName' => $event->competitionSession?->name,
                    'reason' => $event->reason,
                    'beforeState' => $event->before_state,
                    'afterState' => $event->after_state,
                    'createdAt' => $event->created_at?->toDateTimeString(),
                ])
                ->all(),
            'exportJobs' => ExportJob::query()
                ->with('requester:id,name')
                ->latest('completed_at')
                ->limit(10)
                ->get()
                ->map(fn (ExportJob $job): array => [
                    'id' => $job->id,
                    'type' => $job->type,
                    'statusLabel' => $job->status->label(),
                    'rowCount' => $job->row_count,
                    'requestedBy' => $job->requester?->name,
                    'completedAt' => $job->completed_at?->toDateTimeString(),
                ])
                ->all(),
            'notificationLogs' => NotificationLog::query()
                ->with(['recipient:id,name', 'sender:id,name'])
                ->latest('sent_at')
                ->limit(12)
                ->get()
                ->map(fn (NotificationLog $log): array => [
                    'id' => $log->id,
                    'category' => $log->category,
                    'title' => $log->title,
                    'recipient' => $log->recipient?->name,
                    'sender' => $log->sender?->name,
                    'level' => $log->level,
                    'sentAt' => $log->sent_at?->toDateTimeString(),
                ])
                ->all(),
            'recentActivity' => ActivityLog::query()
                ->with('actor:id,name')
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(fn (ActivityLog $log): array => [
                    'id' => $log->id,
                    'event' => $log->event,
                    'description' => $log->description,
                    'actor' => $log->actor?->name,
                    'createdAt' => $log->created_at?->toDateTimeString(),
                ])
                ->all(),
        ]);
    }

    public function sendReminders(Request $request): RedirectResponse
    {
        Artisan::call('negadras:send-workflow-reminders');

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.governance.reminders-triggered',
            description: 'Triggered governance workflow reminders.',
            request: $request,
        );

        return back()->with('success', 'Workflow reminders triggered successfully.');
    }
}
