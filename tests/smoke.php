<?php
// Usage: BASE=http://127.0.0.1:8080 DB_PORT... php tests/smoke.php   (server + DB must be running, schema loaded)
$base = getenv('BASE') ?: 'http://127.0.0.1:8080';
$fails = 0;
function req(string $m, string $path, array $post = [], string $jar = ''): array {
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $m, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($m === 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return [$c, (string)$b];
}
function check(string $name, bool $ok): void { global $fails; echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }
function csrf(string $html): string { preg_match('/name="csrf" value="([0-9a-f]+)"/', $html, $m); return $m[1] ?? ''; }
$jar = tempnam(sys_get_temp_dir(), 'j'); $guest = tempnam(sys_get_temp_dir(), 'g');
$pdo = new PDO('mysql:host=127.0.0.1;port=' . (getenv('DB_PORT') ?: 3307) . ';dbname=nested_forum', 'root', '');
$count = fn() => (int)$pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
$u = 'tester' . random_int(1000, 9999);

[$c, $h] = req('GET', '/discussions/1', [], $guest);
check('read as guest 200', $c === 200);
check('depth 4 label shown, 7 seeded comments', str_contains($h, 'Depth 4') && substr_count($h, 'class="c"') === 7);
check('indent capped at 8 (192px) while labels stay accurate', !str_contains($h, 'margin-left:200px'));
check('invalid id /discussions/abc -> 404', req('GET', '/discussions/abc')[0] === 404);
check('/discussions/0 -> 400', req('GET', '/discussions/0')[0] === 400);
check('missing discussion -> 404', req('GET', '/discussions/99999')[0] === 404);

$before = $count();
check('guest POST rejected (401)', req('POST', '/discussions/1/comments', ['body' => 'x', 'csrf' => csrf($h)], $guest)[0] === 401);
check('guest POST created no row', $count() === $before);

[, $rf] = req('GET', '/register', [], $jar);
check('register bad csrf 403', req('POST', '/register', ['username' => $u, 'email' => "$u@example.test", 'password' => 'password123', 'csrf' => 'bad'], $jar)[0] === 403);
check('register ok (303->login)', in_array(req('POST', '/register', ['username' => $u, 'email' => "$u@example.test", 'password' => 'password123', 'csrf' => csrf($rf)], $jar)[0], [200, 303]));
check('duplicate account 409', req('POST', '/register', ['username' => $u, 'email' => "$u@example.test", 'password' => 'password123', 'csrf' => csrf($rf)], $jar)[0] === 409);
[, $lf] = req('GET', '/login', [], $jar);
check('wrong password 401', req('POST', '/login', ['username' => $u, 'password' => 'nope', 'csrf' => csrf($lf)], $jar)[0] === 401);
check('seed user (NULL hash) cannot log in', req('POST', '/login', ['username' => 'alice', 'password' => '', 'csrf' => csrf($lf)], $jar)[0] === 401);
req('POST', '/login', ['username' => $u, 'password' => 'password123', 'csrf' => csrf($lf)], $jar);
[, $h] = req('GET', '/discussions/1', [], $jar); $t = csrf($h);
check('logged in: sees username', str_contains($h, "Signed in as $u"));

req('POST', '/discussions/1/comments', ['body' => 'depth-5 reply', 'parent_id' => 7, 'csrf' => $t], $jar);
req('POST', '/discussions/1/comments', ['body' => 'second top-level', 'csrf' => $t], $jar);
[, $h] = req('GET', '/discussions/1', [], $jar);
check('depth-5 reply stored with my author', (bool)preg_match("/<strong>$u<\\/strong>[^<]*- Depth 5/", $h));
check('second top-level at depth 0', (bool)preg_match("/<strong>$u<\\/strong>[^<]*- Depth 0/", $h));
$n = $count();
check('bad CSRF 403, no row', req('POST', '/discussions/1/comments', ['body' => 'x', 'csrf' => 'bad'], $jar)[0] === 403 && $count() === $n);
check('cross-discussion parent 422, no row', req('POST', '/discussions/2/comments', ['body' => 'x', 'parent_id' => 1, 'csrf' => $t], $jar)[0] === 422 && $count() === $n);
check('missing discussion POST 404', req('POST', '/discussions/9999/comments', ['body' => 'x', 'csrf' => $t], $jar)[0] === 404);
check('forged user_id ignored', (function () use ($t, $jar, $pdo, $u) {
    req('POST', '/discussions/1/comments', ['body' => 'forge', 'user_id' => 1, 'csrf' => $t], $jar);
    return $pdo->query("SELECT u.username FROM comments c JOIN users u ON u.id=c.user_id WHERE c.body='forge'")->fetchColumn() === $u;
})());
req('POST', '/discussions/1/comments', ['body' => '<script>alert(1)</script>', 'csrf' => $t], $jar);
[, $h] = req('GET', '/discussions/1', [], $jar);
check('HTML escaped', str_contains($h, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($h, '<script>alert'));
req('POST', '/logout', ['csrf' => $t], $jar);
$n = $count();
check('after logout POST rejected', req('POST', '/discussions/1/comments', ['body' => 'x', 'csrf' => $t], $jar)[0] === 401 && $count() === $n);
echo $fails ? "\n$fails FAILED\n" : "\nALL PASSED\n"; exit($fails ? 1 : 0);
