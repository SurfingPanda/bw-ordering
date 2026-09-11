<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Read-only staff inbox for transcripts from the public Moymoy widget. */
class AssistantChatController extends Controller
{
    private function authorizeStaff(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'assistant-chats'), 403, 'Forbidden.');
    }

    public function index(Request $request)
    {
        $this->authorizeStaff($request);
        $email = $this->supabaseUser($request)['email'] ?? null;
        $query = trim((string) $request->query('q', ''));

        // Group the append-only turn log into one row per browser chat. The
        // subquery keeps this portable between MySQL and the test SQLite DB.
        $conversationIds = AssistantMessage::query()
            ->select('conversation_id', DB::raw('MAX(created_at) as latest_at'))
            ->when($query !== '', fn ($q) => $q->where(function ($where) use ($query) {
                $where->where('content', 'like', "%{$query}%")
                    ->orWhere('user_email', 'like', "%{$query}%")
                    ->orWhere('conversation_id', 'like', "%{$query}%");
            }))
            ->groupBy('conversation_id')
            ->orderByDesc('latest_at')
            ->paginate(30, ['conversation_id', DB::raw('MAX(created_at) as latest_at')], 'page')
            ->withQueryString();

        $ids = $conversationIds->pluck('conversation_id')->all();
        $messages = AssistantMessage::whereIn('conversation_id', $ids)
            ->orderBy('created_at')->orderBy('id')->get()
            ->groupBy('conversation_id');

        return view('admin.assistant-chats.index', [
            'conversations' => $conversationIds,
            'messages' => $messages,
            'query' => $query,
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($email),
            'navAccess' => $this->editorNavAccess($email),
        ]);
    }
}
