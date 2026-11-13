<?php
namespace App\Controllers;

use App\{View, Database, Auth, Csrf};
use App\Models\{Discussion, Comment};

final class DiscussionController
{
    public static function index(): void
    {
        echo View::render('index', ['title' => 'Discussions', 'discussions' => Discussion::all()]);
    }

    public static function show(string $rawId): void
    {
        // Router only matches digits; still reject 0 / overflow as 400 (documented in README).
        if (!ctype_digit($rawId) || strlen($rawId) > 10 || (int)$rawId < 1) {
            http_response_code(400);
            echo View::render('error', ['title' => 'Bad request', 'message' => 'Invalid discussion id.']);
            return;
        }
        $id = (int)$rawId;
        $d = Discussion::find($id);
        if (!$d) {
            http_response_code(404);
            echo View::render('error', ['title' => 'Not found', 'message' => 'Discussion not found.']);
            return;
        }
        $cfg = Database::config();
        $rows = Comment::forDiscussion($id, $cfg['max_traversal']);
        echo View::render('discussion', [
            'title' => $d['title'], 'd' => $d,
            'comments' => self::flatten($rows, $cfg['max_indent_depth']),
            'flash' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    /**
     * Group by parent, then walk depth-first from the roots (parent NULL).
     * Iterative with a visited set: cycles / unreachable rows can never loop or render.
     * depth = real depth (label); indent = min(depth, cap).
     */
    public static function flatten(array $rows, int $cap): array
    {
        $byParent = [];
        foreach ($rows as $r) $byParent[$r['parent_id'] ?? 0][] = $r;
        $out = [];
        $open = [];   // depths of comments whose reply container is currently open
        $seen = [];
        $stack = [];
        foreach (array_reverse($byParent[0] ?? []) as $r) $stack[] = [$r, 0];
        while ($stack) {
            [$r, $depth] = array_pop($stack);
            if (isset($seen[$r['id']])) continue;
            $seen[$r['id']] = true;
            $kids = $byParent[$r['id']] ?? [];
            $closes = 0;
            while ($open && end($open) >= $depth) { array_pop($open); $closes++; }
            $out[] = [
                'closes' => array_fill(0, $closes, 1),
                'id' => $r['id'], 'author' => $r['author'], 'body' => $r['body'],
                'created_at' => $r['created_at'] . ' UTC', 'depth' => $depth,
                'indent' => min($depth, $cap) * 24, 'has_children' => (bool)$kids,
                'avatar' => self::avatar($r),
            ];
            if ($kids) $open[] = $depth;
            foreach (array_reverse($kids) as $k) $stack[] = [$k, $depth + 1];
        }
        if ($out) $out[count($out) - 1]['end_closes'] = array_fill(0, count($open), 1);
        return $out;
    }

    /** Default avatar unless the user opted in to Gravatar (discloses an email-derived hash to gravatar.com). */
    private static function avatar(array $r): string
    {
        if (!empty($r['use_gravatar']) && !empty($r['email'])) {
            return 'https://www.gravatar.com/avatar/' . hash('sha256', strtolower(trim($r['email']))) . '?s=48&d=mp';
        }
        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24'%3E%3Ccircle cx='12' cy='12' r='12' fill='%23999'/%3E%3C/svg%3E";
    }
}
