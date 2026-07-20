<?php

use App\Http\Controllers\BioSuggestionController;
use App\Http\Controllers\ClassOfferingController;
use App\Http\Controllers\ClusteringRunController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\ConversationMediaController;
use App\Http\Controllers\ConversationPushController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\EventConversationController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\PostMatchSportActionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\SportGroupController;
use App\Http\Controllers\SportProfileController;
use App\Http\Controllers\SportSessionController;
use App\Http\Controllers\TeacherProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Broadcast::routes(['middleware' => ['auth.service']]);

Route::middleware('auth.service')->group(function () {
    Route::get('/me', MeController::class);
    Route::get('/push', [ConversationPushController::class, 'show']);
    Route::post('/push/subscriptions', [ConversationPushController::class, 'subscribe']);
    Route::patch('/push/preferences', [ConversationPushController::class, 'preference']);
    Route::delete('/push/subscriptions', [ConversationPushController::class, 'unsubscribe']);

    Route::get('/sports', [SportController::class, 'index']);
    Route::get('/discovery', [DiscoveryController::class, 'index'])
        ->middleware('throttle.user:discovery');
    Route::get('/profile', [SportProfileController::class, 'show']);
    Route::put('/profile', [SportProfileController::class, 'upsert']);
    Route::put('/profile/sports', [SportProfileController::class, 'sports']);
    Route::put('/profile/availability', [SportProfileController::class, 'availability']);
    Route::get('/profile/bio-suggestions', [BioSuggestionController::class, 'index']);
    Route::post('/profile/bio-suggestions', [BioSuggestionController::class, 'store'])
        ->middleware('throttle.bio-suggestion');
    Route::post('/profile/bio-suggestions/{suggestion}/accept', [BioSuggestionController::class, 'accept'])
        ->whereNumber('suggestion');

    Route::get('/teacher-profile', [TeacherProfileController::class, 'show']);
    Route::put('/teacher-profile', [TeacherProfileController::class, 'upsert']);
    Route::get('/teacher-profile/students', [TeacherProfileController::class, 'students']);
    Route::post('/teacher-profile/students', [TeacherProfileController::class, 'addStudent']);
    Route::delete('/teacher-profile/students/{studentProfile}', [TeacherProfileController::class, 'removeStudent']);

    Route::get('/classes', [ClassOfferingController::class, 'index']);
    Route::post('/classes', [ClassOfferingController::class, 'store']);
    Route::post('/classes/{classOffering}/interest', [ClassOfferingController::class, 'interest']);

    Route::get('/groups', [SportGroupController::class, 'index']);
    Route::post('/groups', [SportGroupController::class, 'store']);
    Route::get('/groups/{group}', [SportGroupController::class, 'show']);
    Route::post('/groups/{group}/members', [SportGroupController::class, 'addMember']);
    Route::delete('/groups/{group}/members/{profile}', [SportGroupController::class, 'removeMember']);

    Route::get('/sessions', [SportSessionController::class, 'index'])
        ->middleware('throttle.user:map');
    Route::post('/sessions', [SportSessionController::class, 'store'])->middleware('adult.eligible');
    Route::post('/sessions/publish-one-off', [SportSessionController::class, 'publishOneOff'])->middleware('adult.eligible');
    Route::post('/sessions/publish-series', [SportSessionController::class, 'publishSeries'])->middleware('adult.eligible');
    Route::get('/profile/sessions', [SportSessionController::class, 'participantSessions']);
    Route::get('/events', [SportSessionController::class, 'events']);
    Route::post('/session-series/{series}/follow', [SportSessionController::class, 'followSeries'])->middleware('adult.eligible')->whereNumber('series');
    Route::delete('/session-series/{series}/follow', [SportSessionController::class, 'unfollowSeries'])->middleware('adult.eligible')->whereNumber('series');
    Route::get('/sessions/{session}', [SportSessionController::class, 'show'])
        ->whereNumber('session');
    Route::get('/sessions/{session}/conversation', [EventConversationController::class, 'show'])->middleware('adult.eligible')
        ->whereNumber('session');
    Route::post('/sessions/{session}/conversation/messages', [EventConversationController::class, 'store'])->middleware('adult.eligible')
        ->whereNumber('session');
    Route::post('/sessions/{session}/conversation/media/prepare', [ConversationMediaController::class, 'prepare'])->middleware('adult.eligible')->whereNumber('session');
    Route::post('/sessions/{session}/conversation/media/complete', [ConversationMediaController::class, 'complete'])->middleware('adult.eligible')->whereNumber('session');
    Route::get('/conversation-media/{media}', [ConversationMediaController::class, 'show'])->middleware('adult.eligible')->whereNumber('media');
    Route::post('/sessions/{session}/conversation/actions', [EventConversationController::class, 'social'])->middleware('adult.eligible')
        ->whereNumber('session');
    Route::get('/sessions/{session}/recommendations', [SportSessionController::class, 'recommendations'])
        ->whereNumber('session');
    Route::post('/sessions/{session}/invites', [SportSessionController::class, 'invite'])->middleware('adult.eligible')
        ->whereNumber('session');
    Route::patch('/sessions/{session}/invite', [SportSessionController::class, 'respondToInvite'])->middleware('adult.eligible')
        ->whereNumber('session');
    Route::patch('/sessions/{session}/participants/{profile}', [SportSessionController::class, 'updateParticipant'])->middleware('adult.eligible')
        ->whereNumber('session');
    Route::post('/sessions/{session}/join', [SportSessionController::class, 'join'])->middleware('adult.eligible')
        ->whereNumber('session');
    Route::patch('/sessions/{session}/occurrence', [SportSessionController::class, 'updateOccurrence'])->middleware('adult.eligible')->whereNumber('session');
    Route::patch('/sessions/{session}/series-from', [SportSessionController::class, 'updateSeriesFromOccurrence'])->middleware('adult.eligible')->whereNumber('session');
    Route::post('/sessions/{session}/cancel', [SportSessionController::class, 'cancelOccurrence'])->middleware('adult.eligible')->whereNumber('session');
    Route::get('/post-match-actions', [PostMatchSportActionController::class, 'index']);
    Route::post('/post-match-actions/session', [PostMatchSportActionController::class, 'saveSession'])->middleware('adult.eligible');

    Route::post('/connections', [ConnectionController::class, 'store']);
    Route::patch('/connections/{connection}', [ConnectionController::class, 'update']);
    Route::post('/reports', [ReportController::class, 'store']);

    Route::get('/ideas', [IdeaController::class, 'index']);
    Route::post('/ideas', [IdeaController::class, 'store']);

    // Roadmap (#7)
    Route::get('/roadmap', [RoadmapController::class, 'index']);
    Route::get('/roadmap/cluster/runs', [ClusteringRunController::class, 'index']);
    Route::get('/roadmap/cluster/runs/{id}', [ClusteringRunController::class, 'show'])
        ->whereNumber('id');
    Route::get('/roadmap/cluster/runs/{id}/decisions', [ClusteringRunController::class, 'decisions'])
        ->whereNumber('id');
    Route::post('/roadmap/cluster', [ClusteringRunController::class, 'store'])
        ->middleware('throttle:clustering');
    Route::get('/roadmap/{id}', [RoadmapController::class, 'show'])
        ->whereNumber('id');
});

Route::put('/conversation-media/upload/{key}', [ConversationMediaController::class, 'localUpload'])->name('conversation-media.upload')->where('key', '.*');
Route::get('/conversation-media/download/{key}', [ConversationMediaController::class, 'localDownload'])->name('conversation-media.download')->where('key', '.*');
