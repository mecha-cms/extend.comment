<?php

Page::_('comments', function(int $chunk = 100, int $i = 0): Anemon {
    $comments = [];
    $count = 0;
    if ($path = $this->path) {
        $r = Path::R(Path::F($path), PAGE);
        $files = [];
        foreach (g(COMMENT . DS . $r, 'page') as $v) {
            ++$count; // Count comment(s), no filter
            if (is_file($rr = Path::F($v) . DS . 'parent.data') && filesize($rr) > 0) {
                // Has parent comment, skip!
                continue;
            } else if (is_file($v)) {
                $parent = false;
                foreach (stream($v) as $i => $s) {
                    if ($i === 0 && $s !== '---') {
                        break; // No header(s), no parent!
                    }
                    if ($s === '...') {
                        break; // End header(s), no parent!
                    }
                    if (strpos($s, 'parent:') === 0) {
                        // Has parent comment, skip!
                        $parent = true;
                        break;
                    }
                }
                if ($parent) {
                    // Has parent comment, skip!
                    continue;
                }
            }
            $files[] = $v;
        }
        sort($files);
        $files = $chunk === 0 ? [$files] : array_chunk($files, $chunk, false);
        if (!empty($files[$i])) {
            foreach ($files[$i] as $v) {
                $comments[] = new Comment($v);
            }
        }
    }
    $comments = new Anemon($comments);
    $comments->title = $count . ' ' . Language::get('comment' . ($count === 1 ? "" : 's'));
    return $comments;
});