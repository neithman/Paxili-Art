<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\ModerationLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Artwork moderation: approve, reject (with reason) or delete (with reason).
 * The seller is notified automatically every time.
 */
class ArtworkController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $artworks = Artwork::with(['store', 'category'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->q, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->latest()->paginate(12)->withQueryString();

        $counts = Artwork::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.artworks.index', compact('artworks', 'status', 'counts'));
    }

    public function show(Artwork $artwork)
    {
        $artwork->load(['store.user', 'category', 'moderationLogs.admin']);

        return view('admin.artworks.show', compact('artwork'));
    }

    /** Lets the admin open the PRIVATE original file to check it. */
    public function file(Artwork $artwork)
    {
        abort_unless(Storage::disk('local')->exists($artwork->file_path), 404);

        return Storage::disk('local')->response($artwork->file_path, $artwork->file_name);
    }

    public function approve(Request $request, Artwork $artwork)
    {
        $artwork->update(['status' => Artwork::APPROVED, 'rejection_reason' => null]);
        $this->log($request, $artwork, 'approved');

        Notification::send($artwork->store->user, 'artwork_approved', 'Artwork approved ✔',
            "\"{$artwork->title}\" is now live in the marketplace.",
            route('artworks.show', $artwork->slug));

        return redirect()->route('admin.artworks.index')->with('success', "\"{$artwork->title}\" approved.");
    }

    public function reject(Request $request, Artwork $artwork)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $artwork->update(['status' => Artwork::REJECTED, 'rejection_reason' => $request->reason]);
        $this->log($request, $artwork, 'rejected', $request->reason);

        Notification::send($artwork->store->user, 'artwork_rejected', 'Artwork rejected',
            "\"{$artwork->title}\" was rejected. Reason: {$request->reason}",
            route('seller.artworks.edit', $artwork));

        return redirect()->route('admin.artworks.index')->with('success', "\"{$artwork->title}\" rejected.");
    }

    /** Delete censored / prohibited artwork. Files are destroyed permanently. */
    public function destroy(Request $request, Artwork $artwork)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $this->log($request, $artwork, 'deleted', $request->reason);

        Storage::disk('local')->delete($artwork->file_path);
        Storage::disk('public')->delete($artwork->preview_path);

        $artwork->update(['status' => Artwork::REMOVED, 'rejection_reason' => $request->reason]);
        $artwork->delete(); // soft delete keeps order history readable

        Notification::send($artwork->store->user, 'artwork_deleted', 'Artwork removed by admin',
            "\"{$artwork->title}\" was removed. Reason: {$request->reason}",
            route('seller.artworks.index'));

        return redirect()->route('admin.artworks.index')->with('success', "\"{$artwork->title}\" deleted.");
    }

    private function log(Request $request, Artwork $artwork, string $action, ?string $reason = null): void
    {
        ModerationLog::create([
            'artwork_id' => $artwork->id,
            'admin_id' => $request->user()->id,
            'artwork_title' => $artwork->title,
            'action' => $action,
            'reason' => $reason,
        ]);
    }
}
