<?php namespace _\lot\x\comment;

// Set a new comment!
function route($lot, $type) {
    $active = \State::get('x.user') !== null && \Is::user();
    $state = \State::get('x.comment', true);
    $error = $lot['_error'] ?? 0;
    if ($type !== 'Post' || !\is_file(\PAGE . \DS . $this[0] . '.page')) {
        \Alert::error('You cannot write a comment here. This is usually due to the page data that is dynamically generated.');
        ++$error;
    }
    $default = \array_replace_recursive(
        (array) \State::get('x.page.page', true),
        (array) ($state['page'] ?? [])
    );
    $lot = \array_replace_recursive($default, $lot);
    $lot['status'] = $active ? 1 : 2;
    extract($lot, \EXTR_SKIP);
    global $url;
    if (empty($token) || !\Guard::check($token, 'comment')) {
        \Alert::error('Invalid token.');
        ++$error;
    }
    $guard = $state['guard'] ?? [];
    foreach (['author', 'email', 'link', 'content'] as $key) {
        if (!isset($lot[$key])) {
            continue;
        }
        $k = \ucfirst($key);
        // Check for empty field(s)
        if (\Is::void($lot[$key])) {
            if ($key !== 'link') { // `link` field is optional
                \Alert::error('Please fill out the %s field.', $k);
                ++$error;
            }
        }
        // Check for field(s) length
        if (isset($guard['max'][$key]) && \gt($lot[$key], $guard['max'][$key])) {
            \Alert::error('%s too long.', $k);
            ++$error;
        } else if (isset($guard['min'][$key]) && \lt($lot[$key], $guard['min'][$key])) {
            if ($key !== 'link') { // `link` field is optional
                \Alert::error('%s too short.', $k);
                ++$error;
            }
        }
    }
    if ($error === 0 && isset($author)) {
        $author = \strpos($author, '@') !== 0 ? \To::text($author) : $author;
    }
    if ($error === 0 && isset($content)) {
        $content = \To::text((string) $content, 'a,abbr,b,br,cite,code,del,dfn,em,i,img,ins,kbd,mark,q,span,strong,sub,sup,time,u,var', true);
        if (
            (
                !isset($lot['type']) ||
                $lot['type'] === 'HTML' ||
                $lot['type'] === 'text/html'
            ) &&
            \strpos($content, '</p>') === false
        ) {
            // Replace new line with `<br>` and `<p>` tag(s)
            $content = '<p>' . \str_replace(["\n\n", "\n"], ['</p><p>', '<br>'], $content) . '</p>';
        }
        // Permanently disable the `[[e]]` block(s) in comment
        if (\State::get('x.block') !== null) {
            $e = \Block::$state[0];
            $content = \str_replace([
                $e[0] . 'e' . $e[1], // `[[e]]`
                $e[0] . $e[2] . 'e' . $e[1] // `[[/e]]`
            ], "", $content);
        }
        // Temporarily disallow image(s) in comment to prevent XSS
        if (\strpos($content, '<img ') !== false) {
            $content = \preg_replace('#<img(?:\s[^>]*)?>#i', '<!-- $0 -->', $content);
        }
    }
    if ($error === 0 && !$active) {
        if (!empty($email) && !\Is::email($email)) {
            \Alert::error('Invalid %s format.', 'Email');
            ++$error;
        }
        if (!empty($link) && !\Is::URL($link)) {
            \Alert::error('Invalid %s format.', 'Link');
            ++$error;
        }
    }
    // Check for duplicate comment
    if (\Session::get('comment.content') === $content) {
        \Alert::error('You have sent that comment already.');
        ++$error;
    } else {
        // Block user by IP address
        if (!empty($guard['x']['ip'])) {
            $ip = \Get::IP();
            foreach ($guard['x']['ip'] as $v) {
                if ($ip === $v) {
                    \Alert::error('Blocked IP address: %s', $ip);
                    ++$error;
                    break;
                }
            }
        }
        // Block user by UA keyword(s)
        if (!empty($guard['x']['ua'])) {
            $ua = \Get::UA();
            foreach ($guard['x']['ua'] as $v) {
                if (\stripos($ua, $v) !== false) {
                    \Alert::error('Blocked user agent: %s', $ua);
                    ++$error;
                    break;
                }
            }
        }
        // Check for spam keyword(s) in comment
        if (!empty($guard['x']['query'])) {
            $any = ($author ?? "") . ($email ?? "") . ($link ?? "") . ($content ?? "");
            foreach ($guard['x']['query'] as $v) {
                if (\stripos($any, $v) !== false) {
                    \Alert::error('Please choose another word: %s', $v);
                    ++$error;
                    break;
                }
            }
        }
    }
    // Store comment to file
    $t = \time();
    $anchor = $state['anchor'];
    $directory = \COMMENT . \DS . $this[0] . \DS . \date('Y-m-d-H-i-s', $t);
    $file = $directory . '.' . ($x = $state['page']['x'] ?? 'page');
    if ($error > 0) {
        \Session::set('form', $form);
    } else {
        \Session::let('form');
        $data = [
            'author' => $author,
            'email' => $email ?? false ?: false,
            'link' => $link ?? false ?: false,
            'status' => $status,
            'content' => $content
        ];
        foreach ($default as $k => $v) {
            if (isset($data[$k]) && $data[$k] === $v) {
                unset($data[$k]);
            }
        }
        $p = new \Page($file);
        $p->set($data)->save(0600);
        if (!\Is::void($parent)) {
            $f = new \File($directory . \DS . 'parent.data');
            $f->set((new \Time($parent))->name)->save(0600);
        }
        \Hook::fire('on.comment.set', [new \File($file), null], new \Comment($file));
        \Alert::success('Comment created.');
        \Session::set('comment', $data);
        if ($x === 'draft') {
            \Alert::info('Your comment will be visible once approved by the author.');
        } else {
            \Guard::kick($this[0] . $url->query('&', ['parent' => false]) . '#' . \sprintf($anchor[0], \sprintf('%u', $t)));
        }
    }
    \Guard::kick($this[0] . $url->query . '#' . $anchor[1]);
}

\Route::set('.comment/*', 200, __NAMESPACE__ . "\\route");