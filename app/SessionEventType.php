<?php

namespace App;

enum SessionEventType: string
{
    case SessionStarted = 'session_started';
    case SessionPaused = 'session_paused';
    case SessionResumed = 'session_resumed';
    case SessionCompleted = 'session_completed';
    case PresenterStarted = 'presenter_started';
    case PresenterAdvanced = 'presenter_advanced';
    case QueueReordered = 'queue_reordered';
    case ScoresRevealed = 'scores_revealed';
    case ScoresHidden = 'scores_hidden';
    case ProjectionRequested = 'projection_requested';
    case ProjectionApproved = 'projection_approved';
    case ProjectionEnded = 'projection_ended';

    public function label(): string
    {
        return match ($this) {
            self::SessionStarted => 'Session started',
            self::SessionPaused => 'Session paused',
            self::SessionResumed => 'Session resumed',
            self::SessionCompleted => 'Session completed',
            self::PresenterStarted => 'Presenter started',
            self::PresenterAdvanced => 'Presenter advanced',
            self::QueueReordered => 'Queue reordered',
            self::ScoresRevealed => 'Scores revealed',
            self::ScoresHidden => 'Scores hidden',
            self::ProjectionRequested => 'Projection requested',
            self::ProjectionApproved => 'Projection approved',
            self::ProjectionEnded => 'Projection ended',
        };
    }
}
