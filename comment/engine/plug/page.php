<?php

Page::_('comments', function(int $chunk = 100, int $i = 0): Comments {
    $comments = [];
    $count = 0;
    if ($path = $this->path) {
        $r = Path::R(Path::F($path), PAGE);
        foreach (g(COMMENT . DS . $r, 'page') as $k => $v) {
            ++$count; // Count comment(s), no filter
            if (is_file($kk = Path::F($k) . DS . 'parent.data') && filesize($kk) > 0) {
                // Has parent comment, skip!
                continue;
            } else if (is_file($k)) {
                $parent = false;
                foreach (stream($k) as $kk => $vv) {
                    if ($kk === 0 && $vv !== '---') {
                        // No header marker means no property at all
                        break;
                    }
                    if ($vv === '...') {
                        // End header marker means no `parent` property found
                        break;
                    }
                    if (
                        strpos($vv, 'parent:') === 0 ||
                        strpos($vv, '"parent":') === 0 ||
                        strpos($vv, "'parent':") === 0
                    ) {
                        // Has parent comment!
                        $parent = true;
                        break;
                    }
                }
                if ($parent) {
                    // Has parent comment, skip!
                    continue;
                }
            }
            $comments[] = $k;
        }
        sort($comments);
    }
    $comments = $chunk === 0 ? [$comments] : array_chunk($comments, $chunk, false);
    $comments = new Comments($comments[$i] ?? []);
    $comments->title = i('%d Comment' . ($count === 1 ? "" : 's'), $count);
    return $comments;
});