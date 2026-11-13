<?php
namespace App\Controllers;

use App\{View, Auth, Csrf, Database};
use App\Models\{Discussion, Comment};

final class CommentController
{
    private static function stop(int $code, string $msg): void
    {
        http_response_code($code);
        echo View::render('error', ['title' => "Error $code", 'message' => $msg]);
    }

    public static function store(string $rawId): void
    {
        // 1. Server-side authentication check (not just hiding the form).
        $uid = Auth::id();
        if ($uid === null) { self::stop(401, 'You must be logged in to post a reply.'); return; }
        // 2. CSRF.
        if (!Csrf::valid($_POST['csrf'] ?? null)) { self::stop(403, 'Invalid CSRF token.'); return; }
        // 3. Discussion exists.
        $did = (int)$rawId;
        if ($did < 1 || !Discussion::find($did)) { self::stop(404, 'Discussion not found.'); return; }
        // 4. Input validation.
        $cfg = Database::config();
        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > $cfg['max_body']) { self::stop(422, 'Comment must be 1-' . $cfg['max_body'] . ' characters.'); return; }
        // 5. Parent belongs to the same discussion; depth limit. user_id comes from the session only.
        $parent = null;
        $p = $_POST['parent_id'] ?? '';
        if ($p !== '' && $p !== null) {
            if (!ctype_digit((string)$p) || strlen((string)$p) > 10) { self::stop(422, 'Invalid parent.'); return; }
            $parent = (int)$p;
            if (!Comment::find($parent, $did)) { self::stop(422, 'Parent comment is not in this discussion.'); return; }
            if (Comment::depthOf($parent, $did, $cfg['max_write_depth']) + 1 > $cfg['max_write_depth']) { self::stop(422, 'Reply nesting too deep.'); return; }
        }
        // Cycles are impossible: parent_id is set only at INSERT (immutable) and always points to an older row.
        $id = Comment::create($did, $parent, $uid, $body);
        View::redirect("/discussions/$did#comment-$id");
    }
}
